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

	date_default_timezone_set('America/Tegucigalpa');
	$asistencia_id = $_POST['asistencia_id'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->deleteAsistenciaColaborador($asistencia_id);

	if($query){
		echo 1;//ASISTENCIA ELIMINADA
	}else{
		echo 2; //ERROR AL ELIMINAR LA ASISTENCIA
	}