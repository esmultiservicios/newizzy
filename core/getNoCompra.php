<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$compras_id = $_POST['compras_id'];
	
	$result = $insMainModel->getNumeroCompra($compras_id);

	$no_compra = "";

	if($result->num_rows>=0){
		$compra = $result->fetch_assoc();
		$no_compra = $compra['numero'];
    }	
	
	$datos = array(
		0 => $no_compra,					
	);
	
	echo json_encode($datos);