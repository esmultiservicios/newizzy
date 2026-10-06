<?php
//configGenerales.php
// Redirigir a HTTPS si no está en HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('Location: ' . $redirectURL);
    exit;
}

// Obtener el protocolo (http o https)
$protocol = 'https://';  // Forzar siempre HTTPS

// Obtener el nombre del servidor
$serverName = $_SERVER['SERVER_NAME'];

// Obtener el puerto si no es el puerto estándar
$port = ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') ? ':' . $_SERVER['SERVER_PORT'] : '';

// Obtener la ruta base
$basePath = $serverName == 'localhost' ? '/devizzy/' : '/';

// Construir la URL base
$baseURL = $protocol . $serverName . $port . $basePath;
define('SERVERURL', $baseURL);

// Construir la URL de Windows según el entorno.
// LOCAL (.test, .local, .localhost, localhost o 127.0.0.1) usa el servicio local.
// DEMO y PRODUCCIÓN usan el servicio publicado.
$hostActual = strtolower(
    preg_replace(
        '/:\d+$/',
        '',
        (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '')
    )
);

$esEntornoLocal = (
    $hostActual === 'localhost' ||
    $hostActual === '127.0.0.1' ||
    str_ends_with($hostActual, '.test') ||
    str_ends_with($hostActual, '.local') ||
    str_ends_with($hostActual, '.localhost')
);

$urlWindows = $esEntornoLocal
    ? 'http://localhost:58197/esmultiservicios.aspx'
    : 'https://wi.fastsolutionhn.com/Rpt/esmultiservicios.aspx';

define('SERVERURLWINDOWS', $urlWindows);

$urlLogo = "https://wi.fastsolutionhn.com/files/";
define('SERVERURLLOGO', $urlLogo);

// Otras constantes
define('PRODUCT_PATH', '/vistas/plantilla/img/products/');
define('ENTERPRISE_PATH', '/vistas/plantilla/img/enterprise/');
define('COMPANY', 'IZZY :: ES MULTISERVICIOS');

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');