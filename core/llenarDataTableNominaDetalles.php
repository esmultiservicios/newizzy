<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [
		"estado" => $_POST['estado'],
		"empleado" => $_POST['empleado'],
		"nomina_id" => $_POST['nomina_id'],
	];	

	$result = $insMainModel->getNominaDetalles($datos);
	
	$arreglo = array();
	$data = array();
	
	$total_neto_ingreso = 0;
	$total_neto_egreso = 0;
	$total_neto = 0;

	while($row = $result->fetch_assoc()){
		$total_neto_ingreso += $row['neto_ingresos'];
		$total_neto_egreso += $row['neto_egresos'];
		$total_neto += $row['neto'];
						
		$data[] = array( 
			"nomina_id"=>$row['nomina_id'],
			"nomina_detalles_id"=>$row['nomina_detalles_id'],
			"empleado"=>$row['empleado'],
			"neto_ingresos"=>$row['neto_ingresos'],
			"neto_egresos"=>$row['neto_egresos'],
			"neto"=>$row['neto'],
			"notas"=>$row['notas'],
			"contrato"=>$row['contrato'],
			"empresa"=>$row['empresa'],
			"total_neto_ingreso"=>$total_neto_ingreso,
			"total_neto_egreso"=>$total_neto_egreso,
			"total_neto"=>$total_neto,
			"fecha_inicio"=>$row['fecha_inicio'],
			"fecha_fin"=>$row['fecha_fin'],
			"estado"=>$row['estado'],
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);