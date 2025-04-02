<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf']	
	];	

	$result = $insMainModel->getMovimientosCuentasContables($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"movimientos_cuentas_id"=>$row['movimientos_cuentas_id'],
			"fecha"=>$row['fecha'],
			"codigo"=>$row['codigo'],
			"nombre"=>$row['nombre'],
			"ingreso"=>'L. '.$row['ingreso'],
			"egreso"=>'L. '.$row['egreso'],
			"saldo"=>'L. '.$row['saldo']					  
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