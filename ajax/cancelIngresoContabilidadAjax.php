<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

$required = [
  'ingresos_id'      => 'ID del Ingreso',
  'cuenta_ingresos'  => 'Cuenta Contable',
  'fecha_ingresos'   => 'Fecha',
  'total_ingresos'   => 'Total',
];

$missing = [];
foreach ($required as $k => $label) {
  if (!isset($_POST[$k]) || trim($_POST[$k]) === '') {
    $missing[] = $label;
  }
}

if (empty($missing)) {
  require_once "../controladores/ingresosContabilidadControlador.php";
  $ins = new ingresosContabilidadControlador();
  echo $ins->cancel_ingresos_contabilidad_controlador();
} else {
  $title = "Error 🚨";
  $msg = "Faltan los siguientes campos: " . implode(", ", $missing) . ". Por favor, corrígelos.";
  $title = addslashes($title);
  $msg = addslashes($msg);
  echo "<script>showNotify('error', '$title', '$msg');</script>";
}