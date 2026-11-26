<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/core/izzyEnvironment.php';

$options = getopt('', ['entorno:', 'exigir:']);
$target = $options['entorno'] ?? (PHP_OS_FAMILY === 'Windows' ? 'local' : 'produccion');
$requiredModule = $options['exigir'] ?? '';
if (!is_string($target) || !in_array($target, ['produccion','demo','local'], true)
    || !is_string($requiredModule) || !in_array($requiredModule, ['', 'cpanel','whm','cocina','api-externa','cron'], true)) {
    fwrite(STDERR, "Uso: php scripts/validar_configuracion.php --entorno=produccion|demo|local [--exigir=cpanel|whm|cocina|api-externa|cron]\n");
    exit(2);
}
$path = izzyResolveEnvFile('', $target);
echo "Entorno solicitado: {$target}\nArchivo seleccionado: {$path}\n";
if (getenv('IZZY_ENV_FILE')) echo "Aviso: IZZY_ENV_FILE tiene prioridad sobre la ruta predeterminada del entorno.\n";
try {
    $file = izzyPrivateEnvFile($path);
    $env = izzyLoadEnvFile($file);
} catch (RuntimeException $error) {
    fwrite(STDERR, "CRÍTICO: ".$error->getMessage()."\n");
    exit(1);
}

echo "Archivo existente, privado y legible.\n";
if (PHP_OS_FAMILY !== 'Windows') {
    $permissions = fileperms($file);
    if ($permissions !== false && ($permissions & 0037) !== 0) {
        echo "Aviso: revise los permisos del archivo para limitar su acceso.\n";
    }
}
$missing = izzyMissingKeys($env, izzyRequiredKeys());
echo 'Variables base presentes: '.implode(', ', array_diff(izzyRequiredKeys(),$missing))."\n";
if ($missing) echo 'CRÍTICO: faltan '.implode(', ', $missing).".\n";
$extensions = ['mysqli','openssl','mbstring'];
$missingExtensions = array_values(array_filter($extensions, static fn(string $name): bool => !extension_loaded($name)));
if ($missingExtensions) echo 'CRÍTICO: extensiones ausentes '.implode(', ', $missingExtensions).".\n";

$modules = [];
foreach (izzyAdministrativeModules() as $module => $keys) {
    $moduleMissing = izzyMissingKeys($env, $keys);
    $prefix = $module === 'WHM' ? 'WHM' : 'CPANEL';
    $host = (string)($env[$prefix.'_HOST'] ?? '');
    $port = filter_var($env[$prefix.'_PORT'] ?? ($prefix === 'WHM' ? '2087' : '2083'), FILTER_VALIDATE_INT);
    $invalidNetwork = $host !== '' && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    $modules[$module] = [
        ...$moduleMissing, ...($invalidNetwork ? [$prefix.'_HOST inválido'] : []),
        ...($port < 1 || $port > 65535 ? [$prefix.'_PORT inválido'] : []),
        ...(!extension_loaded('curl') ? ['extensión cURL'] : []),
    ];
}
$modules['Cocina'] = preg_match('/^[a-f0-9]{64}$/iD', trim((string)($env['IZZY_COCINA_TOKEN_CIPHER_KEY'] ?? '')))
    ? [] : ['IZZY_COCINA_TOKEN_CIPHER_KEY ausente o inválida'];
$externalKey = trim((string)($env['IZZY_EXTERNAL_API_ENCRYPTION_KEY'] ?? getenv('IZZY_EXTERNAL_API_ENCRYPTION_KEY') ?: ''));
$legacyPath = dirname($file).'/.izzy_external_api_aes.key';
$modules['API externa / ZYNKO'] = !$externalKey
    ? (is_readable($legacyPath) ? [] : ['llave ausente, disponible para altas nuevas si el directorio admite autoaprovisionamiento'])
    : (preg_match('/^[a-f0-9]{64}$/iD', $externalKey) ? [] : ['llave del entorno inválida']);
if (!extension_loaded('curl')) $modules['API externa / ZYNKO'][]='extensión cURL';
$modules['Cron de altas'] = [];
if (!is_readable(dirname(__DIR__).'/plantilla/plantilla_izzy.sql')) $modules['Cron de altas'][]='plantilla SQL';
if (!function_exists('exec')) {
    $modules['Cron de altas'][]='exec de PHP CLI';
} else {
    exec('mysql --version 2>&1', $discard, $status);
    if ($status !== 0) $modules['Cron de altas'][]='cliente mysql en PATH';
}
foreach ($modules as $module => $issues) {
    echo $module.($issues ? ' — revisar '.implode(', ', $issues) : ' — dependencias de configuración presentes')."\n";
}
$optional = ['SERVER_MAIN','DB_PRUEBA','DB_PREFIX','CPANEL_PORT','WHM_PORT','WHM_TIMEOUT','API_TIMEOUT','DB_MAX_LENGTH','METHOD','WEB_SCRAPING_DOLARES','IZZY_DOCUMENT_AUTO_EMAIL','IZZY_APP_VERSION'];
echo 'Opcionales sin ajustar en archivo: '.implode(', ', izzyMissingKeys($env, $optional))."\n";
$obsolete = array_intersect(['MYSQL_USER','MYSQL_PASS','CPANEL_PASSWORD','SSL_VERIFICATION','SISTEMA_PRUEBA','IZZY_ZYNKO_TEST_NUMBERS'],array_keys($env));
if ($obsolete) echo 'Heredadas detectadas: '.implode(', ', $obsolete)."\n";
$moduleLabels=['cpanel'=>'cPanel','whm'=>'WHM','cocina'=>'Cocina','api-externa'=>'API externa / ZYNKO','cron'=>'Cron de altas'];
$failed = $missing || $missingExtensions || ($requiredModule !== '' && $modules[$moduleLabels[$requiredModule]]);
echo $failed ? "Resultado: requiere corrección.\n" : "Resultado: configuración base disponible. Revise los módulos que utilizará.\n";
echo "Alcance: comprobación local. Use process_jobs.php --check para confirmar la conexión a DB_MAIN y la cola.\n";
exit($failed ? 1 : 0);

