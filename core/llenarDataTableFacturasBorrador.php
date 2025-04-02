<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];	
	
	$result = $insMainModel->consultaBillDraft($datos);
	
	$arreglo = array();
	$data = [];
	
	while($row = $result->fetch_assoc()){
	   $facturas_id = $row['facturas_id'];
	   $result_detalle_venta = $insMainModel->getDetalleFactura($row['facturas_id']);
	   $subtotal = 0;
	   $isv = 0;
	   $descuento = 0;
	   $total = 0;
	   
	   while($row1 = $result_detalle_venta->fetch_assoc()){
			$subtotal += ($row1['precio'] * $row1['cantidad']);
			$isv += $row1['isv_valor'];
			$descuento += $row1['descuento'];
	   }
	   
	   $subtotal = number_format($subtotal,2);
	   $isv = number_format($isv,2);
	   $descuento = number_format($descuento,2);
	   $total = $row['total'];

	   if($row['numero_factura'] == 0){
		$numero = "Aún no se ha generado";		
		}else{
			$numero = $row['numero'];
		}

	   $data[] = array( 
		  "facturas_id"=>$row['facturas_id'],
		  "fecha"=>$row['fecha'],
		  "tipo_documento"=>$row['tipo_documento'],
		  "cliente"=>$row['cliente'],
		  "numero"=>$numero,
		  "subtotal"=>'L. '.$subtotal,	
		  "isv"=>'L. '.$isv,	
		  "descuento"=>'L. '.$descuento,
		  "total"=>'L. '.$total	  
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