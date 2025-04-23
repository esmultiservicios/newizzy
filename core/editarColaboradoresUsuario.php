<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}
	
	$colaboradores_id = $_SESSION['colaborador_id_sd'];
	$result = $insMainModel->getColaboradoresEdit($colaboradores_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'], 
		1 => $valores2['identidad'],
		2 => $valores2['telefono'],						
		3 => $valores2['puestos_id'], 
		4 => $valores2['empresa_id'], 		
		5 => $valores2['estado'],
		6 => $valores2['colaboradores_id'],		
		7 => $valores2['fecha_ingreso'],	
		8 => $valores2['fecha_egreso'],	
		9 => $valores2['colaboradores_id'],
	);
	echo json_encode($datos);
	