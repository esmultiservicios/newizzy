<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$compras_id = $_POST['compras_id'];
	
	$result = $insMainModel->getImporteCompras($compras_id);

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