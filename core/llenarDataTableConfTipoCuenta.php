<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getConfTipoCuenta();
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"tipo_cuenta_id"=>$row['tipo_cuenta_id'],
			"nombre"=>$row['nombre'],
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