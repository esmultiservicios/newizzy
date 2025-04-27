<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

// 1. Validar campos obligatorios PRIMERO
$missingFields = [];
if (!isset($_POST['medida'])) $missingFields[] = "Medida";
if (!isset($_POST['producto'])) $missingFields[] = "Producto";
if (!isset($_POST['precio_venta'])) $missingFields[] = "Precio de Venta";

if (!empty($missingFields)) {
    $missingText = implode(", ", $missingFields);
    echo "<script>showNotify('error', 'Error 🚨', 'Faltan: $missingText');</script>";
    exit; // ← ¡IMPORTANTE! Termina la ejecución aquí
}

// 2. Si todo está OK, procesar
require_once "../controladores/productosControlador.php";
$insVarios = new ProductosControlador();
echo $insVarios->agregar_productos_controlador();