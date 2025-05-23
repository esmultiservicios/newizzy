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
	
	$categoria = $_POST['categoria'];
	
	$datos = [
		"categoria" => $categoria,
		"empresa_id_sd" => $_SESSION['empresa_id_sd']	
	];
	
	$result = $insMainModel->getProductosMovimientos($datos);
	
	$arreglo = array();
	$data = array();

	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"productos_id"=>$row['productos_id'],
			"colaborador_id"=>$row['colaborador_id'],
			"nombre"=>$row['nombre'],
			"cantidad"=>$row['cantidad'],
			"medida"=>$row['medida'],
			"categoria"=>$row['tipo_producto'],
			"precio_venta"=>$row['precio_venta'],
			"almacen"=>$row['almacen']			
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