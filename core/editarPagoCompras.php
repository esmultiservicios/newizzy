<?php
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$compras_id = $_POST['compras_id'];

	//CONSULTAR DATOS DEL METODO DE PAGO
	$result = $insMainModel->getDatosCompras($compras_id); 
     
	$proveedor = "";
	$proveedores_id = "";
	$fecha_compra = "";
	$compras_id = "";
	$importe = 0;
	$saldo = 0;
	$estado = 0;

	//OBTENEMOS LOS VALORES DEL REGISTRO
	if($result->num_rows>0){
		$consulta_registro = $result->fetch_assoc(); 
		$proveedor = $consulta_registro['proveedor'];
		$proveedores_id = $consulta_registro['proveedores_id'];
		$fecha_compra = $consulta_registro['fecha_compra'];
		$compras_id = $consulta_registro['compras_id'];	
		$estado = $consulta_registro['tipo_compra'];
		$saldo = floatval($consulta_registro['saldo']);	
	}

	$result_compra = $insMainModel->getDetalleProductosCompras($compras_id);

	while($registro2 = $result_compra->fetch_assoc()){
		$cantidad = $registro2['cantidad'];
		$precio = $registro2['precio'];
		$descuento = $registro2['descuento'];
		$isv_valor = $registro2['isv_valor'];
		$importe += (($cantidad * $precio) - $descuento) + $isv_valor;
	}	

	$datos = array(
		 0 => $proveedor,
		 1 => $proveedores_id,		 
		 2 => $fecha_compra, 
		 3 => $importe,
		 4 => $compras_id,	
		 5 => $estado,
		 6 => $saldo
	);	
	echo json_encode($datos);
?>