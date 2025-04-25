<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$usuarios_id  = $_POST['users_id']; // Asegúrate que coincida con el nombre en el JS
$result = $insMainModel->getUsersEdit($usuarios_id);
$valores2 = $result->fetch_assoc();

if ($valores2) {
    $data = array(
        "colaboradores_id" => $valores2['colaborador_id'],
        "nombre_completo" => $valores2['colaborador'],
        "correo" => $valores2['correo'],
        "empresa_id" => $valores2['empresa_id'],
        "tipo_user_id" => $valores2['tipo_user_id'],
        "estado" => $valores2['estado'],
        "privilegio_id" => $valores2['privilegio_id'],
        "estado_colaborador" => $valores2['estado'], // Si usas esto para badge de estado
        "server_customers_id" => $valores2['server_customers_id'],
        "telefono" => $valores2['telefono'] ?? null,
        "identidad" => $valores2['identidad'] ?? null,
        "fecha_ingreso" => $valores2['fecha_ingreso'] ?? null
    );

    echo json_encode([
        "success" => true,
        "data" => $data
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "No se encontró el usuario"
    ]);
}
