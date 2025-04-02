<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$host_detalles_id  = $_POST['host_detalles_id'];
	$result = $insMainModel->getHostProductosEdit($host_detalles_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['clientes_id'],
		1 => $valores2['plan'],	
		2 => $valores2['productos_id'],
		3 => $valores2['cantidad']								
	);
	echo json_encode($datos);
?>