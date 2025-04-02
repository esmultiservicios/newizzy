<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

if (!isset($_SESSION['user_sd'])) {
	session_start(['name' => 'SD']);
}

$insMainModel = new mainModel();

$datos = [
	'tipo_factura_reporte' => $_POST['tipo_factura_reporte'],
	'fechai' => $_POST['fechai'],
	'fechaf' => $_POST['fechaf'],
	'facturador' => $_POST['facturador'],
	'vendedor' => $_POST['vendedor'],
	'factura' => $_POST['factura'],
	'empresa_id_sd' => $_SESSION['empresa_id_sd'],
];

$result = $insMainModel->consultaVentas($datos);

$arreglo = array();
$data = [];

while ($row = $result->fetch_assoc()) {
    $ganancia = doubleval($row['subtotal']) - doubleval($row['subCosto']) - doubleval($row['isv']) - doubleval($row['descuento']);

    $color = 'bg-c-green'; // Por defecto

    if ($row['tipo_documento'] == 'Crédito' && $row['pagos_realizados'] == 0) {
        $color = 'bg-c-yellow';
    }

    $data[] = array(
        'facturas_id' => $row['facturas_id'],
        'fecha' => $row['fecha'],
        'tipo_documento' => $row['tipo_documento'],
        'cliente' => $row['cliente'],
        'numero' => $row['numero'],
        'subtotal' => $row['subtotal'],
        'ganancia' => $ganancia,
        'isv' => $row['isv'],
        'descuento' => $row['descuento'],
        'total' => $row['total'],
        'color' => $color,
        'vendedor' => $row['vendedor'],
        'facturador' => $row['facturador'],
    );
}

$arreglo = array(
    'echo' => 1,
    'totalrecords' => count($data),
    'totaldisplayrecords' => count($data),
    'data' => $data
);

echo json_encode($arreglo);