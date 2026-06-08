<?php
// core/llenarDataTableEgresosContabilidad.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

$estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
$fechai = isset($_POST['fechai']) ? $insMainModel->cleanString($_POST['fechai']) : "";
$fechaf = isset($_POST['fechaf']) ? $insMainModel->cleanString($_POST['fechaf']) : "";

$datos = [
  "estado" => $estado,
  "fechai" => $fechai,
  "fechaf" => $fechaf,
];

$result = $insMainModel->getEgresosContables($datos);

$data = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $data[] = [
      "egresos_id"     => isset($row['egresos_id']) ? (int)$row['egresos_id'] : 0,
      "fecha_registro" => isset($row['fecha_registro']) ? $row['fecha_registro'] : "",
      "fecha"          => isset($row['fecha']) ? $row['fecha'] : "",
      "nombre"         => isset($row['nombre']) ? $row['nombre'] : "",
      "proveedor"      => isset($row['proveedor']) ? $row['proveedor'] : "",
      "factura"        => isset($row['factura']) ? $row['factura'] : "",
      "factura_pdf"    => isset($row['factura_pdf']) ? $row['factura_pdf'] : "",
      "categoria"      => isset($row['categoria']) ? $row['categoria'] : "",
      "observacion"    => isset($row['observacion']) ? $row['observacion'] : "",
      "estado"         => isset($row['estado']) ? (int)$row['estado'] : 0,

      "proveedores_id" => isset($row['proveedores_id']) ? (int)$row['proveedores_id'] : 0,
      "cuentas_id"     => isset($row['cuentas_id']) ? (int)$row['cuentas_id'] : 0,
      "empresa_id"     => isset($row['empresa_id']) ? (int)$row['empresa_id'] : 0,
      "categoria_gastos_id" => isset($row['categoria_gastos_id']) ? (int)$row['categoria_gastos_id'] : 0,

      "subtotal_raw"   => isset($row['subtotal']) ? (float)$row['subtotal'] : 0,
      "isv_raw"        => isset($row['impuesto']) ? (float)$row['impuesto'] : 0,
      "descuento_raw"  => isset($row['descuento']) ? (float)$row['descuento'] : 0,
      "nc_raw"         => isset($row['nc']) ? (float)$row['nc'] : 0,
      "total_raw"      => isset($row['total']) ? (float)$row['total'] : 0,

      "subtotal"       => isset($row['subtotal']) ? (float)$row['subtotal'] : 0,
      "impuesto"       => isset($row['impuesto']) ? (float)$row['impuesto'] : 0,
      "descuento"      => isset($row['descuento']) ? (float)$row['descuento'] : 0,
      "nc"             => isset($row['nc']) ? (float)$row['nc'] : 0,
      "total"          => isset($row['total']) ? (float)$row['total'] : 0,
    ];
  }
}

echo json_encode([
  "echo" => 1,
  "totalrecords" => count($data),
  "totaldisplayrecords" => count($data),
  "data" => $data
], JSON_UNESCAPED_UNICODE);
