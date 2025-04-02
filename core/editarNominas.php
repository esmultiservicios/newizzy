<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$nomina_id  = $_POST['nomina_id'];
	$result = $insMainModel->getNominaEdit($nomina_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['detalle'],
		1 => $valores2['pago_planificado_id'],	
		2 => $valores2['empresa_id'],
		3 => $valores2['fecha_inicio'],		
		4 => $valores2['fecha_fin'],
		5 => $valores2['importe'],
		6 => $valores2['notas'],
		7 => $valores2['estado'],
		8 => $valores2['tipo_nomina_id'],
		9 => $valores2['cuentas_id'],
	);

	echo json_encode($datos);