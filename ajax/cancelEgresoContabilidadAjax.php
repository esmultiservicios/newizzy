<?php
// ajax/cancelEgresoContabilidadAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";

/**
 * Campos requeridos para poder anular un egreso.
 * (El resto de campos también viajan y el controlador los usa, pero aquí
 * validamos lo mínimo indispensable para no bloquear la acción.)
 */
$required = [
  'egresos_id'        => 'ID del egreso',
  'proveedor_egresos' => 'Proveedor',
  'cuenta_egresos'    => 'Cuenta',
  'fecha_egresos'     => 'Fecha',
  'total_egresos'     => 'Total',
];

// Construir lista de faltantes (no seteado o vacío)
$missingFields = [];
foreach ($required as $key => $label) {
  if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
    $missingFields[] = $label;
  }
}

if (empty($missingFields)) {
  // Todo OK: pasamos al controlador de egresos para anular
  require_once "../controladores/egresosContabilidadControlador.php";
  $insEgresos = new egresosContabilidadControlador();
  echo $insEgresos->cancel_egresos_contabilidad_controlador();
} else {
  // Faltan campos: devolvemos notificación amigable
  $title = "Error 🚨";
  $message = "Faltan los siguientes campos: " . implode(", ", $missingFields) . ". Por favor, complétalos.";
  // Escapar para imprimir dentro de JS
  $title = addslashes($title);
  $message = addslashes($message);
  echo "<script>showNotify('error', '$title', '$message');</script>";
}