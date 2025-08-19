<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

/* Validar sesión */
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
  echo json_encode([
    "echo" => 1,
    "totalrecords" => 0,
    "totaldisplayrecords" => 0,
    "data" => []
  ]);
  exit;
}

/* Entradas */
$tipo = isset($_POST['tipo_cotizacion_reporte']) ? (int)$_POST['tipo_cotizacion_reporte'] : 1;
$fechai = $_POST['fechai'] ?? date('Y-m-01');
$fechaf = $_POST['fechaf'] ?? date('Y-m-d');

$datos = [
  "tipo_cotizacion_reporte" => $tipo,
  "fechai"                  => $fechai,
  "fechaf"                  => $fechaf,
  "empresa_id_sd"           => $_SESSION['empresa_id_sd'],
];

/* Consulta */
$result = $insMainModel->consultaCotizacionesReporte($datos);

$data = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      "cotizacion_id"        => (int)$row['cotizacion_id'],
      "fecha"                => $row['fecha'],
      "tipo_documento"       => $row['tipo_documento'],
      "cliente"              => $row['cliente'],
      "numero"               => $row['numero'],
      "numero_ordenamiento"  => (int)$row['numero_ordenamiento'],
      "subtotal"             => (float)$row['subtotal'],
      "isv"                  => (float)$row['isv'],
      "descuento"            => (float)$row['descuento'],
      "total"                => (float)$row['total'],
    ];
  }
}

echo json_encode([
  "echo"                 => 1,
  "totalrecords"         => count($data),
  "totaldisplayrecords"  => count($data),
  "data"                 => $data
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);