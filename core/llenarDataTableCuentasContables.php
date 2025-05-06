<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;

	$result = $insMainModel->getCuentasContabilidad($estado);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"cuentas_id"=>$row['cuentas_id'],
			"codigo"=>$row['codigo'],
			"nombre"=>$row['nombre'],
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