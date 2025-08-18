<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Filtros
$estado = isset($_POST['estado']) ? intval($_POST['estado']) : 1;
$fechai = $insMainModel->cleanString($_POST['fechai']);
$fechaf = $insMainModel->cleanString($_POST['fechaf']);

$datos = [
    "estado" => $estado,
    "fechai" => $fechai,
    "fechaf" => $fechaf,
];

$result = $insMainModel->getIngresosContables($datos);

$data = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            // IDs necesarios para anulación
            "ingresos_id"  => (int)$row['ingresos_id'],
            "cuentas_id"   => isset($row['cuentas_id'])   ? (int)$row['cuentas_id']   : 0,
            "empresa_id"   => isset($row['empresa_id'])   ? (int)$row['empresa_id']   : 0,
            "clientes_id"  => isset($row['clientes_id'])  ? (int)$row['clientes_id']  : 0,

            // Fechas y descriptores
            "fecha_registro" => $row['fecha_registro'],
            "fecha"          => $row['fecha'],
            "nombre"         => $row['nombre'],   // nombre de la cuenta
            "cliente"        => $row['cliente'],
            "factura"        => $row['factura'],
            "observacion"    => $row['observacion'],
            "tipo_ingreso"   => $row['tipo_ingreso'],
            "estado"         => (int)$row['estado'],

            // Montos crudos (para cálculos)
            "subtotal_raw"   => isset($row['subtotal'])  ? (float)$row['subtotal']  : 0,
            "impuesto_raw"   => isset($row['impuesto'])  ? (float)$row['impuesto']  : 0,
            "descuento_raw"  => isset($row['descuento']) ? (float)$row['descuento'] : 0,
            "nc_raw"         => isset($row['nc'])        ? (float)$row['nc']        : 0,
            "total_raw"      => isset($row['total'])     ? (float)$row['total']     : 0,

            // Si además quieres mostrar formateados en la tabla:
            "subtotal" => (float)$row['subtotal'],
            "impuesto" => (float)$row['impuesto'],
            "descuento"=> (float)$row['descuento'],
            "nc"       => (float)$row['nc'],
            "total"    => (float)$row['total'],
        ];
    }
}

echo json_encode([
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
]);