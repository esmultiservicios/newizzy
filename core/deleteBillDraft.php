<?php
header("Content-Type: application/json; charset=utf-8");

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$validacion = $insMainModel->validarSesion();

if (!empty($validacion['error'])) {
    echo json_encode([
        "success" => false,
        "title" => "Error de sesión",
        "message" => $validacion['mensaje'] ?? "Sesión inválida",
        "redirect" => $validacion['redireccion'] ?? ""
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$facturas_id = isset($_POST['facturas_id']) ? (int)$_POST['facturas_id'] : 0;
$motivo = isset($_POST['motivo']) ? trim((string)$_POST['motivo']) : '';

if ($facturas_id <= 0) {
    echo json_encode([
        "success" => false,
        "title" => "Factura inválida",
        "message" => "No se recibió una factura válida."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($motivo === '' || mb_strlen($motivo, 'UTF-8') < 5) {
    echo json_encode([
        "success" => false,
        "title" => "Motivo requerido",
        "message" => "Debe escribir una razón válida para eliminar la factura borrador."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = $insMainModel->delete_bill_draft($facturas_id, $motivo);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
exit;