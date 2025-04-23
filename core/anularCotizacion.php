<?php
	header("Content-Type: text/html;charset=utf-8");
	
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

	$insMainModel = new mainModel();

	date_default_timezone_set('America/Tegucigalpa');
	$cotizacion_id = $_POST['cotizacion_id'];

	//ANULAMOS LA COTIZACION DEL
	$query = $insMainModel->anular_cotizacion($cotizacion_id);

	if($query){
		echo 1;//COTIZACION ANULADA
	}else{
		echo 2; //ERROR AL ANULAR LA COTIZACION
	}