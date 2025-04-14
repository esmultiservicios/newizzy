<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$id = intval($_POST['id']);

$query = "SELECT nombre, number, tipo_calculo, monto, porcentaje, activo FROM programa_puntos WHERE id = $id";
$result = $insMainModel->ejecutar_consulta($query);

$datos = array(
	'nombre' => '',
	'number' => '',
	'tipo_calculo' => '',
	'monto' => '',
	'porcentaje' => '',
	'activo' => '',
);

if ($result->num_rows > 0) {
	$row = $result->fetch_assoc();
	$datos = array(
		'nombre' => $row['nombre'],
		'number' => $row['number'],
		'tipo_calculo' => $row['tipo_calculo'],
		'monto' => $row['monto'],
		'porcentaje' => $row['porcentaje'],
		'activo' => $row['activo'],
	);
}

echo json_encode($datos);