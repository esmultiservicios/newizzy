<?php
// core/correo/sendCotizacion.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/sendEmail.php';

header("Content-Type: text/html;charset=utf-8");

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo $insMainModel->showNotification([
            "title" => "Error de sesión",
            "text" => $validacion['mensaje'] ?? 'Sesión inválida',
            "type" => "error",
            "funcion" => "window.location.href = '".($validacion['redireccion'] ?? '')."'"
        ]);
        exit;
    }
}

$sendEmail = new sendEmail();

date_default_timezone_set('America/Tegucigalpa');

$cotizacion_id = isset($_POST['cotizacion_id']) ? (int)$_POST['cotizacion_id'] : 0;

if ($cotizacion_id <= 0) {
    echo $insMainModel->showNotification([
        "title" => "Cotización inválida",
        "text" => "No se recibió una cotización válida.",
        "type" => "error"
    ]);
    exit;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$users_id = isset($_SESSION['users_id_sd']) ? (int)$_SESSION['users_id_sd'] : 0;

if ($empresa_id <= 0 || $users_id <= 0) {
    echo $insMainModel->showNotification([
        "title" => "Sesión inválida",
        "text" => "No se pudo identificar la empresa o el usuario de la sesión.",
        "type" => "error"
    ]);
    exit;
}

/* =========================================================
   CONSULTAR DATOS DE LA COTIZACIÓN
   ========================================================= */

$nombre = "";
$para = "";
$numero = "";
$numero_documento = "";

if (method_exists($insMainModel, 'getCotizacionCorreo')) {
    $result_cotizacion = $insMainModel->getCotizacionCorreo($cotizacion_id);

    if ($result_cotizacion && $result_cotizacion->num_rows > 0) {
        $cotizacion = $result_cotizacion->fetch_assoc();

        $nombre = isset($cotizacion['cliente']) ? trim($cotizacion['cliente']) : "";
        $para = isset($cotizacion['correo']) ? trim($cotizacion['correo']) : "";
        $numero = isset($cotizacion['numero']) ? $cotizacion['numero'] : "";

        $numero_documento = "COT-" . str_pad($numero, 8, "0", STR_PAD_LEFT);
    }
} else {
    $sqlCotizacion = "
        SELECT 
            c.cotizacion_id,
            cl.nombre AS cliente,
            cl.correo,
            c.number AS numero
        FROM cotizacion c
        INNER JOIN clientes cl ON c.clientes_id = cl.clientes_id
        WHERE c.cotizacion_id = '$cotizacion_id'
          AND c.empresa_id = '$empresa_id'
        LIMIT 1
    ";

    $result_cotizacion = $insMainModel->ejecutar_consulta_simple($sqlCotizacion);

    if ($result_cotizacion && $result_cotizacion->num_rows > 0) {
        $cotizacion = $result_cotizacion->fetch_assoc();

        $nombre = isset($cotizacion['cliente']) ? trim($cotizacion['cliente']) : "";
        $para = isset($cotizacion['correo']) ? trim($cotizacion['correo']) : "";
        $numero = isset($cotizacion['numero']) ? $cotizacion['numero'] : "";

        $numero_documento = "COT-" . str_pad($numero, 8, "0", STR_PAD_LEFT);
    }
}

if ($numero_documento == "" || $para == "") {
    echo $insMainModel->showNotification([
        "title" => "Datos incompletos",
        "text" => "No se encontró el correo del cliente o los datos de la cotización.",
        "type" => "error"
    ]);
    exit;
}

if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
    echo $insMainModel->showNotification([
        "title" => "Correo inválido",
        "text" => "El cliente no tiene un correo válido registrado.",
        "type" => "error"
    ]);
    exit;
}

/* =========================================================
   CONSULTAR NOMBRE DE LA EMPRESA
   ========================================================= */

$empresa_nombre = "";

$sqlEmpresa = "
    SELECT nombre
    FROM empresa
    WHERE empresa_id = '$empresa_id'
      AND estado = 1
    LIMIT 1
";

$resultadoEmpresa = $insMainModel->ejecutar_consulta_simple($sqlEmpresa);

if ($resultadoEmpresa && $resultadoEmpresa->num_rows > 0) {
    $rowEmpresa = $resultadoEmpresa->fetch_assoc();
    $empresa_nombre = strtoupper(trim($rowEmpresa['nombre']));
}

if ($empresa_nombre == "") {
    $empresa_nombre = "LA EMPRESA";
}

/* =========================================================
   OBTENER BASE DE DATOS ACTUAL
   ========================================================= */

$db_actual = "";

$sqlDb = "SELECT DATABASE() AS db_actual";
$resultadoDb = $insMainModel->ejecutar_consulta_simple($sqlDb);

if ($resultadoDb && $resultadoDb->num_rows > 0) {
    $rowDb = $resultadoDb->fetch_assoc();
    $db_actual = trim($rowDb['db_actual']);
}

if ($db_actual == "") {
    echo $insMainModel->showNotification([
        "title" => "Base de datos no identificada",
        "text" => "No se pudo identificar la base de datos actual para generar el enlace de la cotización.",
        "type" => "error"
    ]);
    exit;
}

/* =========================================================
   CONSTRUIR URL DEL REPORTE WINDOWS
   ========================================================= */

$demo_sistema = "NO";

if (isset($_SESSION['demo_sistema']) && trim($_SESSION['demo_sistema']) != "") {
    $demo_sistema = trim($_SESSION['demo_sistema']);
}

$parametrosReporte = [
    "id" => $cotizacion_id,
    "type" => "Cotizacion_carta_izzy",
    "db" => $db_actual,
    "demo_sistema" => $demo_sistema
];

$urlCotizacion = SERVERURLWINDOWS . '?' . http_build_query($parametrosReporte);

/* =========================================================
   DATOS DEL CORREO
   ========================================================= */

$correo_tipo_id = 3; // Se mantiene igual que facturas si tu configuración usa el mismo tipo

$destinatarios = [
    $para => $nombre
];

$bccDestinatarios = [];

$asunto = "Envío de Cotización " . $numero_documento;

/*
    Ya no se adjunta PDF.
    La cotización se envía como enlace al reporte.
*/

$mensaje = '
    <div style="padding: 20px; font-family: Arial, Helvetica, sans-serif; color: #2d3748;">
        <p style="margin-bottom: 12px;">
            ¡Hola '.$nombre.'!
        </p>
        
        <p style="margin-bottom: 12px;">
            Espero que esté teniendo un excelente día. Queremos comunicarle que su cotización 
            <b>'.$numero_documento.'</b> ya se encuentra disponible para revisión.
        </p>

        <p style="margin-bottom: 12px;">
            Puede visualizarla directamente desde el siguiente enlace:
        </p>

        <div style="text-align: center; margin: 25px 0;">
            <a href="'.$urlCotizacion.'" target="_blank"
                style="
                    display: inline-block;
                    background: #0d6efd;
                    color: #ffffff;
                    padding: 13px 24px;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    font-size: 15px;
                ">
                Ver Cotización '.$numero_documento.'
            </a>
        </div>

        <p style="margin-bottom: 12px;">
            Si el botón anterior no abre correctamente, también puede copiar y pegar este enlace en su navegador:
        </p>

        <p style="
            background: #f4f6f8;
            padding: 12px;
            border-radius: 8px;
            word-break: break-all;
            font-size: 13px;
        ">
            <a href="'.$urlCotizacion.'" target="_blank" style="color: #0d6efd;">
                '.$urlCotizacion.'
            </a>
        </p>

        <p style="margin-bottom: 12px;">
            Si requiere aclaraciones adicionales respecto a la cotización o cualquier otra consulta, no dude en contactarnos.
            Estamos aquí para brindarle el apoyo necesario.
        </p>
        
        <p style="margin-bottom: 12px;">
            Agradecemos enormemente su confianza en '.$empresa_nombre.'.
        </p>
        
        <p style="margin-bottom: 12px;">
            Saludos cordiales,
        </p>
        
        <p>
            <b>El Equipo de '.$empresa_nombre.'</b>
        </p>                
    </div>
';

$archivos_adjuntos = [];

$resultadoEnvio = $sendEmail->enviarCorreo(
    $destinatarios,
    $bccDestinatarios,
    $asunto,
    $mensaje,
    $correo_tipo_id,
    $empresa_id,
    $archivos_adjuntos
);

echo $resultadoEnvio;