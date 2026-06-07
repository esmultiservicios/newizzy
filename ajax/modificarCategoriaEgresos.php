<?php
// ajax/modificarCategoriaEgresos.php

$peticionAjax = true;

require_once "../core/configGenerales.php";

header('Content-Type: application/json; charset=utf-8');

if(isset($_POST['categoria']) && isset($_POST['categoria_gastos_id'])){
	require_once "../controladores/egresosContabilidadControlador.php";

	$insVarios = new egresosContabilidadControlador();

	echo $insVarios->edit_categoria_egresos_contabilidad_controlador();
} else {
	$missingFields = [];

	if (!isset($_POST['categoria_gastos_id'])) $missingFields[] = "ID de la categoría";
	if (!isset($_POST['categoria'])) $missingFields[] = "Categoría";

	echo json_encode([
		"success" => false,
		"title" => "Error",
		"text" => "Faltan los siguientes campos: ".implode(", ", $missingFields).". Por favor, corríjalos.",
		"type" => "error"
	], JSON_UNESCAPED_UNICODE);
}