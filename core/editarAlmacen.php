<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$almacen_id = $_POST['almacen_id'];
	$result = $insMainModel->getAlmacenEdit($almacen_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['ubicacion_id'],
		1 => $valores2['nombre'],
		2 => $valores2['estado'],
		3 => $valores2['empresa_id'],	
		4 => $valores2['facturar_cero']	
	);
	echo json_encode($datos);