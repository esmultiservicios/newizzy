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
	$facturas_id = $_POST['facturas_id'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->delete_bill_draft($facturas_id);

	if($query){
		echo 1;//FACTURA ELIMINADA
	}else{
		echo 2; //ERROR AL ELIMINAR LA FACTURA
	}
	
?>