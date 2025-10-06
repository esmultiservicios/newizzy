<?php
// ajax/modificarNominaAjax.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

require_once "../core/configGenerales.php";

$required = ['nomina_id' => 'Número de nómina'];
$missing  = [];
foreach ($required as $k => $label) {
  if (!isset($_POST[$k]) || trim($_POST[$k]) === '') $missing[] = $label;
}

if (!empty($missing)) {
  echo json_encode([
    'status'  => 'error',
    'title'   => 'Error 🚨',
    'message' => 'Faltan los siguientes campos: '.implode(', ', $missing).'.'
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

require_once "../controladores/nominaControlador.php";

// Soporta nominaControlador o NominaControlador
$ctrlClass = class_exists('nominaControlador') ? 'nominaControlador' : (class_exists('NominaControlador') ? 'NominaControlador' : null);
if (!$ctrlClass) {
  echo json_encode([
    'status'  => 'error',
    'title'   => 'Clase no definida',
    'message' => "No existe la clase 'nominaControlador' (ni 'NominaControlador')."
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

$ins = new $ctrlClass();

// Captura cualquier echo/HTML/Scripts del controlador
ob_start();
$res     = $ins->edit_nomina_controlador();
$garbage = trim(ob_get_clean());

// Normaliza SIEMPRE a JSON
if (is_array($res)) {
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
} else {
  $s = trim((string)$res);
  if ($s !== '' && ($s[0] === '{' || $s[0] === '[')) {
    echo $s; // ya es JSON
  } else if ($garbage !== '') {
    // Caso típico: HTML del login por sesión expirada
    echo json_encode([
      'status'  => 'unauthorized',
      'title'   => 'Sesión expirada',
      'message' => 'Debes iniciar sesión nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
  } else {
    // fallback: sin salida → asumimos éxito
    echo json_encode([
      'status'  => 'success',
      'title'   => 'Actualizada',
      'message' => 'La nómina fue actualizada correctamente.'
    ], JSON_UNESCAPED_UNICODE);
  }
}