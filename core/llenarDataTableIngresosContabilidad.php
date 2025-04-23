<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();

	$datos = [
		"estado" => $_POST['estado'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];		

	$result = $insMainModel->getIngresosContables($datos);

	$arreglo = array();
	$data = array();

	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"ingresos_id"=>$row['ingresos_id'],
			"fecha_registro"=>$row['fecha_registro'],
			"fecha"=>$row['fecha'],
			"nombre"=>$row['nombre'],
			"cliente"=>$row['cliente'],
			"factura"=>$row['factura'],
			"subtotal"=>'L. '.$row['subtotal'],
			"impuesto"=>'L. '.$row['impuesto'],
			"descuento"=>'L. '.$row['descuento'],
			"nc"=>'L. '.$row['nc'],
			"total"=>'L. '.$row['total'],
			"recibide"=>$row['recibide'],
			"tipo_ingreso"=>$row['tipo_ingreso'],
			"observacion"=>$row['observacion'],
		);	
	}

	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data,
		"test" => 'esto es una prueba'
	);

	echo json_encode($arreglo);
?>