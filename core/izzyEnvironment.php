<?php
/** Resolución y lectura de configuración reutilizadas por la aplicación y el diagnóstico CLI. */

function izzyResolveEnvFile(string $host, string $target = ''): string
{
    $override = trim((string)(getenv('IZZY_ENV_FILE') ?: ''));
    $host = strtolower(preg_replace('/:\\d+$/', '', trim($host)));
    $demo = $target === 'demo' || ($target === '' && str_starts_with($host, 'demo.'));
    if ($override !== '') return $override;
    if ($target === 'local' || ($target === '' && PHP_OS_FAMILY === 'Windows')) {
        return 'C:\\credentials\\newizzy\\.env';
    }
    return '/home/esmultiservicios/credentials/' . ($demo ? 'devizzy' : 'newizzy') . '/.env';
}

function izzyPrivateEnvFile(string $path): string
{
    $file = realpath($path);
    if ($file === false || !is_file($file)) {
        throw new RuntimeException('El archivo privado de IZZY no existe o no tiene permisos de lectura.');
    }
    $normalize = static fn(string $value): string => strtolower(str_replace('\\', '/', $value));
    foreach ([dirname(__DIR__), $_SERVER['DOCUMENT_ROOT'] ?? ''] as $directory) {
        if ($directory === '' || ($root = realpath($directory)) === false) continue;
        if (str_starts_with($normalize($file), rtrim($normalize($root), '/') . '/')) {
            throw new RuntimeException('El archivo de configuración debe estar fuera de la carpeta pública y del repositorio.');
        }
    }
    return $file;
}

function izzyRequiredKeys(): array
{
    return ['SERVER', 'USER', 'PASS', 'DB_MAIN', 'SECRET_KEY', 'SECRET_IV'];
}

function izzyMissingKeys(array $values, array $keys): array
{
    return array_values(array_filter($keys, static fn(string $key): bool => trim((string)($values[$key] ?? '')) === ''));
}

function izzyAdministrativeModules(): array
{
    return [
        'cPanel' => ['CPANEL_HOST', 'CPANEL_USERNAME', 'CPANEL_TOKEN'],
        'Subdominios cPanel' => ['CPANEL_HOST', 'CPANEL_USERNAME', 'CPANEL_TOKEN', 'CPANEL_DOMINIO'],
        'WHM' => ['WHM_HOST', 'WHM_USERNAME', 'WHM_TOKEN'],
    ];
}

class IzzyAdministrativeConfigurationException extends RuntimeException {}

function izzyRequireAdministrativeConfig(string $module): void
{
    $values = [];
    foreach (izzyAdministrativeModules()[$module] as $key) $values[$key] = defined($key) ? constant($key) : null;
    $missing = izzyMissingKeys($values, array_keys($values));
    if ($missing) {
        throw new IzzyAdministrativeConfigurationException($module . ': faltan ' . implode(', ', $missing) . '.');
    }
    $host = (string)$values[$module === 'WHM' ? 'WHM_HOST' : 'CPANEL_HOST'];
    if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        throw new IzzyAdministrativeConfigurationException($module . ': configure un nombre de servidor válido.');
    }
    $port = filter_var($module === 'WHM' ? izzyEnv('WHM_PORT', '2087') : CPANEL_PORT, FILTER_VALIDATE_INT);
    $prefix = $module === 'WHM' ? 'WHM' : 'CPANEL';
    if (!preg_match('/^[a-z0-9_.-]+$/iD', $values[$prefix.'_USERNAME']) || preg_match('/[\\x00-\\x20\\x7f]/', $values[$prefix.'_TOKEN'])) {
        throw new IzzyAdministrativeConfigurationException($module . ': revise el usuario y el formato del token API.');
    }
    if ($port === false || $port < 1 || $port > 65535 || !function_exists('curl_init')) {
        throw new IzzyAdministrativeConfigurationException($module . ': compruebe el puerto HTTPS y la extensión cURL.');
    }
}

function izzyOptionalEnv(string $key): ?string
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null || trim((string)$value) === '' ? null : (string)$value;
}

function izzyLoadEnvFile(string $envFile): array
{
    if (!is_file($envFile)) {
        throw new RuntimeException(
            'No se encontró el archivo de configuración segura de IZZY en: ' . $envFile
        );
    }

    $lines = @file($envFile, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException(
            'No se pudo leer el archivo de configuración segura de IZZY: ' . $envFile
        );
    }

    $values = [];
    foreach ($lines as $lineNumber => $line) {
        if ($lineNumber === 0) $line = preg_replace('/^\\xEF\\xBB\\xBF/', '', $line);
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        $separatorPosition = strpos($line, '=');

        if ($separatorPosition === false) {
            continue;
        }

        $key = trim(substr($line, 0, $separatorPosition));
        $value = trim(substr($line, $separatorPosition + 1));

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $key)) {
            continue;
        }

        $valueLength = strlen($value);

        if ($valueLength >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[$valueLength - 1];

            if (
                ($firstChar === '"' && $lastChar === '"') ||
                ($firstChar === "'" && $lastChar === "'")
            ) {
                $value = substr($value, 1, -1);
            }
        }

        $values[$key] = $value;
    }
    return $values;
}

function izzyEnv(string $key, ?string $default = null): string
{
    if (array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    } else {
        $value = getenv($key);
    }

    if ($value === false || $value === null || $value === '') {
        if ($default !== null) {
            return $default;
        }

        throw new RuntimeException(
            'Falta la variable requerida "' . $key . '" en el archivo .env de IZZY.'
        );
    }

    return (string) $value;
}

function izzyEnvBool(string $key, bool $default = false): bool
{
    if (array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    } else {
        $value = getenv($key);
    }

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $parsed === null ? $default : $parsed;
}

