<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$tipo_pago_id = $_POST['tipo_pago_id'];
	$result = $insMainModel->getTipoPagoEdit($tipo_pago_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['cuentas_id'],
		2 => $valores2['estado'],	
		3 => $valores2['tipo_pago_id']	
	);
	echo json_encode($datos);