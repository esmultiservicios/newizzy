<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getHost();
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"host_id"=>$row['host_id'],
			"cliente"=>$row['cliente'],
			"plan"=>$row['plan'],
			"server"=>$row['server'],
			"db"=>$row['db'],
			"user"=>$row['user'],
			"pass"=>$row['pass']								  
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