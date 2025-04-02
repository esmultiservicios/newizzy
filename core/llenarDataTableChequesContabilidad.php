<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];	
	
	$result = $insMainModel->getChequesContables($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"cheque_id"=>$row['cheque_id'],
			"fecha"=>$row['fecha'],
			"proveedor"=>$row['proveedor'],
			"factura"=>$row['factura'],
			"importe"=>$row['importe'],
			"codigo"=>$row['codigo'],
			"nombre"=>$row['nombre'],
			"observacion"=>$row['observacion']			
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