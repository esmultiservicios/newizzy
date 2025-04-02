<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$proveedores_id = $_POST['proveedores_id'];
	
	$result = $insMainModel->getNombreProveedor($proveedores_id);

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