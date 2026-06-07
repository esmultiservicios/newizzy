<?php
// core/llenarDataTableCategoriaEgresos.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();
$conexion = $mainModel->connection();

$query = "
	SELECT
		categoria_gastos_id,
		nombre,
		estado,
		usuario,
		date_write,
		es_inversion
	FROM categoria_gastos
	ORDER BY nombre ASC
";

$resultado = $conexion->query($query);

$data = [];

if($resultado){
	while($row = $resultado->fetch_assoc()){
		$data[] = [
			"categoria_gastos_id" => (int)$row['categoria_gastos_id'],
			"nombre" => $row['nombre'],
			"estado" => (int)$row['estado'],
			"usuario" => (int)$row['usuario'],
			"date_write" => $row['date_write'],
			"es_inversion" => (int)$row['es_inversion']
		];
	}
}

echo json_encode([
	"echo" => 1,
	"totalrecords" => count($data),
	"totaldisplayrecords" => count($data),
	"data" => $data
], JSON_UNESCAPED_UNICODE);