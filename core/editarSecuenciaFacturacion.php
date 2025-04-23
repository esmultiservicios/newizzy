<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$secuencia_facturacion_id  = $_POST['secuencia_facturacion_id'];
	$result = $insMainModel->getSecuenciaFacturacionEdit($secuencia_facturacion_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['empresa_id'],
		1 => $valores2['cai'],
		2 => $valores2['prefijo'],
		3 => $valores2['relleno'],
		4 => $valores2['incremento'],
		5 => $valores2['siguiente'],
		6 => $valores2['rango_inicial'],
		7 => $valores2['rango_final'],
		8 => $valores2['fecha_activacion'],
		9 => $valores2['fecha_limite'],
		10 => $valores2['activo'],					
		11 => $valores2['documento_id'],
	);
	echo json_encode($datos);