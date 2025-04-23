<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$documento = $_POST['documento'];
	$result = $insMainModel->getISV($documento);
	
	$isv = "";
	$activar = "";
	
	if($result->num_rows>0){
		$consulta2 = $result->fetch_assoc();
		$isv = $consulta2['valor'];
		$activar = $consulta2['activar'];
	}

	$datos = array(
		0 => $isv,
		1 => $activar,
	);	

	echo json_encode($datos);