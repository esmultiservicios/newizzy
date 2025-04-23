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
	

	//ACTUALIZAMOS LA BITACORA
	$codigo_bitacora = $_SESSION['codigo_bitacora_sd'];
	$hora = date("H:m:s");
	$insMainModel->actualizar_bitacora($codigo_bitacora, $hora);