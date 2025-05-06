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

while ($row = $result->fetch_assoc()) {
	if ($row['puesto'] === 'Clientes') {
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

echo json_encode($arreglo);