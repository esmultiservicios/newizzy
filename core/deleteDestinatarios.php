<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}

	$insMainModel = new mainModel();

	date_default_timezone_set('America/Tegucigalpa');
	$notificaciones_id = $_POST['notificaciones_id'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->deleteDestinatarios($notificaciones_id);

	if($query){
		echo 1;//ASISTENCIA ELIMINADA
	}else{
		echo 2; //ERROR AL ELIMINAR LA ASISTENCIA
	}
?>