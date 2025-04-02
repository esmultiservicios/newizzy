<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// getFacturaporAno.php
$ano = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$result = $insMainModel->getFacturasAnual($ano);

$arreglo = array();

while ($row = $result->fetch_assoc()) {
	// Obtén el mes y el año de cada registro, asegurando que $mes sea un valor entre 1 y 12
	$mes = (int) date('n', strtotime($row['fecha']));  // 'n' devuelve el número de mes sin ceros iniciales
	$año = (int) date('Y', strtotime($row['fecha']));

	$arreglo[] = array(
		'mes' => $insMainModel->nombremes($mes, $año),
		'total' => $row['total'],
	);
}

echo json_encode($arreglo);
