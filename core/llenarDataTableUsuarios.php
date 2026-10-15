<?php
// llenarDataTableUsuarios.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";
require_once __DIR__ . "/registro/Database.php";

$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    echo json_encode([
        "success" => false,
        "message" => $validacion['mensaje'],
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$estado = (isset($_POST['estado']) && $_POST['estado'] !== '')
    ? $_POST['estado']
    : 1;

$datos = [
    "privilegio_id" => $_SESSION['privilegio_sd'],
    "colaborador_id" => $_SESSION['colaborador_id_sd'],
    "empresa_id" => $_SESSION['empresa_id_sd'],
    "db_cliente" => $_SESSION['db_cliente'],
    "estado" => $estado
];

/*
 * IMPORTANTE:
 * Se conserva getUsuarios() exactamente como fuente de datos del sistema.
 * No se abre una conexión adicional desde este endpoint porque mainModel::connection()
 * no debe invocarse desde fuera de la clase.
 */
$result = $insMainModel->getUsuarios($datos);

$data = [];

foreach ($result as $row) {
    $data[] = [
        "users_id" => $row['users_id'],
        "colaborador" => $row['colaborador'],
        "correo" => $row['correo'],
        "tipo_usuario" => $row['tipo_usuario'],
        "privilegio" => $row['privilegio'],
        "empresa" => isset($row['empresa']) ? $row['empresa'] : '',
        // El modelo devuelve la empresa real del usuario; no inventar la empresa de sesión.
        "empresa_id" => (int)($row['empresa_id'] ?? 0),
        "server_customers_id" => $row['server_customers_id'],
        "estado" => $row['estado']
    ];
}

echo json_encode([
    "success" => true,
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
], JSON_UNESCAPED_UNICODE);
