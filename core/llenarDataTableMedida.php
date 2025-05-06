<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

	$result = $insMainModel->getMedida($estado);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"medida_id"=>$row['medida_id'],
			"nombre"=>$row['nombre'],
			"descripcion"=>$row['descripcion'],
			"estado"=>$row['estado']
		);
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);