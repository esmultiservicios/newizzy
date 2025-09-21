<?php
// core/getProductoBarCode.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    echo json_encode([
        "error"   => true,
        "mensaje" => $validacion['mensaje']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = [
    "barcode"       => $_POST['barcode'] ?? '',
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
    "bodega"        => ''
];

$datos = []; // respuesta

$resultCantidad = $insMainModel->getProductosCantidad($data);
if ($resultCantidad && $resultCantidad->num_rows > 0) {
    $row = $resultCantidad->fetch_assoc();

    // ===== Saldo (por lote o global) =====
    $lote_id = isset($row['lote_id']) ? $row['lote_id'] : null;
    if ($lote_id) {
        $saldoLote = $insMainModel->getSaldoPorLote($row['productos_id'], $lote_id);
        $saldo = $saldoLote ? (float)$saldoLote['saldo'] : 0.0;
    } else {
        // OJO: esta función devuelve un result, hay que fetch_assoc
        $rsSaldo = $insMainModel->getSaldoProductosMovimientos($row['productos_id']);
        if ($rsSaldo && $rsSaldo->num_rows > 0) {
            $saldoRow = $rsSaldo->fetch_assoc();
            $saldo = isset($saldoRow['saldo']) ? (float)$saldoRow['saldo'] : 0.0;
        } else {
            $saldo = 0.0;
        }
    }

    // Flags del producto
    $impuesto_venta = isset($row['impuesto_venta']) ? (int)$row['impuesto_venta'] : 0;
    $prod_isv1      = isset($row['isv1']) ? (int)$row['isv1'] : 0; // 1 si aplica ISV id=1
    $prod_isv2      = isset($row['isv2']) ? (int)$row['isv2'] : 0; // 1 si aplica ISV id=2

    // Porcentajes actuales (tabla isv)
    $valor_isv1 = 0.0; // id=1 (ej. 15.00)
    $valor_isv2 = 0.0; // id=2 (ej. 18.00)
    $db = $insMainModel->connection();
    if ($db) {
        if ($rs1 = $db->query("SELECT valor FROM isv WHERE isv_id = 1 AND activar = 1 LIMIT 1")) {
            if ($rs1->num_rows > 0) $valor_isv1 = (float)$rs1->fetch_assoc()['valor'];
            $rs1->free();
        }
        if ($rs2 = $db->query("SELECT valor FROM isv WHERE isv_id = 2 AND activar = 1 LIMIT 1")) {
            if ($rs2->num_rows > 0) $valor_isv2 = (float)$rs2->fetch_assoc()['valor'];
            $rs2->free();
        }
    }

    // Si el producto no grava o el flag está en 0 → % = 0
    $porc_isv_id1 = ($impuesto_venta === 1 && $prod_isv1 === 1) ? $valor_isv1 : 0.0;
    $porc_isv_id2 = ($impuesto_venta === 1 && $prod_isv2 === 1) ? $valor_isv2 : 0.0;

    $datos = [
        "nombre"            => $row['nombre'],
        "precio_venta"      => $row['precio_venta'],
        "productos_id"      => $row['productos_id'],
        "impuesto_venta"    => $impuesto_venta,     // 1 = grava, 0 = exento
        "isv1"              => $prod_isv1,          // flag producto
        "isv2"              => $prod_isv2,          // flag producto
        "valor_isv"         => $porc_isv_id1,       // % id=1 (ej. 15.00) o 0
        "valor_isv1"        => $porc_isv_id2,       // % id=2 (ej. 18.00) o 0
        "cantidad_mayoreo"  => $row['cantidad_mayoreo'],
        "precio_mayoreo"    => $row['precio_mayoreo'],
        "saldo"             => $saldo,
        "almacen_id"        => $row['almacen_id'],
        "medida"            => $row['medida'],
        "tipo_producto_id"  => $row['tipo_producto_id'],
        "precio_compra"     => $row['precio_compra']
    ];
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);