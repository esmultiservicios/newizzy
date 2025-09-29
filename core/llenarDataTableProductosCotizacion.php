<?php
// core/llenarDataTableProductosCotizacion.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title"   => "Error de sesión",
        "text"    => $validacion['mensaje'],
        "type"    => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$bodega = isset($_POST['bodega']) ? $_POST['bodega'] : '';

$datos = [
    "bodega"        => $bodega,
    "barcode"       => '',
    "planes_id"     => $_SESSION['planes_id'],
    "empresa_id_sd" => $_SESSION['empresa_id_sd']
];

/*
 * Importante:
 * Reutilizamos el mismo método robusto que usa facturas
 * para evitar duplicar lógica y mantener el mismo shape.
 */
$result = $insMainModel->getProductosConInventarioYServicios($datos);

$data = array();

while ($row = $result->fetch_assoc()) {
    $bodegaNombre = ($row['almacen_id'] == 0 || $row['almacen_id'] == null) ? "Sin bodega" : $row['almacen'];
    $cantidad     = ($row['cantidad'] == null || $row['cantidad'] == "") ? 0 : $row['cantidad'];

    $data[] = array(
        "productos_id"          => $row['productos_id'],
        "barCode"               => $row['barCode'],
        "nombre"                => $row['nombre'],
        "cantidad"              => $cantidad,
        "medida"                => $row['medida'],
        "tipo_producto_id"      => $row['tipo_producto_id'],
        "precio_venta"          => $row['precio_venta'],

        // Usamos la misma key que facturas para 1:1 con el JS existente
        "almacen_facturas"      => $bodegaNombre,
        "almacen_id"            => $row['almacen_id'],
        "tipo_producto"         => $row['tipo_producto'],
        "impuesto_venta"        => $row['impuesto_venta'], // 1 = grava ISV, 0 = exento
        "precio_mayoreo"        => $row['precio_mayoreo'],
        "cantidad_mayoreo"      => $row['cantidad_mayoreo'],
        "tipo_producto_nombre"  => $row['tipo_producto_nombre'],
        "isv_venta"             => $row['isv_venta'],
        "isv_compra"            => $row['isv_compra'],
        "image"                 => $row['image'],
        "id_producto_superior"  => $row['id_producto_superior'],

        // Flags de ISV (1/0) iguales a facturas
        "isv1"                  => isset($row['isv1']) ? (int)$row['isv1'] : 0,
        "isv2"                  => isset($row['isv2']) ? (int)$row['isv2'] : 0,
    );
}

$arreglo = array(
    "echo"                 => 1,
    "totalrecords"         => count($data),
    "totaldisplayrecords"  => count($data),
    "data"                 => $data
);

echo json_encode($arreglo);