<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$cuentas_id = $_POST['cuentas_id'];
	$result = $insMainModel->getCuentasContabilidadEdit($cuentas_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['cuentas_id'], 
		1 => $valores2['codigo'], 
		2 => $valores2['nombre'],
		3 => $valores2['estado'],						
	);
	echo json_encode($datos);
?>	