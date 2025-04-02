<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$colaboradores_id = $_POST['colaboradores_id'];
	$result = $insMainModel->getEmpleadoContratoEdit($colaboradores_id);
	$valores2 = $result->fetch_assoc();
	$salario_mensual = isset($valores2['salario_mensual']) ? $valores2['salario_mensual'] : 0;
	$salario = isset($valores2['salario']) ? $valores2['salario'] : 0;

	//CONSULTAR SI EL EMPLEADO TIENE VALES
	$resultVales = $insMainModel->getConsultaValesEmpleado($colaboradores_id);

	$vales = 0;

	if($result->num_rows > 0) {
		$valores2Vales = $result->fetch_assoc();
		$valores2Vales = $resultVales->fetch_assoc();
		$vales = isset($valores2Vales['monto']) ? $valores2Vales['monto'] : 0;
	}

	$datos = array(
		0 => $valores2['puesto'],
		1 => $valores2['identidad'],
		2 => $valores2['contrato_id'],
		3 => $salario_mensual,	
		4 => $valores2['fecha_ingreso'],
		5 => $valores2['tipo_empleado_id'],
		6 => $valores2['pago_planificado_id'],
		7 => $vales,
		8 => $salario,
		9 =>  $valores2['semanal']
	);

	echo json_encode($datos);