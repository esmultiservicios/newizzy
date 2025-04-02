<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$insMainModel = new mainModel();

	$datos = [
		"tipo_compra_reporte" => $_POST['tipo_compra_reporte'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],	
		"empresa_id_sd" => $_SESSION['empresa_id_sd'],		
	];	
	
	$result = $insMainModel->consultaCompras($datos);
	
	$arreglo = array();
	$data = [];
		
	while($row = $result->fetch_assoc()){
	   $compras_id = $row['compras_id'];
	   $result_detalle_compras = $insMainModel->getDetalleCompra($row['compras_id']);
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

	   if($row['tipo_documento'] == 'Contado'){
			$color = 'bg-c-green';
	   }

	   if($row['tipo_documento'] == 'Crédito'){
			//CONSULTAMOS LOS PAGOS DEL CLIENTE
			$result_cxpFacturaCompraPago = $insMainModel->consultaCXPagoFacturaCompras($compras_id);

			if($result_cxpFacturaCompraPago->num_rows>0){
				$color = 'bg-c-green';
			}else{
				$color = 'bg-c-yellow';
			}			
	   }	   

	   $data[] = array( 
		  "compras_id"=>$row['compras_id'],
		  "fecha"=>$row['fecha'],
		  "tipo_documento"=>$row['tipo_documento'],
		  "proveedor"=>$row['proveedor'],
		  "numero"=>$row['numero'],
		  "subtotal"=>$subtotal,	
		  "isv"=>$isv,	
		  "descuento"=>$descuento,
		  "total"=>$total	,
		  "color"=> $color, 
		  "cuenta"=>$row['cuenta']		  
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