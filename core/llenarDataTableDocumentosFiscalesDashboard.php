<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getDocumentosfiscalesDashboard();
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"secuencia_facturacion_id"=>$row['secuencia_facturacion_id'],
			"empresa"=>$row['empresa'],
			"documento"=>$row['documento'],
			"cai"=>$row['cai'],
			"inicio"=>$row['rango_inicial'],
			"fin"=>$row['rango_final'],
			"fecha"=>$row['fecha_limite'],
			"siguiente"=>$row['siguiente']			
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