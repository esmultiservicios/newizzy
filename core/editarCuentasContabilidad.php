<?php
// editarCuentasContabilidad.php
$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$cuentas_id = isset($_POST['cuentas_id']) ? $_POST['cuentas_id'] : 0;

$result = $insMainModel->getCuentasContabilidadEdit($cuentas_id);
$valores2 = $result->fetch_assoc();

$datos = array(
	0 => isset($valores2['cuentas_id']) ? $valores2['cuentas_id'] : '',
	1 => isset($valores2['codigo']) ? $valores2['codigo'] : '',
	2 => isset($valores2['nombre']) ? $valores2['nombre'] : '',
	3 => isset($valores2['estado']) ? $valores2['estado'] : 1,
	4 => isset($valores2['es_inversion']) ? $valores2['es_inversion'] : 0
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($datos);