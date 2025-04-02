<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getMedida();
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"medida_id"=>$row['medida_id'],
			"nombre"=>$row['nombre'],
			"descripcion"=>$row['descripcion']		  
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