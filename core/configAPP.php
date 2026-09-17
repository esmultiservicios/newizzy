<?php
/*
 * IZZY - Configuración principal de NEWIZZY (Producción)
 *
 * Las credenciales ya NO se guardan en este archivo.
 *
 * Este archivo corresponde a NEWIZZY y se versiona junto con el proyecto.
 * Los secretos permanecen exclusivamente en los archivos .env externos de cada ambiente.
 *
 * LOCAL:
 *   C:\credentials\newizzy\.env
 *
 * PRODUCCIÓN - NEWIZZY:
 *   /home/esmultiservicios/credentials/newizzy/.env
 *
 * DEVIZZY debe utilizar su propio configAPP.php apuntando a:
 *   /home/esmultiservicios/credentials/devizzy/.env
 *
 * Nunca colocar contraseñas, tokens, API keys ni otros secretos directamente aquí.
 */

/* =========================================================
   SESIÓN
   ========================================================= */
/*
 * IMPORTANTE:
 * La sesión debe abrirse ANTES de comprobar user_sd.
 * De lo contrario, en cada nueva petición PHP todavía no conoce
 * el contenido de la sesión y podría sobrescribir user_sd con null,
 * provocando que el login regrese nuevamente a la pantalla de acceso.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

if (!array_key_exists('user_sd', $_SESSION)) {
    $_SESSION['user_sd'] = null;
}

/* =========================================================
   CARGA DEL .ENV
   ========================================================= */
function izzyLoadEnvFile(string $envFile): void
{
    if (!is_file($envFile)) {
        throw new RuntimeException(
            'No se encontró el archivo de configuración segura de IZZY en: ' . $envFile
        );
    }

    if (!is_readable($envFile)) {
        throw new RuntimeException(
            'El archivo de configuración segura de IZZY no tiene permisos de lectura: ' . $envFile
        );
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        throw new RuntimeException(
            'No se pudo leer el archivo de configuración segura de IZZY: ' . $envFile
        );
    }

    foreach ($lines as $line) {
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

        if ($key === '') {
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

        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        putenv($key . '=' . $value);
    }
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

/*
 * Permite sobrescribir la ruta sin modificar el código:
 * IZZY_ENV_FILE=/ruta/personalizada/.env
 */
$customEnvFile = getenv('IZZY_ENV_FILE');

/*
 * NEWIZZY usa las rutas predeterminadas indicadas abajo.
 * IZZY_ENV_FILE permite sobrescribir la ruta de forma explícita sin editar este archivo.
 */
if ($customEnvFile !== false && trim($customEnvFile) !== '') {
    $envFile = trim($customEnvFile);
} elseif (DIRECTORY_SEPARATOR === '\\') {
    $envFile = 'C:\\credentials\\newizzy\\.env';
} else {
    $envFile = '/home/esmultiservicios/credentials/newizzy/.env';
}

izzyLoadEnvFile($envFile);

/* =========================================================
   DATOS DE CONEXIÓN DEL CLIENTE
   ========================================================= */
define('SERVER', izzyEnv('SERVER'));
define('USER', izzyEnv('USER'));
define('PASS', izzyEnv('PASS'));

/* =========================================================
   DATOS DE CONEXIÓN SERVIDOR PRINCIPAL
   ========================================================= */
define('SERVER_MAIN', izzyEnv('SERVER_MAIN'));
define('DB_MAIN', izzyEnv('DB_MAIN'));
define('DB_PRUEBA', izzyEnv('DB_PRUEBA'));

/* =========================================================
   MYSQL ROOT
   ========================================================= */
define('MYSQL_USER', izzyEnv('MYSQL_USER'));
define('MYSQL_PASS', izzyEnv('MYSQL_PASS'));

/* =========================================================
   CPANEL
   ========================================================= */
define('CPANEL_TOKEN', izzyEnv('CPANEL_TOKEN'));
define('CPANEL_USERNAME', izzyEnv('CPANEL_USERNAME'));
define('CPANEL_PASSWORD', izzyEnv('CPANEL_PASSWORD'));
define('CPANEL_HOST', izzyEnv('CPANEL_HOST'));
define('CPANEL_PORT', izzyEnv('CPANEL_PORT', '2083'));
define('CPANEL_DB_USERNAME', USER);
define('CPANEL_DB_PASSWORD', PASS);
define('CPANEL_DOMINIO', izzyEnv('CPANEL_DOMINIO'));

/* =========================================================
   WHM
   ========================================================= */
define('WHM_HOST', izzyEnv('WHM_HOST'));
define('WHM_PORT', (int) izzyEnv('WHM_PORT', '2087'));
define('WHM_USERNAME', izzyEnv('WHM_USERNAME'));
define('WHM_TOKEN', izzyEnv('WHM_TOKEN'));
define('WHM_TIMEOUT', (int) izzyEnv('WHM_TIMEOUT', '30'));

/* =========================================================
   API CAMBIO DÓLAR
   ========================================================= */
define(
    'WEB_SCRAPING_DOLARES',
    izzyEnv(
        'WEB_SCRAPING_DOLARES',
        'https://www.bancopromerica.com/banca-de-empresas/banca-internacional/mesa-de-cambio/'
    )
);

/* =========================================================
   CONFIGURACIÓN PARA NOMBRES DE BASE DE DATOS
   ========================================================= */
define('DB_PREFIX', CPANEL_USERNAME);
define('DB_MAX_LENGTH', (int) izzyEnv('DB_MAX_LENGTH', '10'));

/* =========================================================
   CONFIGURACIÓN DE SEGURIDAD
   ========================================================= */
define('API_TIMEOUT', (int) izzyEnv('API_TIMEOUT', '60'));
define('SSL_VERIFICATION', izzyEnvBool('SSL_VERIFICATION', false));

$GLOBALS['DB_MAIN'] = DB_MAIN;

/* =========================================================
   BASE DE DATOS EXCEPTION LOGIN CONTROLADOR
   ========================================================= */
define('DB_MAIN_LOGIN_CONTROLADOR', DB_MAIN);

/* =========================================================
   CIFRADO
   IMPORTANTE: NO CAMBIAR si ya existen datos cifrados
   ========================================================= */
define('METHOD', izzyEnv('METHOD', 'AES-256-CBC'));
define('SECRET_KEY', izzyEnv('SECRET_KEY'));
define('SECRET_IV', izzyEnv('SECRET_IV'));

/* =========================================================
   DETECTAR ENTORNO LOCAL
   ========================================================= */
$host = trim($_SERVER['SERVER_NAME'] ?? '');

$isLocalDomain = (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    str_ends_with($host, '.test') ||
    str_ends_with($host, '.local') ||
    str_ends_with($host, '.localhost')
);

define('ES_LOCAL', $isLocalDomain);

/* =========================================================
   MODO DEMO
   ========================================================= */
define('SISTEMA_PRUEBA', strtoupper(izzyEnv('SISTEMA_PRUEBA', 'NO')));

if (ES_LOCAL) {
    define('SISTEMA_PRUEBA_LABEL', 'MODO DESARROLLO');
} else {
    define(
        'SISTEMA_PRUEBA_LABEL',
        SISTEMA_PRUEBA === 'SI' ? 'DEMO' : ''
    );
}

/* =========================================================
   INICIALIZAR CONFIGURACIÓN
   ========================================================= */
initConfig();

function initConfig()
{
    $GLOBALS['SISTEMA_PRUEBA'] = constant('SISTEMA_PRUEBA');

    if (session_status() === PHP_SESSION_ACTIVE) {
        if (
            isset($_SESSION['db_cliente']) &&
            $_SESSION['db_cliente'] !== ''
        ) {
            $db_cliente = $_SESSION['db_cliente'];
        } else {
            $db_cliente = $GLOBALS['DB_MAIN'];
        }

        $GLOBALS['db'] = $db_cliente;
    } else {
        header('Location: ' . SERVERURL);
        exit;
    }
}
