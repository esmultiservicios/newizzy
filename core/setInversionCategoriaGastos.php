<?php
// core/setInversionCategoriaGastos.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

$validacion = $mainModel->validarSesion();

if($validacion['error']){
	echo json_encode([
		"success" => false,
		"title" => "Error de sesión",
		"text" => $validacion['mensaje'],
		"type" => "error",
		"redirect" => $validacion['redireccion']
	], JSON_UNESCAPED_UNICODE);
	exit();
}

$categoria_gastos_id = isset($_POST['categoria_gastos_id']) ? (int)$_POST['categoria_gastos_id'] : 0;
$es_inversion = isset($_POST['es_inversion']) ? (int)$_POST['es_inversion'] : 0;

$es_inversion = $es_inversion === 1 ? 1 : 0;

if($categoria_gastos_id <= 0){
	echo json_encode([
		"success" => false,
		"title" => "Error",
		"text" => "No se recibió la categoría.",
		"type" => "error"
	], JSON_UNESCAPED_UNICODE);
	exit();
}

$conexion = $mainModel->connection();

if($es_inversion === 1){
	$conexion->query("UPDATE categoria_gastos SET es_inversion = 0 WHERE es_inversion = 1");

	$query = "
		UPDATE categoria_gastos
		SET es_inversion = 1,
			estado = 1
		WHERE categoria_gastos_id = '$categoria_gastos_id'
		LIMIT 1
	";
} else {
	$query = "
		UPDATE categoria_gastos
		SET es_inversion = 0
		WHERE categoria_gastos_id = '$categoria_gastos_id'
		LIMIT 1
	";
}

if($conexion->query($query)){
	echo json_encode([
		"success" => true,
		"title" => "Cambio aplicado",
		"text" => $es_inversion === 1 ? "La categoría fue marcada como inversión/reposición." : "Se quitó la marca de inversión a la categoría.",
		"type" => "success"
	], JSON_UNESCAPED_UNICODE);
	exit();
}

echo json_encode([
	"success" => false,
	"title" => "Error",
	"text" => "No se pudo actualizar la categoría.",
	"type" => "error"
], JSON_UNESCAPED_UNICODE);