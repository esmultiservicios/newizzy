<?php
// core/inventario/llenarDataTableInventarioTransferencia.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "echo" => 1,
            "totalrecords" => 0,
            "totaldisplayrecords" => 0,
            "data" => [],
            "success" => false,
            "message" => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
        exit;
    }
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

$categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : 0;
$bodega = isset($_POST['bodega']) ? (int)$_POST['bodega'] : 0;
$productos_id = isset($_POST['productos_id']) ? (int)$_POST['productos_id'] : 0;

if ($empresa_id <= 0) {
    echo json_encode([
        "echo" => 1,
        "totalrecords" => 0,
        "totaldisplayrecords" => 0,
        "data" => [],
        "success" => false,
        "message" => "No se pudo identificar la empresa de la sesión."
    ]);
    exit;
}

$datos = [
    "categoria_id"  => $categoria_id,
    "bodega"        => $bodega,
    "productos_id"  => $productos_id,
    "empresa_id_sd" => $empresa_id
];

$result = $insMainModel->getTranferenciaProductos($datos);

$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $bodega_nombre = ((int)$row['almacen_id'] === 0 || $row['almacen_id'] === null) ? "Sin bodega" : $row['bodega'];

        $data[] = [
            "fecha_registro" => $row['fecha_registro'],
            "barCode"        => $row['barCode'],
            "producto"       => $row['producto'],
            "medida"         => $row['medida'],
            "movimientos_id" => $row['movimientos_id'],
            "entrada"        => (float)$row['entrada'],
            "salida"         => (float)$row['salida'],
            "saldo"          => (float)$row['saldo'],
            "saldo_anterior" => (float)$row['saldo_anterior'],
            "bodega"         => $bodega_nombre,
            "id_bodega"      => (int)$row['almacen_id'],
            "productos_id"   => (int)$row['productos_id'],
            "superior"       => isset($row['id_producto_superior']) ? (int)$row['id_producto_superior'] : 0,
            "image"          => $row['image'],
            "numero_lote"    => $row['numero_lote'],
            "empresa_id"     => (int)$row['empresa_id'],
            "lote_id"        => (int)$row['lote_id'],
            "precio_venta"   => isset($row['precio_venta']) ? (float)$row['precio_venta'] : 0,
            "precio_compra"  => isset($row['precio_compra']) ? (float)$row['precio_compra'] : 0,
            "categoria"      => isset($row['categoria']) ? $row['categoria'] : 'Sin categoría'
        ];
    }
}

echo json_encode([
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "success" => true,
    "data" => $data
]);