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
	
	$colaborador_id_sd = $_SESSION['colaborador_id_sd'];
	$fecha = date("Y-m-d");
	
	$result = $insMainModel->getAperturaCajaUsuario($colaborador_id_sd, $fecha);
	$estado = "2";
	$apertura_id = "";
	
	if($result->num_rows>0){
		$valores2 = $result->fetch_assoc();
		$apertura_id = $valores2['apertura_id'];
		$estado = $valores2['estado'];		
	}		
	$datos = array(
		0 => $estado, 
		1 => $apertura_id, 					
	);
	
	echo json_encode($datos);