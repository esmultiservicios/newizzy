<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$planes_id  = $_POST['planes_id'];
	$result = $insMainModel->getPlanesEdit($planes_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'],
		1 => $valores2['estado'],					
	);
	echo json_encode($datos);
?>	