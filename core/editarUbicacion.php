<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$ubicacion_id  = $_POST['ubicacion_id'];
	$result = $insMainModel->getUbicacionEdit($ubicacion_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['empresa_id'],
		1 => $valores2['nombre'],
		2 => $valores2['estado'],		
	);
	echo json_encode($datos);