<?php
// core/correo/testEmail.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/sendEmail.php';

header('Content-Type: text/html; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo "Error de sesión: " . ($validacion['mensaje'] ?? 'Sesión inválida');
        exit;
    }
}

function postCorreoValor($principal, $alternativo = '', $default = '') {
    if ($principal !== '' && isset($_POST[$principal])) {
        return trim((string)$_POST[$principal]);
    }

    if ($alternativo !== '' && isset($_POST[$alternativo])) {
        return trim((string)$_POST[$alternativo]);
    }

    return $default;
}

$metodoEnvio = postCorreoValor('metodoEnvioConfEmail', 'metodo_envio', 'SMTP');
$metodoEnvio = strtoupper(trim($metodoEnvio));

if ($metodoEnvio !== 'SMTP' && $metodoEnvio !== 'GRAPH') {
    echo "Método de envío no válido.";
    exit;
}

$datos = [
    "correo_id" => isset($_POST['correo_id']) ? (int)$_POST['correo_id'] : 0,

    "metodo_envio" => $metodoEnvio,

    "server" => postCorreoValor('serverConfEmail', 'server', ''),
    "correo" => postCorreoValor('correoConfEmail', 'correo', ''),
    "password" => postCorreoValor('passConfEmail', 'password', ''),
    "port" => (int)postCorreoValor('puertoConfEmail', 'port', '587'),
    "smtp_secure" => postCorreoValor('smtpSecureConfEmail', 'smtp_secure', 'tls'),

    "tenant_id" => postCorreoValor('tenantIdConfEmail', 'tenant_id', ''),
    "client_id" => postCorreoValor('clientIdConfEmail', 'client_id', ''),
    "client_secret" => postCorreoValor('clientSecretConfEmail', 'client_secret', ''),
    "graph_user" => postCorreoValor('graphUserConfEmail', 'graph_user', ''),
    "save_to_sent_items" => (int)postCorreoValor('saveToSentItemsConfEmail', 'save_to_sent_items', '1'),

    "empresa_id" => isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 1
];

$sendEmail = new sendEmail();

echo $sendEmail->testingConfiguracion($datos);