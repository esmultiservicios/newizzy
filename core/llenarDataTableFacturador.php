<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getFacturador();
	
	$arreglo = array();
	$data = array();

	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"colaboradores_id"=>$row['colaboradores_id'],
			"nombre"=>$row['nombre'],
			"identidad"=>$row['identidad']			
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