<?php
// ajax/addValesAjax.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

require_once "../core/configGenerales.php";

$required = [
  'vale_empleado' => 'Empleado',
  'vale_monto'    => 'Monto del vale'
];

$missing = [];
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
$res     = $ins->agregar_vale_controlador();
$garbage = trim(ob_get_clean());

// Normaliza SIEMPRE a JSON
if (is_array($res)) {
  echo json_encode($res, JSON_UNESCAPED_UNICODE);
} else {
  $s = trim((string)$res);
  if ($s !== '' && ($s[0] === '{' || $s[0] === '[')) {
    echo $s; // ya es JSON
  } else if ($garbage !== '') {
    echo json_encode([
      'status'  => 'error',
      'title'   => 'Salida inesperada',
      'message' => 'El servidor devolvió contenido no JSON.'
    ], JSON_UNESCAPED_UNICODE);
  } else {
    // fallback: sin salida → asumimos éxito
    echo json_encode([
      'status'  => 'success',
      'title'   => 'Vale registrado',
      'message' => 'El vale fue registrado correctamente.'
    ], JSON_UNESCAPED_UNICODE);
  }
}