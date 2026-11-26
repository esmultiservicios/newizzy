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
if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

if (!isset($_SESSION['user_sd'])) {
    $_SESSION['user_sd'] = null;
}

/* =========================================================
   CARGA DEL .ENV
   ========================================================= */
require_once __DIR__ . '/izzyEnvironment.php';

$hostActual = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
$esDemo = str_starts_with(strtolower($hostActual), 'demo.');
$envFile = izzyPrivateEnvFile(izzyResolveEnvFile($hostActual));
$envValues = izzyLoadEnvFile($envFile);
$missing = izzyMissingKeys($envValues, izzyRequiredKeys());
if ($missing) {
    throw new RuntimeException('Faltan variables base de IZZY: ' . implode(', ', $missing) . '.');
}
define('IZZY_ACTIVE_ENV_FILE', $envFile);

foreach ($envValues as $key => $value) {
    // El archivo seleccionado prevalece para las variables de la aplicación.
    if ($key === 'IZZY_ENV_FILE') continue;
    $_ENV[$key] = $value;
    putenv($key . '=' . $value);
}
unset($envValues, $key, $value);

/* =========================================================
   DATOS DE CONEXIÓN DEL CLIENTE
   ========================================================= */
define('SERVER', izzyEnv('SERVER'));
define('USER', izzyEnv('USER'));
define('PASS', izzyEnv('PASS'));

/* =========================================================
   DATOS DE CONEXIÓN SERVIDOR PRINCIPAL
   ========================================================= */
define('SERVER_MAIN', izzyEnv('SERVER_MAIN', SERVER));
define('DB_MAIN', izzyEnv('DB_MAIN'));
define('DB_PRUEBA', izzyOptionalEnv('DB_PRUEBA'));

/* =========================================================
   CPANEL
   ========================================================= */
define('CPANEL_TOKEN', izzyOptionalEnv('CPANEL_TOKEN'));
define('CPANEL_USERNAME', izzyOptionalEnv('CPANEL_USERNAME'));
define('CPANEL_HOST', izzyOptionalEnv('CPANEL_HOST'));
define('CPANEL_PORT', izzyEnv('CPANEL_PORT', '2083'));
// El token provisiona la base. La aplicación y el importador se conectan por MySQL.
define('CPANEL_DB_USERNAME', USER);
define('CPANEL_DB_PASSWORD', PASS);
define('CPANEL_DOMINIO', izzyOptionalEnv('CPANEL_DOMINIO'));

define('IZZY_DOCUMENT_AUTO_EMAIL', izzyEnvBool('IZZY_DOCUMENT_AUTO_EMAIL', true));

/* =========================================================
   WHM
   ========================================================= */
define('WHM_HOST', izzyOptionalEnv('WHM_HOST'));
define('WHM_PORT', (int) izzyEnv('WHM_PORT', '2087'));
define('WHM_USERNAME', izzyOptionalEnv('WHM_USERNAME'));
define('WHM_TOKEN', izzyOptionalEnv('WHM_TOKEN'));
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
define('DB_PREFIX', izzyOptionalEnv('DB_PREFIX') ?? CPANEL_USERNAME);
define('DB_MAX_LENGTH', (int) izzyEnv('DB_MAX_LENGTH', '10'));

/* =========================================================
   CONFIGURACIÓN DE SEGURIDAD
   ========================================================= */
define('API_TIMEOUT', (int) izzyEnv('API_TIMEOUT', '60'));

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
   ENTORNO / MODO DEMO
   ---------------------------------------------------------
   La URL determina automáticamente el entorno visible y la
   variable histórica SISTEMA_PRUEBA utilizada por otros módulos.

   LOCAL:
     localhost, 127.0.0.1, *.test, *.local, *.localhost
     -> SISTEMA_PRUEBA = NO
     -> etiqueta: MODO DESARROLLO

   DEMO:
     demo.izzycloud.app o cualquier host demo.*
     -> SISTEMA_PRUEBA = SI
     -> etiqueta: DEMO

   PRODUCCIÓN:
     cualquier otro host, incluido sistema.izzycloud.app
     -> SISTEMA_PRUEBA = NO
     -> sin etiqueta de entorno
   ========================================================= */
define('SISTEMA_PRUEBA', $esDemo ? 'SI' : 'NO');
define(
    'IZZY_ENTORNO',
    ES_LOCAL ? 'LOCAL' : (SISTEMA_PRUEBA === 'SI' ? 'DEMO' : 'PRODUCCION')
);

if (ES_LOCAL) {
    define('SISTEMA_PRUEBA_LABEL', 'MODO DESARROLLO');
} elseif (SISTEMA_PRUEBA === 'SI') {
    define('SISTEMA_PRUEBA_LABEL', 'DEMO');
} else {
    define('SISTEMA_PRUEBA_LABEL', '');
}

/* =========================================================
   INICIALIZAR CONFIGURACIÓN
   ========================================================= */
initConfig();

function initConfig()
{
    $GLOBALS['SISTEMA_PRUEBA'] = constant('SISTEMA_PRUEBA');

    if (PHP_SAPI === 'cli') {
        $GLOBALS['db'] = DB_MAIN;
        return;
    }

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