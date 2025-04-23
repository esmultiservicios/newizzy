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
	
	$result = $insMainModel->getConsumidorVenta();
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['clientes_id'], 
		1 => $valores2['cliente'], 
		2 => $valores2['rtn'], 		
	);
	
	echo json_encode($datos);