<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

$required = [
  'nombre_proveedores' => 'Nombre del Proveedor',
  'rtn_proveedores'    => 'RTN del Proveedor',
  'fecha_proveedores'  => 'Fecha de Registro',
];

$missing = [];
foreach ($required as $key => $label) {
  if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
    $missing[] = $label;
  }
}

if (empty($missing)) {
  require_once "../controladores/proveedoresControlador.php";
  $ins = new proveedoresControlador();
  echo $ins->agregar_proveedores_controlador();
} else {
  $title   = "Error 🚨";
  $message = "Faltan los siguientes campos: ".implode(", ", $missing).".";
  $title   = addslashes($title);
  $message = addslashes($message);
  echo "<script>showNotify('error', '$title', '$message');</script>";
}