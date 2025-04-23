<?php
header("Content-Type: application/json;charset=utf-8");

$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

date_default_timezone_set('America/Tegucigalpa');
$id = $_POST['id'];
$estado = $_POST['estado'];

$query = $insMainModel->updateImpresora($id, $estado);

$response = [
    "success" => $query,
    "message" => $query ? "La operación se realizó con éxito." : "No se pudo realizar la operación, comuníquese con el administrador."
];

echo json_encode($response);