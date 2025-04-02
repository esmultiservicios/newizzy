<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$puestos_id  = $_POST['puestos_id'];
	$result = $insMainModel->getPuestosEdit($puestos_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['estado'],					
	);
	echo json_encode($datos);
?>	