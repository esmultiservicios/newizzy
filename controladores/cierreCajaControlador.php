<?php
    if($peticionAjax){
        require_once "../modelos/aperturaCajaModelo.php";
    }else{
        require_once "./modelos/aperturaCajaModelo.php";
    }
	
	class cierreCajaControlador extends aperturaCajaModelo{
		public function cerrar_caja_controlador(){
			$colaboradores_id_apertura = $_POST['colaboradores_id_apertura'];	
			$monto_apertura = 0;		
			$fecha_apertura = $_POST['fecha_apertura'];
			$estado = 2;
			$fecha_registro = date("Y-m-d H:i:s");
			$fecha = date("Y-m-d");
			$factura_inicial = "";
			$factura_final = "";
			$neto = 0;
			$tipo_ingreso = 1;//INGRESOS POR VENTAS				
			
			$datos_apertura = [
				"colaboradores_id" => $colaboradores_id_apertura,
				"fecha" => $fecha_apertura				
			];
						
			//CONSULTAMOS QUE LA CAJA ESTE ABIERTA
			$resultVarios = aperturaCajaModelo::valid_apertura_caja_modelo($datos_apertura);			
			$apertura_id = 0;

			if($resultVarios->num_rows>0){
				$consultaAperturaCaja = $resultVarios->fetch_assoc();
				$apertura_id = $consultaAperturaCaja['apertura_id'];

				//CONSULTAMOS LA PRIMER FACTURA ELABORADA						
				$resultFacturaInicial = aperturaCajaModelo::consultar_factura_inicial($apertura_id);

				if($resultFacturaInicial->num_rows>0){
					$consultaFacturaInicial = $resultFacturaInicial->fetch_assoc();
					$no_facturaInicial = $consultaFacturaInicial['prefijo']."".str_pad($consultaFacturaInicial['numero'], $consultaFacturaInicial['relleno'], "0", STR_PAD_LEFT);
					$factura_inicial = $no_facturaInicial;					
				}
				
				//CONSULTAMOS LA ULTIMA FACTURA ELABORADA
				$resultFacturaInicial = aperturaCajaModelo::consultar_factura_final($apertura_id);			
				
				if($resultFacturaInicial->num_rows>0){
					$consultaFacturaFinal = $resultFacturaInicial->fetch_assoc();
					$no_facturaFinal = $consultaFacturaFinal['prefijo']."".str_pad($consultaFacturaFinal['numero'], $consultaFacturaFinal['relleno'], "0", STR_PAD_LEFT);
					$factura_final = $no_facturaFinal;
				}	
				
				//CONSULTAMOS TODAS LAS FACTURAS QUE SE PROCESARON BAJO EL MISMO NUMERO DE APERTURA
				$resultFacturaFinalNeto = aperturaCajaModelo::consulta_facturas($apertura_id);
				
				$total_despues_isv = 0;
				$total = 0;
				$descuentos = 0;
				$isv_neto = 0;
				$importe_gravado = 0;
				$importe_excento = 0;
				$subtotal = 0;

				while($data = $resultFacturaFinalNeto->fetch_assoc()){
					$facturas_id = $data['facturas_id'];

					//CONSULTAMOS EL TOTAL DE LA FACTURA
					$result_factura_detalle = aperturaCajaModelo::consulta_detalles_facturas($facturas_id);

					while($registro_detalles = $result_factura_detalle->fetch_assoc()){
						$total += ($registro_detalles["precio"] * $registro_detalles["cantidad"]);
						$descuentos += $registro_detalles["descuento"];
						$isv_neto += $registro_detalles["isv_valor"];
						
						if($registro_detalles["isv_valor"] > 0){
							$importe_gravado += ($registro_detalles["precio"] * $registro_detalles["cantidad"]);
						}else{
							$importe_excento += ($registro_detalles["precio"] * $registro_detalles["cantidad"]);
						}
					}
					$subtotal = $importe_gravado + $importe_excento;
					$total_despues_isv = ($total + $isv_neto) - $descuentos;
				}		

				$datos = [
					"colaboradores_id" => $colaboradores_id_apertura,
					"fecha" => $fecha_apertura,
					"factura_inicial" => $factura_inicial,
					"factura_final" => $factura_final,
					"monto" => $monto_apertura,
					"neto" => $total_despues_isv,				
					"estado" => $estado,
					"fecha_registro" => $fecha_registro,				
				];
				
				$query = aperturaCajaModelo::cerrar_caja_modelo($datos);
				
				if($query){
					//AGREGAMOS EL MOVIMIENTO DEL CIERRE DE CAJA Y LO SEPARAMOS POR EL TIPO DE CUENTA
					$resultFacturaMontoTipoPago = mainModel::getMontoTipoPago($apertura_id);

					while($dataMontoTipoPago = $resultFacturaMontoTipoPago->fetch_assoc()){
						$cuentas_id = $dataMontoTipoPago['cuentas_id'];
						$total_despues_isvMontoTipoPago = $dataMontoTipoPago['monto'];

						//OBTENEMOS EL PORCENTAJE DE ISV
						$porcentajeconsulta = mainModel::getISV("Facturas")->fetch_assoc();
						$porcentaje_isv = $porcentajeconsulta['valor'];
						$total_antes_isvMontoTipoPago = (float)$total_despues_isvMontoTipoPago / (((float)$porcentaje_isv/100) + 1 );
						$isv_neto = $total_despues_isvMontoTipoPago - $total_antes_isvMontoTipoPago;
						$descuentos = 0;

						/*#####################################################################*/
						if(!isset($_SESSION)){
							session_start(['name'=>'SD']); 
						}

						$clientes_id = 1;
						$consulta_nombre_ingresos_contabilidad = aperturaCajaModelo::getNombreClienteModelo($clientes_id)->fetch_assoc();
						if ($consulta_nombre_ingresos_contabilidad) {
							$recibide = $consulta_nombre_ingresos_contabilidad['nombre'];
						} else {
							// Manejar el caso cuando $consulta_nombre_ingresos_contabilidad es null
							$recibide = '';
						}

						$nc = 0;						
						$empresa_id = $_SESSION['empresa_id_sd'];
						$colaboradores_id = $_SESSION['colaborador_id_sd'];
						$observacion = "Ingresos por venta Cierre de Caja";
						$estado = 1;

						//AGREGAMOS EL INGRESO DE VENTA
						$datosMontoTipoPago = [
							"clientes_id" => $clientes_id,
							"cuentas_id" => $cuentas_id,
							"empresa_id" => $empresa_id,
							"fecha" => $fecha,
							"factura" => $apertura_id,
							"subtotal" => $total_antes_isvMontoTipoPago,
							"isv" => $isv_neto,
							"descuento" => $descuentos,
							"nc" => $nc,
							"total" => $total_despues_isvMontoTipoPago,
							"observacion" => $observacion,
							"estado" => $estado,
							"fecha_registro" => $fecha_registro,
							"colaboradores_id" => $colaboradores_id_apertura,
							"tipo_ingreso" => $tipo_ingreso,
							"recibide" => $recibide				
						];
						
						$resultIngresos = aperturaCajaModelo::valid_ingreso_cuentas_modelo($datosMontoTipoPago);
						aperturaCajaModelo::agregar_ingresos_contabilidad_modelo($datosMontoTipoPago);

						//CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
						$consulta_ingresos_contabilidad = aperturaCajaModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
						if ($consulta_ingresos_contabilidad) {
							$saldo_consulta = $consulta_ingresos_contabilidad['saldo'];
						} else {
							// Manejar el caso cuando $consulta_ingresos_contabilidad es null
							$saldo_consulta = 0;
						}
						
						$ingreso = $total_despues_isvMontoTipoPago;
						$egreso = 0;
						$saldo = $saldo_consulta + $ingreso;
						
						//AGREGAMOS LOS MOVIMIENTOS DE LA CUENTA
						$datos_movimientos = [
							"cuentas_id" => $cuentas_id,
							"empresa_id" => $empresa_id,
							"fecha" => $fecha,
							"ingreso" => $ingreso,
							"egreso" => $egreso,
							"saldo" => $saldo,
							"colaboradores_id" => $colaboradores_id,
							"fecha_registro" => $fecha_registro,				
						];
						
						aperturaCajaModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);					
				}					

					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "Caja cerrada correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formAperturaCaja",
						"id" => "proceso_aperturaCaja",
						"valor" => "Registro",
						"funcion" => "validarAperturaCajaUsuario();getCajero();printComprobanteCajas($apertura_id);listar_registro_cajas();",
						"modal" => "modal_apertura_caja",	
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];					
				}
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Error al cerrar la caja",
					"text" => "Lo sentimos, la caja no se encuentra abierta",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}

			return mainModel::sweetAlert($alert);
		}
	}