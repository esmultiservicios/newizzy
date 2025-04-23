<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$categoria_id = $_POST['categoria_id'];
	$result = $insMainModel->getCategoriaProductoEdit($categoria_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['categoria_id'],
		1 => $valores2['nombre'],
		2 => $valores2['estado'],
		3 => $valores2['fecha_registro'],		
	);
	echo json_encode($datos);