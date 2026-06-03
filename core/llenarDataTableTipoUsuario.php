<?php	
//llenarDataTableTipoUsuario.php
$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
	echo json_encode([
		"error" => true,
		"mensaje" => $validacion['mensaje'],
		"redireccion" => $validacion['redireccion'],
		"data" => []
	]);
	exit();
}

/*
	=========================================================
	VALIDAR BASE PRINCIPAL
	=========================================================

	DB_MAIN debe estar definido en configAPP.php:
	define('DB_MAIN', 'esmultiservicios_izzy');

	Regla:
	- Si la base actual es DB_MAIN, muestra todos los tipos de usuario.
	- Si la base actual NO es DB_MAIN, oculta tipo_user_id = 1.
*/
function obtenerBaseActualTipoUsuario($insMainModel) {
	$dbActual = "";

	$sql = "SELECT DATABASE() AS db_actual";
	$result = $insMainModel->ejecutar_consulta_simple($sql);

	if ($result && $result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$dbActual = isset($row['db_actual']) ? trim($row['db_actual']) : "";
	}

	return $dbActual;
}

function esBasePrincipalTipoUsuario($insMainModel) {
	if (!defined('DB_MAIN')) {
		return false;
	}

	$dbActual = obtenerBaseActualTipoUsuario($insMainModel);
	$dbMain = trim(DB_MAIN);

	if ($dbActual === "" || $dbMain === "") {
		return false;
	}

	return strtolower($dbActual) === strtolower($dbMain);
}

$es_base_principal = esBasePrincipalTipoUsuario($insMainModel);

$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? (int)$_POST['estado'] : 1;

$datos = [
	"privilegio_id" => $_SESSION['privilegio_sd'],
	"colaborador_id" => $_SESSION['colaborador_id_sd'],	
	"db_cliente" => $_SESSION['db_cliente'],
	"estado" => $estado
];	

$result = $insMainModel->getTipoUsuario($datos);

$arreglo = [];
$data = [];

if ($result) {
	while ($row = $result->fetch_assoc()) {
		$tipo_user_id = isset($row['tipo_user_id']) ? (int)$row['tipo_user_id'] : 0;

		// Si NO es base principal, ocultar Super Administrador
		if (!$es_base_principal && $tipo_user_id === 1) {
			continue;
		}

		$data[] = [
			"tipo_user_id" => $tipo_user_id,
			"nombre" => $row['nombre'],
			"estado" => (int)$row['estado']
		];		
	}
}

$arreglo = [
	"echo" => 1,
	"totalrecords" => count($data),
	"totaldisplayrecords" => count($data),
	"data" => $data
];

echo json_encode($arreglo);