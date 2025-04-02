<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$contrato_id = $_POST['contrato_id'];
	$result = $insMainModel->getContratoEdit($contrato_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['colaborador_id'],
		1 => $valores2['tipo_contrato_id'],
		2 => $valores2['pago_planificado_id'],
		3 => $valores2['tipo_empleado_id'],	
		4 => $valores2['salario'],
		5 => $valores2['fecha_inicio'],
		6 => $valores2['fecha_fin'],
		7 => $valores2['notas'],
		8 => $valores2['estado'],
		9 => $valores2['salario_mensual']
	);
	echo json_encode($datos);
?>	