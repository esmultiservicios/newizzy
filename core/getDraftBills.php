<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$facturas_id = $_POST['facturas_id'];
	$result = $insMainModel->getDetalleFactura($facturas_id);

	$arreglo = array();

	while( $row = $result->fetch_assoc()){
	  $arreglo[] = $row;  
	}	
	
	echo json_encode($arreglo);	