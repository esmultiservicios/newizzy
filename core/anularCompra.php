<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}

	$insMainModel = new mainModel();

	date_default_timezone_set('America/Tegucigalpa');
	$compras_id = $_POST['compras_id'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->anular_compra($compras_id);

	if($query){
		//CONSULTAMOS SI HAY PAGO APLICADO A LA FACTURA DEL
		$resultPagos = $insMainModel->valid_pago_compras($compras_id);

		if($resultPagos->num_rows>0){
			//ANULAMOS EL PAGO
			$insMainModel->anular_pago_compras($compras_id);
		}

		echo 1;//FACTURA ANULADA
	}else{
		echo 2; //ERROR AL ANULAR LA FACTURA
	}
	
?>