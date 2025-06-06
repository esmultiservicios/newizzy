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
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
		"productos_id" => $_POST['productos_id'],
		"colaboradores_id" => $_POST['colaboradores_id'],
		"empresa_id_sd" => $_SESSION['empresa_id_sd'],
	];	

	$result = $insMainModel->GetDetalleVentas($datos);
	
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"numero"=>$row['numero'],
			"Producto"=>$row['Producto'],
			"Precio"=>$row['Precio'],
			"Cantidad"=>$row['Cantidad'],
			"ISV"=>$row['ISV'],
			"Descuento"=>$row['Descuento'],
			"Total"=>$row['Total'],
			"Vendedor"=>$row['Vendedor']		  
		);		
	}
	
	$response = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($response);