<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$facturas_id = $_POST['facturas_id'];
	
	$result = $insMainModel->getNumeroFactura($facturas_id);

	$no_factura = "";

	if($result->num_rows>=0){
		$factura = $result->fetch_assoc();
		$no_factura = $factura['prefijo'].''.$insMainModel->rellenarDigitos($factura['numero'],$factura['relleno']);
    }	
	
	$datos = array(
		0 => $no_factura,					
	);
	
	echo json_encode($datos);