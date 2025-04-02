<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

$ano = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$result = $insMainModel->getComprasAnual($ano);

$arreglo = array();

while ($row = $result->fetch_assoc()) {
	// Cambia 'm' por 'n' para obtener el mes sin ceros a la izquierda
	$mes = (int) date('n', strtotime($row['fecha']));  // 'n' devuelve el mes sin ceros iniciales

	$arreglo[] = array(
		'mes' => $insMainModel->nombremes($mes),
		'total' => $row['total'],
	);
}

echo json_encode($arreglo);
