<?php	
 	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();
	
	$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;
	
	$datos = [
		"estado" => $estado,	
		"empresa_id_sd" => $_SESSION['empresa_id_sd']
	];
	
	$result = $insMainModel->getProductos($datos);
	
	$arreglo = array();
	$data = array();
	$saldo_productos = 0;
	
	while($row = $result->fetch_assoc()){		
		$result_movimientos = $insMainModel->getSaldoProductosMovimientos($row['productos_id']);
		if($result_movimientos->num_rows>0){
		$consulta = $result_movimientos->fetch_assoc();
		$saldo_productos = $consulta['saldo'];
		}

		$data[] = array( 
			"productos_id"=>$row['productos_id'],
			"image"=>$row['image'],
			"barCode"=>$row['barCode'],
			"nombre"=>$row['nombre'],
			"medida"=>$row['medida'],
			"categoria"=>$row['categoria'],
			"precio_compra"=> $row['precio_compra'],
			"precio_venta"=> $row['precio_venta'],
			"isv_venta"=> $row['isv_venta'],
			"isv_compra"=> $row['isv_compra'],
			"porcentaje_venta"=> $row['porcentaje_venta']				 			
		);			
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo); 