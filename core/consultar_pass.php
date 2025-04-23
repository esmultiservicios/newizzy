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
	
	$contraseña_anterior = $insMainModel->encryption($_POST['contranaterior']);
	$colaborador_id_sd = $_SESSION['colaborador_id_sd'];
	
	$result = $insMainModel->consultar_usuario($colaborador_id_sd, $contraseña_anterior);

	if($result->num_rows==0){
		echo 0;
	}else{
		echo 1;
	}