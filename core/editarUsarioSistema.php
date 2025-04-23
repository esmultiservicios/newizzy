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
	$result = $insMainModel->getUserSistema($colaboradores_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['colaboradores_id'], 
		1 => $valores2['colaborador'], 					
	);
	echo json_encode($datos);