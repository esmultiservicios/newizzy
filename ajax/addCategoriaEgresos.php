<?php
// ajax/addCategoriaEgresos.php

$peticionAjax = true;

require_once "../core/configGenerales.php";

header('Content-Type: application/json; charset=utf-8');

if(isset($_POST['categoria'])){
	require_once "../controladores/egresosContabilidadControlador.php";

	$insVarios = new egresosContabilidadControlador();

	echo $insVarios->agregar_categoria_egresos_controlador();
} else {
	echo json_encode([
		"success" => false,
		"title" => "Error",
		"text" => "Falta el campo categoría. Por favor, corríjalo.",
		"type" => "error"
	], JSON_UNESCAPED_UNICODE);
}