<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$apertura_id = $_POST['apertura_id'];
		
	$result = $insMainModel->getCajasEdit($apertura_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['usuario'],
		1 => $valores2['monto_apertura'],		
		2 => $valores2['fecha'],	
		3 => $valores2['colaboradores_id'],
	);
	echo json_encode($datos);
?>	