<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$tipo_cuenta_id = $_POST['tipo_cuenta_id'];
	$result = $insMainModel->getTipoCuentaEdit($tipo_cuenta_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['estado'],
		2 => $valores2['tipo_cuenta_id']	
	);
	echo json_encode($datos);