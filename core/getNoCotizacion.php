<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$cotizacion_id = $_POST['cotizacion_id'];
	
	$result = $insMainModel->getNumeroCotizacion($cotizacion_id);

	$no_factura = "";
	$cliente = "";	

	if($result->num_rows>=0){
		$factura = $result->fetch_assoc();
		$cliente =$factura['cliente'];
		$no_factura =$factura['numero']." (Cliente: ".$cliente.")";
    }	
	
	$datos = array(
		0 => $no_factura,							
	);
	
	echo json_encode($datos);
