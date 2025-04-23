<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
	return $insMainModel->showNotification([
		"title" => "Error de sesión",
		"text" => $validacion['mensaje'],
		"type" => "error",
		"funcion" => "window.location.href = '".$validacion['redireccion']."'"
	]);
}

$datos = [
	'tipo_producto_id' => $_POST['tipo_producto_id'],
	'fechai' => $_POST['fechai'],
	'fechaf' => $_POST['fechaf'],
	'bodega' => $_POST['bodega'],
	'producto' => $_POST['producto'],
	'cliente' => $_POST['cliente'],
	'empresa_id_sd' => $_SESSION['empresa_id_sd']
];

$result = $insMainModel->getMovimientosProductos($datos);

$arreglo = array();
$data = array();

while ($row = $result->fetch_assoc()) {

	if($row['almacen_id'] == 0 || $row['almacen_id'] == null){
		$bodega = "Sin bodega";
	}else{
		$bodega = $row['bodega'];
	}

	$data[] = array(
		'cliente' => $row['cliente'],
		'comentario' => $row['comentario'],
		'movimientos_id' => $row['movimientos_id'],
		'fecha_registro' => $row['fecha_registro'],
		'barCode' => $row['barCode'],
		'producto' => $row['producto'],
		'medida' => $row['medida'],
		'documento' => $row['documento'],
		'entrada' => $row['entrada'],
		'salida' => $row['salida'],
		'saldo' => $row['saldo'],
		'saldo_anterior' => $row['saldo_anterior'],
		'bodega' => $bodega,
		'id_bodega' => $row['almacen_id'],
		'productos_id' => $row['productos_id'],
		'numero_lote' => $row['numero_lote'],
		'image' => $row['image']
	);
}

$arreglo = array(
	'echo' => 1,
	'totalrecords' => count($data),
	'totaldisplayrecords' => count($data),
	'data' => $data
);

echo json_encode($arreglo);
