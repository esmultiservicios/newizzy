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

$datos = [
    "correo_id" => isset($_POST['correo_id']) ? (int)$_POST['correo_id'] : 0,

    "metodo_envio" => isset($_POST['metodoEnvioConfEmail']) ? trim($_POST['metodoEnvioConfEmail']) : "SMTP",

    "server" => isset($_POST['serverConfEmail']) ? trim($_POST['serverConfEmail']) : "",
    "correo" => isset($_POST['correoConfEmail']) ? trim($_POST['correoConfEmail']) : "",
    "password" => isset($_POST['passConfEmail']) ? trim($_POST['passConfEmail']) : "",
    "port" => isset($_POST['puertoConfEmail']) ? (int)$_POST['puertoConfEmail'] : 587,
    "smtp_secure" => isset($_POST['smtpSecureConfEmail']) ? trim($_POST['smtpSecureConfEmail']) : "tls",

    "tenant_id" => isset($_POST['tenantIdConfEmail']) ? trim($_POST['tenantIdConfEmail']) : "",
    "client_id" => isset($_POST['clientIdConfEmail']) ? trim($_POST['clientIdConfEmail']) : "",
    "client_secret" => isset($_POST['clientSecretConfEmail']) ? trim($_POST['clientSecretConfEmail']) : "",
    "graph_user" => isset($_POST['graphUserConfEmail']) ? trim($_POST['graphUserConfEmail']) : "",
    "save_to_sent_items" => isset($_POST['saveToSentItemsConfEmail']) ? (int)$_POST['saveToSentItemsConfEmail'] : 1,

    "empresa_id" => isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 1
];

$sendEmail = new sendEmail();

echo $sendEmail->testingConfiguracion($datos);