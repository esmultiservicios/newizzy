<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$factura_id = $_POST['factura_id'];

    $result = $insMainModel->abonos_cxc_cliente($factura_id);
    $total_abono = 0;
	$arreglo = array();
	$data = array();
		
	while($row = $result->fetch_assoc()){		
        $total_abono += $row['abono'];

		$no_factura = $row['prefijo'].str_pad( $row['numero'], $row['relleno'], "0", STR_PAD_LEFT);		

		$data[] = array( 
			"facturas_id"=>$row['facturas_id'],
			"fecha"=>$row['fecha'],
			"abono"=>number_format($row['abono'],2),						
			"cliente"=> $row['cliente'],
			"descripcion"=>$row['descripcion1'],
			"tipo_pago"=>$row['tipo_pago'],
			"importe"=>number_format($row['importe'],2),
            "total"=> number_format($total_abono ,2),
			"no_factura"=>$no_factura,
			"usuario"=>$row['usuario'],
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