<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$isv_id = $_POST['isv_id'];
	$result = $insMainModel->getImpuestosEdit($isv_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['isv_id'],
		1 => $valores2['tipo_isv_nombre'],
		2 => $valores2['isv_tipo_id'],		
		3 => $valores2['valor']		
	);
	echo json_encode($datos);