<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

$required = [
  'nombre_clientes'    => 'Nombre del cliente',
  'identidad_clientes' => 'Identidad/RTN',
  'fecha_clientes'     => 'Fecha de registro'
];

$missing = [];
foreach ($required as $key => $label) {
  if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
    $missing[] = $label;
  }
}

if (empty($missing)) {
  require_once "../controladores/clientesControlador.php";
  $ins = new clientesControlador();
  echo $ins->agregar_clientes_controlador();
} else {
  $title   = "Error 🚨";
  $message = "Faltan los siguientes campos: ".implode(", ", $missing).".";
  $title   = addslashes($title);
  $message = addslashes($message);
  echo "<script>showNotify('error', '$title', '$message');</script>";
}