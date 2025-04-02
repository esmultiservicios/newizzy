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
	$facturas_id = $_POST['facturas_id'];
	$comentario = $_POST['comentario'];

	//ANULAMOS LA FACTURA DEL
	$query = $insMainModel->anular_factura($facturas_id);

	if($query){
		//CONSULTAMOS SI HAY PAGO APLICADO A LA FACTURA DEL
		$resultPagos = $insMainModel->valid_pago_factura($facturas_id);

		if($resultPagos->num_rows>0){
			//ANULAMOS EL PAGO
			$insMainModel->anular_pago_factura($facturas_id);
			
			//CONSULTAMOS LA FACTURA PARA GUARDAR EL HISTORIAL
			$resultNumFactura = $insMainModel->getNumeroFactura($facturas_id);
			
			if($resultNumFactura->num_rows>0){
				$consulta2 = $resultNumFactura->fetch_assoc();
				$prefijo = $consulta2['prefijo'];
				$no_factura = $consulta2['prefijo'].''.str_pad($consulta2['numero'], $consulta2['relleno'], "0", STR_PAD_LEFT);
				
				//GUARDAMOS EL HISTORIAL
				$datos = [
					"modulo" => "Facturación",
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],
					"status" => "Anulada",
					"observacion" => "El número de factura $no_factura ha sido anulada correctamente segun comentario: $comentario",				
				];
			
				$insMainModel->guardarHistorial($datos);
			}
		}

		echo 1;//FACTURA ANULADA
	}else{
		echo 2; //ERROR AL ANULAR LA FACTURA
	}
	
?>