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
		"acceso_submenu_id" => $_POST['acceso_submenu_id'],
		"submenu_id" => $_POST['submenu_id'],
		"privilegio_id" => $_POST['privilegio_id'],		
	];	

	//EVALUAMO QUE NO TENGA AGREGADO UN SUBMENU
	$resultVarios = $insMainModel->valid_submenu_on_submenu1_acceso($datos);

	if($resultVarios->num_rows==0){
		///ELIMINAMOS EL ACCESO AL SUBMENU
		$query = $insMainModel->delete_subMenuAccessos($datos);

		if($query){
			echo 1;//ACCESO AL SUBMENU ELIMINADO
		}else{
			echo 2; //ACCESO AL SUBMENU NO FUE ELIMINADO
		}
	}else{
		echo 3;//REGISTRO NO SE PUEDE ELIMINAR
	}	
?>