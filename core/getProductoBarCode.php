<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

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

$data = [
    "barcode" => $_POST['barcode'],
    "empresa_id_sd" => $_SESSION['empresa_id_sd'],
    "bodega" => ''
];

$datos = []; // Inicializamos como array vacío

$resultCantidad = $insMainModel->getProductosCantidad($data);

if($resultCantidad->num_rows > 0) {
    $row = $resultCantidad->fetch_assoc();
    
    $lote_id = isset($row['lote_id']) ? $row['lote_id'] : null;

    if ($lote_id) {
        $saldoLote = $insMainModel->getSaldoPorLote($row['productos_id'], $lote_id);
        $saldo = $saldoLote ? $saldoLote['saldo'] : 0;
    } else {
        $saldo = $insMainModel->getSaldoProductosMovimientos($row['productos_id']);
    }

    // Crear objeto con propiedades nombradas
    $datos = [
        "nombre" => $row['nombre'],
        "precio_venta" => $row['precio_venta'],
        "productos_id" => $row['productos_id'],
        "impuesto_venta" => $row['impuesto_venta'],
        "cantidad_mayoreo" => $row['cantidad_mayoreo'],    
        "precio_mayoreo" => $row['precio_mayoreo'],
        "saldo" => $saldo,
        "almacen_id" => $row['almacen_id'],
        "medida" => $row['medida'],
        "tipo_producto_id" => $row['tipo_producto_id'],
        "precio_compra" => $row['precio_compra']
    ];
}

echo json_encode($datos);