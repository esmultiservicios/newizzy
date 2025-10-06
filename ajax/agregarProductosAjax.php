<?php
//agregarProductosAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";
//var_dump($_POST);

// 1. Validar campos obligatorios PRIMERO
$missingFields = [];

if (!isset($_POST['medida']) || $_POST['medida'] === '') {
    $missingFields[] = "Medida";
}

if (!isset($_POST['producto']) || $_POST['producto'] === '') {
    $missingFields[] = "Producto";
}

if (!isset($_POST['precio_venta']) || $_POST['precio_venta'] === '') {
    $missingFields[] = "Precio de Venta";
}

if (!empty($missingFields)) {
    $missingText = implode(", ", $missingFields);
    echo "<script>showNotify('error', 'Error 🚨', 'Faltan: $missingText');</script>";
    exit;
}

// 2. Si todo está OK, procesar
require_once "../controladores/productosControlador.php";
$insVarios = new ProductosControlador();
echo $insVarios->agregar_productos_controlador();