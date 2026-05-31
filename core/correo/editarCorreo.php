<?php
// core/correo/editarCorreo.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "success" => false,
            "message" => $validacion['mensaje'] ?? "Sesión inválida"
        ]);
        exit;
    }
}

$correo_id = isset($_POST['correo_id']) ? (int)$_POST['correo_id'] : 0;

if ($correo_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se recibió el ID de correo."
    ]);
    exit;
}

$sql = "
    SELECT
        correo_id,
        correo_tipo_id,
        metodo_envio,
        server,
        correo,
        port,
        smtp_secure,
        tenant_id,
        client_id,
        graph_user,
        save_to_sent_items,
        estado
    FROM correo
    WHERE correo_id = '$correo_id'
    LIMIT 1
";

$resultado = $insMainModel->ejecutar_consulta_simple($sql);

if (!$resultado || $resultado->num_rows <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró la configuración de correo."
    ]);
    exit;
}

$row = $resultado->fetch_assoc();

echo json_encode([
    "success" => true,
    "correo_id" => $row['correo_id'],
    "correo_tipo_id" => $row['correo_tipo_id'],
    "metodo_envio" => $row['metodo_envio'],
    "server" => $row['server'],
    "correo" => $row['correo'],
    "port" => $row['port'],
    "smtp_secure" => $row['smtp_secure'],
    "tenant_id" => $row['tenant_id'],
    "client_id" => $row['client_id'],
    "graph_user" => $row['graph_user'],
    "save_to_sent_items" => $row['save_to_sent_items'],
    "estado" => $row['estado']
]);