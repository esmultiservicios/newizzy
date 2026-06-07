<?php
// core/cambiarEstadoCategoriaGastos.php

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
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 0;

$estado = $estado === 1 ? 1 : 0;

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

if($estado === 0){
	$query = "
		UPDATE categoria_gastos
		SET estado = 0,
			es_inversion = 0
		WHERE categoria_gastos_id = '$categoria_gastos_id'
		LIMIT 1
	";
} else {
	$query = "
		UPDATE categoria_gastos
		SET estado = 1
		WHERE categoria_gastos_id = '$categoria_gastos_id'
		LIMIT 1
	";
}

if($conexion->query($query)){
	echo json_encode([
		"success" => true,
		"title" => "Estado actualizado",
		"text" => $estado === 1 ? "La categoría fue activada correctamente." : "La categoría fue inactivada correctamente.",
		"type" => "success"
	], JSON_UNESCAPED_UNICODE);
	exit();
}

echo json_encode([
	"success" => false,
	"title" => "Error",
	"text" => "No se pudo cambiar el estado de la categoría.",
	"type" => "error"
], JSON_UNESCAPED_UNICODE);