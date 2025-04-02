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

	$datos = [
		"submenu_id" => $_POST['submenu_id'],
		"privilegio_id" => $_POST['privilegio_id'],		
	];	
			

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->delete_subMenu1Accessos($datos);

	if($query){
		echo 1;//ACCESO AL MENU ELIMINADO
	}else{
		echo 2; //ACCESO AL MENU NO FUE ELIMINADO
	}
?>