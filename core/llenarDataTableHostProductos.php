<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getHostProductos();
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"host_id"=>$row['host_id'],
			"host_detalles_id"=>$row['host_detalles_id'],			
			"cliente"=>$row['cliente'],
			"plan"=>$row['plan'],
			"producto"=>$row['producto'],
			"cantidad"=>$row['cantidad']							  
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