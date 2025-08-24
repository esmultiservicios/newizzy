<?php
// core/llenarDataTableEgresosContabilidad.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;
$fechai = $insMainModel->cleanString($_POST['fechai']);
$fechaf = $insMainModel->cleanString($_POST['fechaf']);

$datos = [
  "estado" => $estado,
  "fechai" => $fechai,
  "fechaf" => $fechaf,
];

$result = $insMainModel->getEgresosContables($datos);

$data = [];
while ($row = $result->fetch_assoc()){
  $data[] = [
    "egresos_id"     => $row['egresos_id'],
    "fecha_registro" => $row['fecha_registro'],
    "fecha"          => $row['fecha'],
    "nombre"         => $row['nombre'],
    "proveedor"      => $row['proveedor'],
    "factura"        => $row['factura'],
    "factura_pdf"    => $row['factura_pdf'],

    // Enviamos números crudos (sin prefijo) para que el front formatee
    "subtotal"  => (float)$row['subtotal'],
    "impuesto"  => (float)$row['impuesto'],
    "descuento" => (float)$row['descuento'],
    "nc"        => (float)$row['nc'],
    "total"     => (float)$row['total'],

    "categoria"   => $row['categoria'],
    "observacion" => $row['observacion'],
    "estado"      => $row['estado'],

    // IDs para anulación
    "proveedores_id" => isset($row['proveedores_id']) ? (int)$row['proveedores_id'] : 0,
    "cuentas_id"     => isset($row['cuentas_id'])     ? (int)$row['cuentas_id']     : 0,
    "empresa_id"     => isset($row['empresa_id'])     ? (int)$row['empresa_id']     : 0,

    // Duplicamos crudos por compatibilidad con lógica de anulación
    "subtotal_raw"   => isset($row['subtotal']) ? (float)$row['subtotal'] : 0,
    "isv_raw"        => isset($row['impuesto']) ? (float)$row['impuesto'] : 0,
    "descuento_raw"  => isset($row['descuento'])? (float)$row['descuento']: 0,
    "nc_raw"         => isset($row['nc'])       ? (float)$row['nc']       : 0,
    "total_raw"      => isset($row['total'])    ? (float)$row['total']    : 0,
  ];
}

echo json_encode([
  "echo" => 1,
  "totalrecords" => count($data),
  "totaldisplayrecords" => count($data),
  "data" => $data
]);