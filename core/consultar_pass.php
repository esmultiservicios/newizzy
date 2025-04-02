<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	$contraseña_anterior = $insMainModel->encryption($_POST['contranaterior']);
	$colaborador_id_sd = $_SESSION['colaborador_id_sd'];
	
	$result = $insMainModel->consultar_usuario($colaborador_id_sd, $contraseña_anterior);

	if($result->num_rows==0){
		echo 0;
	}else{
		echo 1;
	}
		
?>