<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	$users_id = $_POST['users_id'];
	
	$result = $insMainModel->getNombreUsuario($users_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['usuario'],	
		1 => $valores2['identidad'],						
	);
	echo json_encode($datos);
?>	