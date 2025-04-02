<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$factura_id = $_POST['factura_id'];
	
	$result = $insMainModel->getNombreClienteFactura($factura_id);

	$no_factura = "";
	$cliente = "";	

	if($result->num_rows>=0){
		$factura = $result->fetch_assoc();
		$cliente =$factura['nombre'];
    }	
	
	$datos = array(
		0 => $cliente,							
	);
	
	echo json_encode($datos);
?>	