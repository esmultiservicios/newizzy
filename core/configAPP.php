<?php
/*
 * Parametros de conexiÃ³n a la DB
 */

// Reemplaza esto:
if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

// Por esto:
if (!isset($_SESSION['user_sd'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start(['name' => 'SD']);
    }
    // Si la sesiÃ³n estÃ¡ activa pero no tiene 'user_sd'
    $_SESSION['user_sd'] = null; // O el valor inicial que necesites
}

// DATOS DE CONEXION DEL CLIENTE
define('SERVER', 'localhost');
define('USER', 'esmultiservicios_root');
define('PASS', 'o8lXA0gtIO$@');

// DATOS DE CONEXION SERVIDOR PRINCIPAL
define('SERVER_MAIN', 'localhost');
define('DB_MAIN', 'esmultiservicios_izzy');
define('DB_PRUEBA', 'esmultiservicios_dev_izzy');

// Datos conexion root MySQL user
define('MYSQL_USER', 'root');
define('MYSQL_PASS', 'a$Exv*Smj?w7|DP');

// ConfiguraciÃ³n para Microsoft 365 OAuth
define('OAUTH_CLIENT_ID', 'tu-client-id-de-azure-ad');
define('OAUTH_CLIENT_SECRET', 'tu-client-secret');
define('OAUTH_TENANT_ID', 'tu-tenant-id');
define('OAUTH_REDIRECT_URI', 'https://tudominio.com/oauth_callback.php');
define('OAUTH_AUTHORITY', 'https://login.microsoftonline.com/' . OAUTH_TENANT_ID);
define('OAUTH_AUTHORIZE_ENDPOINT', OAUTH_AUTHORITY . '/oauth2/v2.0/authorize');
define('OAUTH_TOKEN_ENDPOINT', OAUTH_AUTHORITY . '/oauth2/v2.0/token');
define('OAUTH_RESOURCE', 'https://outlook.office365.com');

// cPanel
define('CPANEL_TOKEN', 'YDBIN7O9JZMUWZU8JRWZZORJZL6GHZS7');
define('CPANEL_USERNAME', 'esmultiservicios');
define('CPANEL_PASSWORD', 'CEdwin82003%*');
define('CPANEL_HOST', 'esmultiservicios.com');
define('CPANEL_PORT', '2083');
define('CPANEL_DB_USERNAME', USER);
define('CPANEL_DB_PASSWORD', PASS);
define('CPANEL_DOMINIO', "izzycloud.app");
;  

//WHM
define('WHM_HOST', 'tu.servidor.whm');  // Ej: server.midominio.com
define('WHM_PORT', 2087);               // Puerto WHM (2086 para SSL, 2087 para no SSL)
define('WHM_USERNAME', 'root');         // Usuario WHM (normalmente root)
define('WHM_TOKEN', 'CTON6YOX1L4U50RAV7HT8EX10A94RAZS'); // Token de acceso WHM (opcional, o usa password)
define('WHM_TIMEOUT', 30);              // Timeout para conexiones WHM

//API CAMBIO DOLAR
define('WEB_SCRAPING_DOLARES', "https://www.bancopromerica.com/banca-de-empresas/banca-internacional/mesa-de-cambio/");

// ConfiguraciÃ³n para nombres de base de datos
define('DB_PREFIX', CPANEL_USERNAME);
define('DB_MAX_LENGTH', 10); // Longitud mÃ¡xima para el identificador Ãºnico

// ConfiguraciÃ³n de seguridad
define('API_TIMEOUT', 60);
define('SSL_VERIFICATION', false); // true en producciÃ³n

$GLOBALS['DB_MAIN'] = DB_MAIN;

// BASE DE DATOS EXCEPTION LOGIN CONTROLADOR
const DB_MAIN_LOGIN_CONTROLADOR = DB_MAIN;  // LA BASE DE DATOS QUE ESTE AQUÃ SE EXCEPTÃšA EN EL LOGIN CONTROLADOR

/*
 * Para encrptar y Desencriptar
 * Nota: Estos valores no se deben cambiar, si hay datos en la DB
 */
define('METHOD', "AES-256-CBC");
define('SECRET_KEY', '$DP_@2020');
define('SECRET_IV', '10172');

// 1. Detectar entorno local (manera más confiable)
$host = trim($_SERVER['SERVER_NAME']); // Elimina espacios ocultos
$isLocalDomain = (
    $host === 'localhost' ||
    $host === '127.0.0.1' ||
    str_ends_with($host, '.test') ||    // PHP 8+ (recomendado)
    str_ends_with($host, '.local') ||
    str_ends_with($host, '.localhost')
);
// Si no tienes PHP 8, usa esto:
// $isLocalDomain = $isLocalDomain || (strpos($host, '.test') !== false);

define('ES_LOCAL', $isLocalDomain);

// 2. Definir modo demo (solo aplica en producción)
define('SISTEMA_PRUEBA', 'NO'); // Cambia a 'SI' o 'NO'

// 3. Asignar etiqueta (prioridad absoluta a entorno local)
if (ES_LOCAL) {
    define('SISTEMA_PRUEBA_LABEL', 'MODO DESARROLLO'); // Siempre en local
} else {
    define('SISTEMA_PRUEBA_LABEL', (SISTEMA_PRUEBA === 'SI') ? 'DEMO' : '');
}

initConfig();  // Llamar a la funciÃ³n para inicializar la configuraciÃ³n

function initConfig()
{
    $GLOBALS['SISTEMA_PRUEBA'] = constant('SISTEMA_PRUEBA'); // ✅ Añade esto
    // Verificar si la sesiÃ³n estÃ¡ activa y no ha expirado
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Verificar si $_SESSION['db_cliente'] estÃ¡ definido y no estÃ¡ vacÃ­o
        if (isset($_SESSION['db_cliente']) && $_SESSION['db_cliente'] !== '') {
            $db_cliente = $_SESSION['db_cliente'];
        } else {
            $db_cliente = $GLOBALS['DB_MAIN'];  // Valor predeterminado si $_SESSION['db_cliente'] no estÃ¡ definido o estÃ¡ vacÃ­o
        }

        // DATOS DE CONEXIÃ“N DEL CLIENTE
        $GLOBALS['db'] = $db_cliente;
    } else {
        // La sesiÃ³n ha expirado, puedes manejar esto de alguna manera, por ejemplo, redirigiendo al usuario a una pÃ¡gina de inicio de sesiÃ³n.
        // AquÃ­ puedes decidir quÃ© hacer en caso de sesiÃ³n expirada.
        // Por ejemplo, puedes redirigir al usuario a una pÃ¡gina de inicio de sesiÃ³n.
        header('Location: ' . SERVERURL);
        exit;
    }
}
