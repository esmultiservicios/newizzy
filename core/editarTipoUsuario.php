<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$tipo_user_id = $_POST['tipo_user_id'];
		
	$result = $insMainModel->getTipoUsuarioEdit($tipo_user_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['estado'],						
	);
	echo json_encode($datos);