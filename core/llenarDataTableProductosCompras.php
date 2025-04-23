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

	$bodega = '';

	if(isset($_POST['bodega'])){
		$bodega = $_POST['bodega'];
	}

	$datos = [
		"bodega" => $bodega,
		"barcode" => '',	
		"empresa_id_sd" => $_SESSION['empresa_id_sd']
	];
	
	$result = $insMainModel->getProductosCantidadCompras($datos);
	
	$arreglo = array();
	$data = array();
	
	$entradaH = 0;
	$salidaH = 0;
	
	while($row = $result->fetch_assoc()){	
		$result_productos = $insMainModel->getCantidadProductos($row['productos_id']);	
		if($result_productos->num_rows>0){
			while($consulta = $result_productos->fetch_assoc()){
				$data[] = array( 
					"productos_id"=>$row['productos_id'],
					"barCode"=>$row['barCode'],
					"nombre"=>$row['nombre'],
					"medida"=>$row['medida'],
					"tipo_producto_id"=>$row['tipo_producto_id'],
					"tipo_producto"=>$row['tipo_producto'],
					"image"=>$row['image'],
					"precio_compra"=>$row['precio_compra'],
				);
			}
		}			
	}
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);
?>