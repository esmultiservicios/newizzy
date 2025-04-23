<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$fechai = $_POST['fechai'];
	$fechaf = $_POST['fechaf'];
	
	$result = $insMainModel->getHistorialAccesos($fechai, $fechaf);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"historial_acceso_id"=>$row['historial_acceso_id'],
			"fecha"=>$row['fecha'],
			"colaborador"=>$row['colaborador'],
			"ip"=>$row['ip'],
			"acceso"=>$row['acceso']		  
		);
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);