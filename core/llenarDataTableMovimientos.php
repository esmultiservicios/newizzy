<?php
// llenarDataTableMovimientos.php

$peticionAjax = true;

require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
	echo json_encode([
		'echo' => 1,
		'totalrecords' => 0,
		'totaldisplayrecords' => 0,
		'data' => [],
		'error' => true,
		'mensaje' => $validacion['mensaje']
	]);
	exit();
}

$datos = [
	'tipo_producto_id' => isset($_POST['tipo_producto_id']) ? $_POST['tipo_producto_id'] : '',
	'fechai'           => isset($_POST['fechai']) ? $_POST['fechai'] : '',
	'fechaf'           => isset($_POST['fechaf']) ? $_POST['fechaf'] : '',
	'bodega'           => isset($_POST['bodega']) ? $_POST['bodega'] : '',
	'producto'         => isset($_POST['producto']) ? $_POST['producto'] : '',
	'cliente'          => isset($_POST['cliente']) ? $_POST['cliente'] : '',
	'empresa_id_sd'    => isset($_SESSION['empresa_id_sd']) ? $_SESSION['empresa_id_sd'] : 0
];

$result = $insMainModel->getMovimientosProductos($datos);

$data = [];

while ($row = $result->fetch_assoc()) {

	$bodega = ($row['almacen_id'] == 0 || $row['almacen_id'] == null) ? "Sin bodega" : $row['bodega'];

	$data[] = [
		'cliente'        => $row['cliente'],
		'comentario'     => $row['comentario'],
		'movimientos_id' => $row['movimientos_id'],
		'fecha_registro' => $row['fecha_registro'],
		'barCode'        => $row['barCode'],
		'producto'       => $row['producto'],
		'medida'         => $row['medida'],
		'documento'      => $row['documento'],

		'entrada'        => (float)$row['entrada'],
		'salida'         => (float)$row['salida'],
		'saldo'          => (float)$row['saldo'],
		'saldo_anterior' => (float)$row['saldo_anterior'],

		'bodega'         => $bodega,
		'id_bodega'      => $row['almacen_id'],
		'productos_id'   => $row['productos_id'],
		'numero_lote'    => $row['numero_lote'],
		'image'          => $row['image']
	];
}

echo json_encode([
	'echo' => 1,
	'totalrecords' => count($data),
	'totaldisplayrecords' => count($data),
	'data' => $data
]);