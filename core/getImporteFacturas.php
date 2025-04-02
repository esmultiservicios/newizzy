<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$factura_id = $_POST['factura_id'];
	
	$result = $insMainModel->getImporteFacturas($factura_id);

	$no_factura = "";
	$importe = "";	

	if($result->num_rows>=0){
		$factura = $result->fetch_assoc();
		$importe = number_format($factura['importe'],2);
    }	
	
	$datos = array(
		0 => $importe,							
	);
	echo json_encode($datos);
?>	