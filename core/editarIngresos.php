<?php
// core/editarIngresos.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

try {
    if (!isset($_POST['ingresos_id']) || $_POST['ingresos_id'] === '') {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "No se recibió el ID del ingreso."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ingresos_id = intval($_POST['ingresos_id']);

    if ($ingresos_id <= 0) {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "ID de ingreso inválido."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $insMainModel->getIngresosEdit($ingresos_id);

    if (!$result || $result->num_rows <= 0) {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "No se encontró el ingreso solicitado."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $valores2 = $result->fetch_assoc();

    $datos = [
        "clientes_id" => isset($valores2['clientes_id']) ? $valores2['clientes_id'] : "",
        "cuentas_id" => isset($valores2['cuentas_id']) ? $valores2['cuentas_id'] : "",
        "empresa_id" => isset($valores2['empresa_id']) ? $valores2['empresa_id'] : "",
        "fecha" => isset($valores2['fecha']) ? $valores2['fecha'] : "",
        "factura" => isset($valores2['factura']) ? $valores2['factura'] : "",
        "subtotal" => isset($valores2['subtotal']) ? $valores2['subtotal'] : "0.00",
        "impuesto" => isset($valores2['impuesto']) ? $valores2['impuesto'] : "0.00",
        "descuento" => isset($valores2['descuento']) ? $valores2['descuento'] : "0.00",
        "nc" => isset($valores2['nc']) ? $valores2['nc'] : "0.00",
        "total" => isset($valores2['total']) ? $valores2['total'] : "0.00",
        "observacion" => isset($valores2['observacion']) ? $valores2['observacion'] : "",
        "recibide" => isset($valores2['recibide']) ? $valores2['recibide'] : ""
    ];

    echo json_encode([
        "success" => true,
        "data" => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log("Error en editarIngresos.php: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "title" => "Error",
        "message" => "No se pudieron cargar los datos del ingreso."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}