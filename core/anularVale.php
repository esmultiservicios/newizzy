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
	$vale_id = $_POST['vale_id'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->anular_vale($vale_id);

	if($query){
		echo 1;//VALE ANULADO
	}else{
		echo 2; //ERROR AL ANULAR EL VALE
	}
	
?>