<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$medida_id  = $_POST['medida_id'];
	$result = $insMainModel->getMedidaEdit($medida_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['descripcion'],		
		2 => $valores2['estado'],		
	);
	echo json_encode($datos);