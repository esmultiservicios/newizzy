<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$compras_id = $_POST['compras_id'];
	
	$result = $insMainModel->getNombreClienteFacturaCompras($compras_id);

	$no_factura = "";
	$proveedor = "";	

	if($result->num_rows>=0){
		$factura = $result->fetch_assoc();
		$proveedor =$factura['nombre'];
    }	
	
	$datos = array(
		0 => $proveedor,							
	);
	
	echo json_encode($datos);
?>	