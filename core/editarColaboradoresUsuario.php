<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	
	$colaboradores_id = $_SESSION['colaborador_id_sd'];
	$result = $insMainModel->getColaboradoresEdit($colaboradores_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nombre'], 
		1 => $valores2['apellido'],
		2 => $valores2['identidad'],
		3 => $valores2['telefono'],						
		4 => $valores2['puestos_id'], 
		5 => $valores2['empresa_id'], 		
		6 => $valores2['estado'],
		7 => $valores2['colaboradores_id'],		
		8 => $valores2['fecha_ingreso'],	
		9 => $valores2['fecha_egreso'],	
	);
	echo json_encode($datos);
	
?>	