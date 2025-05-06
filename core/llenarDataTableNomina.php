<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

	$datos = [
		"estado" => $estado,	
		"tipo_contrato_id" => $_POST['tipo_contrato_id'],
	];	

	$result = $insMainModel->getNomina($datos);
	
	$arreglo = array();
	$data = array();
	$neto_importe = 0;
	$importe = 0;	

	while($row = $result->fetch_assoc()){
		$importe  = $row['importe'];
		if($row['estado'] === "0"){
			//CONSULTAMOS EL TOTAL DE EMPLEADOS AGREGADOS EN LA NOMINA DE TALLES PARA SUMAR EL IMPORTE
			$resultNominaDetalle = $insMainModel->getImporteNominaDetalles($row['nomina_id']);
			$consultaNominaDetalle = $resultNominaDetalle->fetch_assoc();
			$importe = $consultaNominaDetalle['neto'] ?? 0;
		}

		$neto_importe += $importe;

		$data[] = array( 
			"nomina_id"=>$row['nomina_id'],
			"empresa"=>$row['empresa'],
			"fecha_inicio"=>$row['fecha_inicio'],
			"fecha_fin"=>$row['fecha_fin'],
			"importe"=>$importe ,
			"notas"=>$row['notas']	,
			"detalle"=>$row['detalle'],
			"pago_planificado_id"=>$row['pago_planificado_id'],
			"estado"=>(int)$row['estado'],
			"neto_importe"=>$neto_importe,	
			"empresa_id"=>$row['empresa_id'],	
		);			
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);