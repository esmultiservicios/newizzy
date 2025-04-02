<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getConsumidorVenta();
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['clientes_id'], 
		1 => $valores2['cliente'], 
		2 => $valores2['rtn'], 		
	);
	
	echo json_encode($datos);
?>	