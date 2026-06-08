<?php
// core/editarGastos.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

try {
    if (!isset($_POST['egresos_id']) || $_POST['egresos_id'] === '') {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "No se recibió el ID del egreso."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $egresos_id = intval($_POST['egresos_id']);

    if ($egresos_id <= 0) {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "ID de egreso inválido."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $insMainModel->getGastosEdit($egresos_id);

    if (!$result || $result->num_rows <= 0) {
        echo json_encode([
            "success" => false,
            "title" => "Error",
            "message" => "No se encontró el egreso solicitado."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $row = $result->fetch_assoc();

    $datos = [
        "egresos_id" => isset($row['egresos_id']) ? (int)$row['egresos_id'] : $egresos_id,
        "proveedores_id" => isset($row['proveedores_id']) ? (int)$row['proveedores_id'] : 0,
        "cuentas_id" => isset($row['cuentas_id']) ? (int)$row['cuentas_id'] : 0,
        "empresa_id" => isset($row['empresa_id']) ? (int)$row['empresa_id'] : 0,
        "categoria_gastos_id" => isset($row['categoria_gastos_id']) ? (int)$row['categoria_gastos_id'] : 0,
        "fecha" => isset($row['fecha']) ? $row['fecha'] : "",
        "factura" => isset($row['factura']) ? $row['factura'] : "",
        "subtotal" => isset($row['subtotal']) ? $row['subtotal'] : "0.00",
        "impuesto" => isset($row['impuesto']) ? $row['impuesto'] : "0.00",
        "descuento" => isset($row['descuento']) ? $row['descuento'] : "0.00",
        "nc" => isset($row['nc']) ? $row['nc'] : "0.00",
        "total" => isset($row['total']) ? $row['total'] : "0.00",
        "observacion" => isset($row['observacion']) ? $row['observacion'] : "",
        "factura_pdf" => isset($row['factura_pdf']) ? $row['factura_pdf'] : "",
        "proveedor" => isset($row['proveedor']) ? $row['proveedor'] : "",
        "nombre_cuenta" => isset($row['nombre_cuenta']) ? $row['nombre_cuenta'] : "",
        "nombre_empresa" => isset($row['nombre_empresa']) ? $row['nombre_empresa'] : "",
        "categoria" => isset($row['categoria']) ? $row['categoria'] : ""
    ];

    echo json_encode([
        "success" => true,
        "data" => $datos
    ], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Exception $e) {
    error_log("Error en editarGastos.php: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "title" => "Error",
        "message" => "No se pudieron cargar los datos del egreso."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
