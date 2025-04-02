<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getImpuestos();
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"isv_id"=>$row['isv_id'],
			"tipo_isv_nombre"=>$row['tipo_isv_nombre'],
			"valor"=>$row['valor']		  
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