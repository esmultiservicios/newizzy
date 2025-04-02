<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$nomina_detalles_id  = $_POST['nomina_detalles_id'];
	$result = $insMainModel->getNominaDetallesEdit($nomina_detalles_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['nomina_id'],
		1 => $valores2['nomina_detalles_id'],	
		2 => $valores2['pago_planificado_id'],
		3 => $valores2['colaboradores_id'],		
		4 => $valores2['empleado'],
		5 => $valores2['puesto'],
		6 => $valores2['identidad'],
		7 => $valores2['contrato_id'],
		8 => $valores2['fecha_ingreso'],
		9 => $valores2['salario_mensual'],	
		10 => $valores2['dias_trabajados'],
		11 => $valores2['retroactivo'],		
		12 => $valores2['bono'],
		13 => $valores2['otros_ingresos'],
		14 => $valores2['horas_25'],
		15 => $valores2['horas_50'],		
		16 => $valores2['horas_75'],
		17 => $valores2['horas_100'],
		18 => $valores2['deducciones'],
		19 => $valores2['prestamo'],
		20 => $valores2['ihss'],
		21 => $valores2['rap'],
		22 => $valores2['isr'],
		23 => $valores2['incapacidad_ihss'],
		24 => $valores2['neto_ingresos'],	
		25 => $valores2['neto_egresos'],
		26 => $valores2['neto'],
		27 => $valores2['notas'],
		28 => $valores2['nota_detalles'],
		29 => $valores2['estado'],
		30 => $valores2['vales'],
		31 => $valores2['colaboradores_id'],
		32 => $valores2['hrse25_valor'],
		33 => $valores2['hrse50_valor'],
		34 => $valores2['hrse75_valor'],
		35 => $valores2['hrse100_valor'],
		36 => $valores2['detalle'],
		37 => $valores2['tipo_empleado_id'],		
	);

	echo json_encode($datos);