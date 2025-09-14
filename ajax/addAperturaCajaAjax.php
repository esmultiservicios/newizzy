<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

$required = [
  'colaboradores_id_apertura' => 'ID del colaborador'
];

$missing = [];
foreach ($required as $key => $label) {
  if (!isset($_POST[$key]) || trim($_POST[$key]) === '') {
    $missing[] = $label;
  }
}

if (empty($missing)) {
  require_once "../controladores/aperturaCajaControlador.php";
  $ins = new aperturaCajaControlador();
  echo $ins->agregar_apertura_caja_controlador();
} else {
  $title   = "Error 🚨";
  $message = "Faltan los siguientes campos: " . implode(", ", $missing) . ".";

  // Escapar por si hay comillas
  $title   = addslashes($title);
  $message = addslashes($message);

  // Si tu flujo NO es AJAX, esto funcionará tal cual.
  // Si lo llamas por AJAX, es mejor devolver JSON y manejarlo en JS.
  echo "<script>
    (function(){
      if (typeof showNotify === 'function') {
        showNotify('error', '$title', '$message', false);
      } else {
        alert('$message');
      }
    })();
  </script>";
}