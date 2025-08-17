<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

$required = [
  'productos_id'   => 'ID del Producto',
  'producto'       => 'Nombre del Producto',
  'precio_compra'  => 'Precio de Compra', // si no es obligatorio, elimínala de aquí
  'precio_venta'   => 'Precio de Venta',
];

// Construye faltantes (no seteado o vacío)
$missingFields = [];
foreach ($required as $key => $label) {
  if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
    $missingFields[] = $label;
  }
}

if (empty($missingFields)) {
  require_once "../controladores/productosControlador.php";
  $insVarios = new productosControlador();
  echo $insVarios->edit_productos_controlador();
} else {
  $title = "Error 🚨";
  $message = "Faltan los siguientes campos: " . implode(", ", $missingFields) . ". Por favor, corrígelos.";
  $title = addslashes($title);
  $message = addslashes($message);
  echo "<script>showNotify('error', '$title', '$message');</script>";
}
