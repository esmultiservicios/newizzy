<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$fechai = $_POST['fechai'] ?? date('Y-m-01');
$fechaf = $_POST['fechaf'] ?? date('Y-m-d');

$result = $insMainModel->consultaBillDraft([
  "fechai" => $fechai,
  "fechaf" => $fechaf,
]);

$data = [];

while ($row = $result->fetch_assoc()) {
    $facturas_id = (int)$row['facturas_id'];

    // Si quieres recalcular subtotal/isv/descuento aquí:
    $det = $insMainModel->getDetalleFactura($facturas_id);
    $subtotal = 0; $isv = 0; $descuento = 0;
    while ($d = $det->fetch_assoc()) {
        $subtotal  += ((float)$d['precio'] * (float)$d['cantidad']);
        $isv       += (float)$d['isv_valor'];
        $descuento += (float)$d['descuento'];
    }

    $data[] = [
        "facturas_id"    => $facturas_id,
        "fecha"          => $row['fecha'],
        "tipo_documento" => $row['tipo_documento'],
        "cliente"        => $row['cliente'],
        "numero"         => $row['numero'],
        "subtotal"       => round($subtotal, 2),
        "isv"            => round($isv, 2),
        "descuento"      => round($descuento, 2),
        "total"          => (float)str_replace(',', '', $row['total']),
    ];
}

echo json_encode(["data" => $data]);
