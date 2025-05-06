<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

	$result = $insMainModel->getTipoPagoContabilidad($estado);
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"tipo_pago_id"=>$row['tipo_pago_id'],
			"nombre"=>$row['nombre'],
			"codigo"=>$row['codigo'],
			"cuenta"=>$row['cuenta'],
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