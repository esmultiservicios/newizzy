<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [
		"estado" => $_POST['estado'],
		"tipo_contrato" => $_POST['tipo_contrato'],		
		"pago_planificado" => $_POST['pago_planificado'],
		"tipo_empleado" => $_POST['tipo_empleado']
	];	

	$result = $insMainModel->getContrato($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"contrato_id"=>$row['contrato_id'],
			"empleado"=>$row['empleado'],
			"tipo_contrato"=>$row['tipo_contrato'],
			"pago_planificado"=>$row['pago_planificado'],
			"tipo_empleado"=>$row['tipo_empleado'],
			"salario"=>$row['salario'],
			"fecha_inicio"=>$row['fecha_inicio'],	
			"fecha_fin"=>$row['fecha_fin'],
			"notas"=>$row['notas'],
			"estado"=>$row['estado'],				
			"estado_nombre"=>$row['estado_nombre'],
			"tipo_contrato_id"=>$row['tipo_contrato_id'],
			"pago_planificado_id"=>$row['pago_planificado_id'],
			"tipo_empleado_id"=>$row['tipo_empleado_id'],			
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);
?>	