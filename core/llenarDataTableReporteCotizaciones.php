<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}

	$datos = [
		"tipo_cotizacion_reporte" => $_POST['tipo_cotizacion_reporte'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
		"empresa_id_sd" => $_SESSION['empresa_id_sd'],
	];	
	
	$result = $insMainModel->consultaCotizaciones($datos);
	
	$arreglo = array();
	$data = [];
		
	while($row = $result->fetch_assoc()){
	   $cotizacion_id = $row['cotizacion_id'];
	   $result_detalle_compras = $insMainModel->getDetalleCotizaciones($row['cotizacion_id']);
	   $subtotal = 0;
	   $isv = 0;
	   $descuento = 0;
	   $total = 0;
	   
	   while($row1 = $result_detalle_compras->fetch_assoc()){
			$subtotal += ($row1['precio'] * $row1['cantidad']);
			$isv += $row1['isv_valor'];
			$descuento += $row1['descuento'];
	   }
	   
	   $subtotal = $subtotal;
	   $isv = $isv;
	   $descuento = $descuento;
	   $total = $row['total'];

	   $data[] = array( 
		  "cotizacion_id"=>$row['cotizacion_id'],
		  "fecha"=>$row['fecha'],
		  "tipo_documento"=>$row['tipo_documento'],
		  "cliente"=>$row['cliente'],
		  "numero"=>$row['numero'],
		  "subtotal"=>$subtotal,	
		  "isv"=>$isv,	
		  "descuento"=>$descuento,
		  "total"=>$total	    
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