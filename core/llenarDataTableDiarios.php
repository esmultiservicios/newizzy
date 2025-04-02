<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getDiarios();
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"diarios_id"=>$row['diarios_id'],
			"diario"=>$row['diario'],
			"cuenta"=>$row['cuenta']		  
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