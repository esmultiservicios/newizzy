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

$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

$datos = [
	'empresa_id' => $_SESSION['empresa_id_sd'],
	"estado" => $estado
];

$result = $insMainModel->getColaboradoresTabla($datos);

$arreglo = array();
$data = array();

/*
 * IZZY | Cotización
 * El mismo endpoint se utiliza en distintos módulos.
 * Únicamente cuando la petición proviene de /cotizacion/
 * se limita el listado al puesto "Vendedores".
 */
$referer = isset($_SERVER['HTTP_REFERER']) ? (string)$_SERVER['HTTP_REFERER'] : '';
$solo_vendedores_cotizacion = preg_match('~/cotizacion(?:/|$|\?)~i', $referer) === 1;

while ($row = $result->fetch_assoc()) {
	if ($row['puesto'] === 'Clientes') {
		continue;
	}

	if ($solo_vendedores_cotizacion && strcasecmp(trim((string)$row['puesto']), 'Vendedores') !== 0) {
		continue;
	}

	$data[] = array(
		'colaborador_id' => $row['colaborador_id'],
		'empresa' => $row['empresa'],
		'colaborador' => $row['colaborador'],
		'identidad' => $row['identidad'],
		'estado' => $row['estado'],
		'telefono' => $row['telefono'],
		'puesto' => $row['puesto']
	);
}

$arreglo = array(
	'echo' => 1,
	'totalrecords' => count($data),
	'totaldisplayrecords' => count($data),
	'data' => $data
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($arreglo, JSON_UNESCAPED_UNICODE);
