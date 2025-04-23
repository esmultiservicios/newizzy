<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$diarios_id = $_POST['diarios_id'];
		
	$result = $insMainModel->getDiariosEdit($diarios_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['diarios_id'],
		1 => $valores2['diario'],		
		2 => $valores2['cuentas_id'],
		3 => $valores2['estado'],				
	);
	echo json_encode($datos);