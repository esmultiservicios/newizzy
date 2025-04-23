<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getValesPendientes();
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"vale_id"=>$row['vale_id'],
			"colaboradores_id"=>$row['colaboradores_id'],
			"monto"=>$row['monto'],
			"empleado"=>$row['empleado'],
			"nota"=>$row['nota']				  
		);	
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);