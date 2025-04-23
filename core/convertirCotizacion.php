<?php
	header("Content-Type: text/html;charset=utf-8");
	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}

	date_default_timezone_set('America/Tegucigalpa');
	$cotizacion_id = $_POST['cotizacion_id'];
	$usuario = $_SESSION['colaborador_id_sd'];
	$empresa_id = $_SESSION['empresa_id_sd'];	
	$tipo_factura = 1; //CONTADO
	$fecha = date("Y-m-d");
	$fecha_registro = date("Y-m-d H:i:s");

	$secuenciaFacturacion = $insMainModel->secuencia_facturacion($empresa_id)->fetch_assoc();
	$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
	$numero = $secuenciaFacturacion['numero'];
	$incremento = $secuenciaFacturacion['incremento'];
	$no_factura = $secuenciaFacturacion['prefijo']."".str_pad($secuenciaFacturacion['numero'], $secuenciaFacturacion['relleno'], "0", STR_PAD_LEFT);

	//CONSULTA ENCABEZADO DE COTIZACION PARA GUARDAR EN EL ENCABEZADO DE LA FACTURA
	$consultaCotizacion = $insMainModel->getCotizacionFactura($cotizacion_id)->fetch_assoc();
	$clientes_id = $consultaCotizacion['clientes_id'];
	$colaborador_id = $consultaCotizacion['colaboradores_id'];
	$importe = $consultaCotizacion['importe'];
	$notas = $consultaCotizacion['notas'];

	//CONSULTAMOS LA APERTURA
	$datos_apertura = [
		"colaboradores_id" => $usuario,
		"fecha" => $fecha,
		"estado" => 1,
	];

	$apertura = $insMainModel->getAperturaID($datos_apertura)->fetch_assoc();
	$apertura_id = $apertura['apertura_id'];

	//INGRESAMOS LOS DATOS EN EL ENCABEZADO DE LA FACTURA
	$facturas_id = $insMainModel->correlativoEntidades("facturas_id", "facturas");
	$estado = 1;//PENDIENTE DE PAGO

	$datos = [
		"facturas_id" => $facturas_id,
		"clientes_id" => $clientes_id,
		"secuencia_facturacion_id" => $secuencia_facturacion_id,
		"apertura_id" => $apertura_id,				
		"tipo_factura" => $tipo_factura,				
		"numero" => $numero,
		"colaboradores_id" => $colaborador_id,
		"importe" => $importe,
		"notas" => $notas,
		"fecha" => $fecha,				
		"estado" => $estado,
		"usuario" => $usuario,
		"fecha_registro" => $fecha_registro,
		"fecha_dolar" => $fecha_registro,
		"empresa_id" => $empresa_id
	];		

	$query = $insMainModel->agregar_facturas($datos);

	if($query){
		//CONSULTAMOS LOS DETALLES DE LA COTIZACION
		$result_cotizacion_detalle = $insMainModel->getCotizacionDetallesFactura($cotizacion_id);

		while($registro_detalles = $result_cotizacion_detalle->fetch_assoc()){
			$productos_id = $registro_detalles['productos_id'];
			$quantity = $registro_detalles['cantidad'];
			$price = $registro_detalles['precio'];
			$isv_valor = $registro_detalles['isv_valor'];
			$discount = $registro_detalles['descuento'];
			$medida = "";
			
			//CONSULTAR LA MEDIDA DEL PRODUCTO
			$result_medida = $insMainModel->getMedidaProductoPadre($productos_id);

			if ($result_medida->num_rows > 0) {
				$row = $result_medida->fetch_assoc(); // ✅ Extraer la fila
				$medida = $row['medida']; // ✅ Obtener el valor correcto
			}			
			
			$datos_detalles_facturas = [
				"facturas_id" => $facturas_id,
				"productos_id" => $productos_id,
				"cantidad" => $quantity,				
				"precio" => $price,
				"isv_valor" => $isv_valor,
				"descuento" => $discount,	
				"medida" => $discount,
			];
			
			$insMainModel->agregar_detalle_facturas($datos_detalles_facturas);
		}

		//CAMBIAR ESTADO DE LA COTIZACION
		$insMainModel->actualizarCotizacionFactura($cotizacion_id);

		$datos = array(
			0 => 1,
			1 => $facturas_id,		
		);
	}else{
		//ERROR AL CONVERTIR LA FACTURA
		$datos = array(
			0 => 2,
			1 => '',		
		);
	}

	echo json_encode($datos);