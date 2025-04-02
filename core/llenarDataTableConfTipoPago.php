<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getTipoPagoContabilidad();
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"tipo_pago_id"=>$row['tipo_pago_id'],
			"nombre"=>$row['nombre'],
			"codigo"=>$row['codigo'],
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