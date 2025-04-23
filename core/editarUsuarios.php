<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$usuarios_id  = $_POST['usuarios_id'];
	$result = $insMainModel->getUsersEdit($usuarios_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['colaborador_id'],
		1 => $valores2['colaborador'],
		2 => $valores2['username'],
		3 => $valores2['correo'],
		4 => $valores2['empresa_id'],
		5 => $valores2['tipo_user_id'],
		6 => $valores2['estado'],
		7 => $valores2['privilegio_id'],
		8 => $valores2['estado'],						
		9 => $valores2['server_customers_id'],
	);
	echo json_encode($datos);