<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;
	$fechai = $insMainModel->cleanString($_POST['fechai']);
	$fechaf = $insMainModel->cleanString($_POST['fechaf']); 

	$datos = [
		"estado" => $estado,
		"fechai" => $fechai,
		"fechaf" => $fechaf, 	
	];	
		
	$result = $insMainModel->getEgresosContables($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"egresos_id"=>$row['egresos_id'],
			"fecha_registro"=>$row['fecha_registro'],
			"fecha"=>$row['fecha'],
			"nombre"=>$row['nombre'],
			"proveedor"=>$row['proveedor'],
			"factura"=>$row['factura'],
			"subtotal"=>'L. '.$row['subtotal'],
			"impuesto"=>'L. '.$row['impuesto'],
			"descuento"=>'L. '.$row['descuento'],
			"nc"=>'L. '.$row['nc'],
			"total"=>'L. '.$row['total'],
			"categoria"=>$row['categoria'],
			"observacion"=>$row['observacion'],
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
?>