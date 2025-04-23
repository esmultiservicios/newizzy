<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

    $datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf']
	];	
    
	$result = $insMainModel->getReporteCategoriaGastos($datos);
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"categoria"=>$row['categoria'],
			"monto"=>$row['monto']		  
		);	
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);