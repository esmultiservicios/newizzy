<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	$colaborador_id_sd = $_SESSION['colaborador_id_sd'];
	$fecha = date("Y-m-d");
	
	$result = $insMainModel->getAperturaCajaUsuario($colaborador_id_sd, $fecha);
	$estado = "2";
	$apertura_id = "";
	
	if($result->num_rows>0){
		$valores2 = $result->fetch_assoc();
		$apertura_id = $valores2['apertura_id'];
		$estado = $valores2['estado'];		
	}		
	$datos = array(
		0 => $estado, 
		1 => $apertura_id, 					
	);
	
	echo json_encode($datos);
?>	