<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	
	$colaboradores_id = $_SESSION['colaborador_id_sd'];
	$result = $insMainModel->getUserSistema($colaboradores_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['colaboradores_id'], 
		1 => $valores2['colaborador'], 					
	);
	echo json_encode($datos);
?>	