<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$datos = [
  "fechai" => $_POST['fechai'],
  "fechaf" => $_POST['fechaf']
];

$result = $insMainModel->getMovimientosCuentasContables($datos);

$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = [
    "movimientos_cuentas_id" => (int)$row['movimientos_cuentas_id'],
    "fecha"   => $row['fecha'],     // 'YYYY-MM-DD HH:MM:SS'
    "codigo"  => $row['codigo'],
    "nombre"  => $row['nombre'],
    "ingreso" => (float)$row['ingreso'],
    "egreso"  => (float)$row['egreso'],
    "saldo"   => (float)$row['saldo']
  ];
}

echo json_encode([
  "echo" => 1,
  "totalrecords" => count($data),
  "totaldisplayrecords" => count($data),
  "data" => $data
]);