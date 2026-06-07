<?php
// core/llenarDataTableMovimientosCuentasContabilidad.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

$datos = [
	"fechai"           => isset($_POST['fechai']) ? $_POST['fechai'] : date('Y-m-01'),
	"fechaf"           => isset($_POST['fechaf']) ? $_POST['fechaf'] : date('Y-m-d'),
	"cuenta_busqueda"  => isset($_POST['cuenta_busqueda']) ? trim($_POST['cuenta_busqueda']) : "",
	"tipo_movimiento"  => isset($_POST['tipo_movimiento']) ? trim($_POST['tipo_movimiento']) : "",
	"monto_desde"      => isset($_POST['monto_desde']) ? trim($_POST['monto_desde']) : "",
	"monto_hasta"      => isset($_POST['monto_hasta']) ? trim($_POST['monto_hasta']) : ""
];

$result = $insMainModel->getMovimientosCuentasContables($datos);

$data = [];

if ($result) {
	while ($row = $result->fetch_assoc()) {
		$data[] = [
			"movimientos_cuentas_id" => (int)$row['movimientos_cuentas_id'],
			"fecha"                  => $row['fecha'],
			"codigo"                 => $row['codigo'],
			"nombre"                 => $row['nombre'],
			"ingreso"                => (float)$row['ingreso'],
			"egreso"                 => (float)$row['egreso'],
			"saldo"                  => (float)$row['saldo'],
			"es_inversion"           => (int)$row['es_inversion']
		];
	}
}

echo json_encode([
	"echo" => 1,
	"totalrecords" => count($data),
	"totaldisplayrecords" => count($data),
	"data" => $data
], JSON_UNESCAPED_UNICODE);