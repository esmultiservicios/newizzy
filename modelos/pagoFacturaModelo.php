<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class pagoFacturaModelo extends mainModel{
		protected function agregar_pago_factura_modelo($datos){

			$importe = $datos['importe'];

			if($datos['abono']>0){
				$importe = $datos['abono'];
			}

			$pagos_id = mainModel::correlativo("pagos_id", "pagos");
			$insert = "INSERT INTO pagos 
				VALUES('$pagos_id','".$datos['facturas_id']."','".$datos['tipo_pago_id']."','".$datos['fecha']."',
				'".$importe."','".$datos['efectivo']."','".$datos['cambio']."','".$datos['tarjeta']."',
				'".$datos['usuario']."','".$datos['estado']."','".$datos['empresa']."','".$datos['fecha_registro']."')";				
			
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $result;		
		}
		
		protected function agregar_pago_detalles_factura_modelo($datos){	

			$pagos_detalles_id = mainModel::correlativo("pagos_detalles_id", "pagos_detalles");
			$insert = "INSERT INTO pagos_detalles 
				VALUES('$pagos_detalles_id','".$datos['pagos_id']."','".$datos['tipo_pago_id']."','".$datos['banco_id']."','".$datos['efectivo']."','".$datos['descripcion1']."','".$datos['descripcion2']."','".$datos['descripcion3']."')";

			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
		
			return $result;			
		}
		
		protected function cancelar_pago_modelo($pagos_id){
			$estado = 2;//Pago CANCELADA
			$update = "UPDATE pagos
				SET
					estado = '$estado'
				WHERE pagos_id = '$pagos_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function consultar_codigo_pago_modelo($facturas_id){
			$query = "SELECT pagos_id
				FROM pagos
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}

		protected function consultar_numero_factura_pago_modelo($facturas_id){
			$query = "SELECT number
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}		

		protected function getLastInserted(){
			$query = "SELECT MAX(pagos_id) AS id
			FROM pagos";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;			
		}
		
		protected function update_status_factura($facturas_id){
			$estado = 2;//FACTURA PAGADA
			$update = "UPDATE facturas
				SET
					estado = '$estado'
				WHERE facturas_id = '$facturas_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
		
			return $result;					
		}	

		protected function update_status_factura_cuentas_por_cobrar($facturas_id,$estado = 2,$importe = ''){ //DONDE 2 ES PAGO REALIZADO			
			if($importe != '' || $importe == 0){
				$importe = ', saldo = '.$importe;
			}

			$update = "UPDATE cobrar_clientes
				SET
					estado = '$estado'
					$importe
				WHERE facturas_id = '$facturas_id'";
			
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			return $result;					
		}
		
		protected function consultar_factura_cuentas_por_cobrar($facturas_id){
			$query = "SELECT *
				FROM cobrar_clientes
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			return $result;				
		}	

		protected function consultar_factura_fecha($facturas_id){
			$query = "SELECT fecha
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function consultar_tipo_factura($facturas_id){
			$query = "SELECT tipo_factura
				FROM facturas
				WHERE facturas_id = '$facturas_id'";

			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;	
		}

		protected function consultar_numero_factura($facturas_id){
			$query = "SELECT number, secuencia_facturacion_id
				FROM facturas
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;	
		}

		protected function valid_pagos_factura($facturas_id){
			$query = "SELECT pagos_id
				FROM pagos
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}
		
		protected function valid_pagos_detalles_facturas($pagos_id, $tipo_pago){
			$query = "SELECT pagos_detalles_id
					FROM pagos_detalles
					WHERE pagos_id = '$pagos_id' AND tipo_pago_id = '$tipo_pago'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;				
		}

		protected function secuencia_facturacion_modelo($empresa_id, $documento_id){
			$query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS 'numero', rango_final, fecha_limite, incremento, relleno
			   FROM secuencia_facturacion
			   WHERE activo = '1' AND empresa_id = '$empresa_id' AND documento_id = '$documento_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;
		}	

		protected function consulta_cuenta_pago_modelo($tipo_pago_id){
			$query = "SELECT cuentas_id
			   FROM tipo_pago
			   WHERE tipo_pago_id = '$tipo_pago_id'";
			$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $result;
		}			
		
		protected function actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero){
			$update = "UPDATE secuencia_facturacion
				SET
					siguiente = '$numero'
				WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;				
		}	

		protected function actualizar_estado_factura_proforma_pagos_modelo($facturas_id){
			$update = "UPDATE facturas_proforma
				SET
					estado = '1'
				WHERE facturas_id = '$facturas_id'";
			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;				
		}	
		
		protected function actualizar_factura($datos){
			$update = "UPDATE facturas
			SET
				estado = '".$datos['estado']."'
			WHERE facturas_id = '".$datos['facturas_id']."'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;					
		}

		protected function actualizar_Secuenciafactura_PagoModelo($datos){
			$update = "UPDATE facturas
			SET
				secuencia_facturacion_id = '".$datos['secuencia_facturacion_id']."',
				number = '".$datos['number']."',
				fecha = CURDATE()
			WHERE facturas_id = '".$datos['facturas_id']."'";

			$result = mainModel::connection()->query($update) or die(mainModel::connection()->error);	

			return $result;					
		}		
		
	    //METODO QUE PERMITE AGREGAR EL INGRESO DEL PAGO DEL CLIENTE
		protected function agregar_ingresos_contabilidad_pagos_modelo($datos){	
			$ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");		
			$insert = "INSERT INTO ingresos VALUES('".$ingresos_id."','".$datos['cuentas_id']."','".$datos['clientes_id']."','".$datos['empresa_id']."','".$datos['tipo_ingreso']."','".$datos['fecha']."','".$datos['factura']."','".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."','".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['recibide']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function agregar_movimientos_contabilidad_pagos_modelo($datos){
			$movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
			$insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentas_id){
			$query = "SELECT ingreso, egreso, saldo
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id'
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function consultar_numero_factura_modelo($facturas_id){
			$query = "SELECT number FROM facturas WHERE facturas_id = '$facturas_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function consultar_factura_proforma_pagos_modelo($facturas_id){
			$result = mainModel::getConsultaFacturaProforma($facturas_id);
			
			return $result;			
		}		

		//funcion para realizar todos lo pagos de factura
		protected function agregar_pago_factura_base($res){	
			$existeProforma = 0;
			$proformaNombre = "Factura Electronica";			

			//CONSULTAMOS SI EXISTE FACTURA PROFORMA
			$resultTotalPadre = pagoFacturaModelo::consultar_factura_proforma_pagos_modelo($res['facturas_id']);

			if($resultTotalPadre->num_rows>0) {
				$existeProforma = 1;
				$proformaNombre = "Factura Proforma";
			}

			//SI EL PAGO QUE SE ESTA REALIZANDO ES DE UN DOCUMENTO AL CREDITO
			if($res['estado_factura'] == 2 || $res['multiple_pago'] == 1){//SI ES CREDITO ESTO ES UN ABONO A LA FACTURA
				$saldo_credito = 0;
				$nuevo_saldo = 0;
				
				//consultamos a la tabla cobrar cliente
				$get_cobrar_cliente = pagoFacturaModelo::consultar_factura_cuentas_por_cobrar($res['facturas_id']);
		
				if($get_cobrar_cliente->num_rows == 0){
					echo 'error';
				}else{
					$rec = $get_cobrar_cliente->fetch_assoc();
					$saldo_credito = $rec['saldo'];
				}
	
				//validar que no se hagan mas abonos que el importe
				if($res['abono'] <= $saldo_credito ){
					//update tabla cobrar cliente
					if($res['abono'] == $saldo_credito){
						//actualizamos el estado a pagado (2)
						$nuevo_saldo = 0;
						$put_cobrar_cliente = pagoFacturaModelo::update_status_factura_cuentas_por_cobrar($res['facturas_id'],2,0);
												
						//ACTUALIZAMOS EL ESTADO DE LA FACTURA
						pagoFacturaModelo::update_status_factura($res['facturas_id']);
					}else{
						$nuevo_saldo = $saldo_credito - $res['abono'];
						$put_cobrar_cliente = pagoFacturaModelo::update_status_factura_cuentas_por_cobrar($res['facturas_id'],1,$nuevo_saldo);
					}
	
					$query = pagoFacturaModelo::agregar_pago_factura_modelo($res);					
	
					if($query){
						//ACTUALIZAMOS EL DETALLE DEL PAGO
						$consulta_pago = pagoFacturaModelo::getLastInserted()->fetch_assoc();

						$pagos_id = $consulta_pago['id'];
													
						$datos_pago_detalle = [
							"pagos_id" => $pagos_id,
							"tipo_pago_id" => $res['tipo_pago_id'],
							"banco_id" => $res['banco_id'],
							"efectivo" => $res['importe'],
							"descripcion1" => $res['referencia_pago1'],
							"descripcion2" => $res['referencia_pago2'],
							"descripcion3" => $res['referencia_pago3'],
						];	
						
						$result_valid_pagos_detalles_facturas = pagoFacturaModelo::valid_pagos_detalles_facturas($pagos_id, $res['tipo_pago_id']);
						
						pagoFacturaModelo::agregar_pago_detalles_factura_modelo($datos_pago_detalle);
						/**###########################################################################################################*/
						//INGRESAMOS LOS DATOS DEL PAGO EN LA TABLA ingresos
						//CONSULTAMOS LA CUENTA DONDE SE ENLZARA CON EL PAGO
						$consulta_cuenta_ingreso = self::consulta_cuenta_pago_modelo($res['tipo_pago_id'])->fetch_assoc();
						$cuentas_id = $consulta_cuenta_ingreso['cuentas_id'];					
						$empresa_id = $res['empresa'];

						//CONSULTAMOS EL NUMERO DE FACTURA QUE ESTAMOS PAGANDO O ABONANDO
						$consulta_factura = mainModel::getFactura($res['facturas_id'])->fetch_assoc();
						$no_factura = str_pad($consulta_factura['numero_factura'], $consulta_factura['relleno'], "0", STR_PAD_LEFT);
						$clientes_id = $consulta_factura['clientes_id'];

						$subtotal = $res['abono'];
						$isv = 0;
						$descuento = 0;
						$nc = 0;
						$total = $res['abono'];
						$observacion = "Ingresos por venta Cierre de Caja";
						$tipo_ingreso = 2;//OTROS INGRESOS
						$fecha = date("Y-m-d");
						$fecha_registro = date("Y-m-d H:i:s");
						$estado = 1;
						
						$datos_ingresos = [
							"clientes_id" => $clientes_id,
							"cuentas_id" => $cuentas_id,
							"empresa_id" => $empresa_id,
							"fecha" => $fecha,
							"factura" => $no_factura,
							"subtotal" => $subtotal,
							"isv" => $isv,
							"descuento" => $descuento,
							"nc" => $nc,
							"total" => $total,
							"observacion" => $observacion,
							"estado" => $estado,
							"fecha_registro" => $fecha_registro,
							"colaboradores_id" => $res['colaboradores_id'],
							"tipo_ingreso" => $tipo_ingreso,
							"recibide" => ""								
						];						

						//ALMACENAMOS EL INGRESO DEL PAGO
						self::agregar_ingresos_contabilidad_pagos_modelo($datos_ingresos);

						//INGRESAMOS LOS DATOS DEL PAGO EN LA TABLA movimientos_cuentas
						//CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
						$consulta_ingresos_contabilidad = self::consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentas_id)->fetch_assoc();
						$saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) ? $consulta_ingresos_contabilidad['saldo'] : 0;	
						$ingreso = $total;
						$egreso = 0;
						$saldo = $saldo_consulta + $ingreso;

						$datos_movimientos = [
							"cuentas_id" => $cuentas_id,
							"empresa_id" => $empresa_id,
							"fecha" => $fecha,
							"ingreso" => $ingreso,
							"egreso" => $egreso,
							"saldo" => $saldo,
							"colaboradores_id" => $res['colaboradores_id'],
							"fecha_registro" => $fecha_registro,				
						];

						//ALMACENAMOS EL MOVIMIENTO DE CUENTA DEL PAGO
						self::agregar_movimientos_contabilidad_pagos_modelo($datos_movimientos);
						
						$get_cobrar_cliente = pagoFacturaModelo::consultar_factura_cuentas_por_cobrar($res['facturas_id']);
						$saldo_nuevo = 0;
						if($get_cobrar_cliente->num_rows > 0){
							$rec = $get_cobrar_cliente->fetch_assoc();
							$saldo_nuevo = $rec['saldo'];
							$saldo_nuevo = intval($saldo_nuevo);
						}
						
						if($res['multiple_pago'] == 1 && $saldo_nuevo > 0){
							$datos = [
								"modulo" => 'Pagos',
								"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
								"status" => "Registrar",
								"observacion" => "Se registro el pago para la factura {$no_factura} al contado, con pagos múltiples",
								"fecha_registro" => date("Y-m-d H:i:s")
							];	
							
							mainModel::guardarHistorial($datos);

							$alert = [
								"type" => "success",
								"title" => "Registro pago multiples almacenado",
								"text" => "El registro se ha almacenado correctamente",                
								"funcion" => "pago(".$res['facturas_id'].");saldoFactura(".$res['facturas_id'].")"
							];

							//OBTENEMOS EL DOCUMENTO ID DE LA FACTURACION
							$consultaDocumento = mainModel::getDocumentoSecuenciaFacturacion($proformaNombre)->fetch_assoc();
							$documento_id = $consultaDocumento['documento_id'];	

							//VALIDAMOS SI LA FACTURA YA TIENE ASIGNADO UN NUMERO CORRELATIVO, DE NO TENERLO NO HACEMOS NADA
							$result_consuta_factura = pagoFacturaModelo::consultar_numero_factura_modelo($res['facturas_id'])->fetch_assoc();
							$numero_factura_consultado = $result_consuta_factura['number'];

							if($numero_factura_consultado == "" || $numero_factura_consultado == 0){
								if($res['tipo_pago'] == 1){
									$secuenciaFacturacion = pagoFacturaModelo::secuencia_facturacion_modelo($res['empresa'], $documento_id)->fetch_assoc();
									$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
									$numero = $secuenciaFacturacion['numero'];
									$incremento = $secuenciaFacturacion['incremento'];
									$no_factura = $secuenciaFacturacion['prefijo']."".str_pad($secuenciaFacturacion['numero'], $secuenciaFacturacion['relleno'], "0", STR_PAD_LEFT);
								}else{
									$secuenciaFacturacion = pagoFacturaModelo::consultar_numero_factura($res['facturas_id'])->fetch_assoc();
									$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
									$numero = $secuenciaFacturacion['number'];	
									$no_factura = $secuenciaFacturacion['prefijo']."".str_pad($secuenciaFacturacion['numero'], $secuenciaFacturacion['relleno'], "0", STR_PAD_LEFT);			
								}

								//ACTUALIZAMOS EL ESTADO DE LA FACTURA Y EL NUMERO DE FACTURACION
								$datos_update_factura = [
									"facturas_id" => $res['facturas_id'],
									"estado" => 2,//PAGADA
									"number" => $numero,
								];	

								pagoFacturaModelo::actualizar_factura($datos_update_factura);
							}
						}else{
							// Convertimos $saldo_nuevo a un entero para asegurarnos de que estamos comparando números
							$saldo_nuevo = intval($saldo_nuevo);

							$accion = "";
							//SI SE TERMINO DE HAER TODO EL ABONO A LA FACTURA ACTUALIZAMOS LA SECUENCIA DE LA FACTURA Y ACTUALIZAMOS EL ESTADO DE LA PROFORMA
							if($saldo_nuevo === 0){
								//OBTENEMOS EL DOCUMENTO ID DE LA FACTURACION
								$consultaDocumento = mainModel::getDocumentoSecuenciaFacturacion("Factura Electronica")->fetch_assoc();
								$documento_id = $consultaDocumento['documento_id'];	
								
								//OBTENEMOS EL DOCUMENTO ID DE LA FACTURACION
								$secuenciaFacturacion = pagoFacturaModelo::secuencia_facturacion_modelo($res['empresa'], $documento_id)->fetch_assoc();
								$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
								$numero = $secuenciaFacturacion['numero'];
								$incremento = $secuenciaFacturacion['incremento'];	
																					
								if($proformaNombre === "Factura Electronica"){
									//CONSULTAMOS EL NUMERO ACTUAL DE LA FACTURA
									$numeroFactura = pagoFacturaModelo::consultar_numero_factura_pago_modelo($res['facturas_id'])->fetch_assoc();
									$numero = $numeroFactura['number'];
								}

								//ACTUALIZAMOS EL NUMERO Y LA SECUENCIA DE LA FACTURA								
								$datosFactura = [
									"secuencia_facturacion_id" => $secuencia_facturacion_id,
									"number" => $numero,
									"facturas_id" => $res['facturas_id']
								];
								pagoFacturaModelo::actualizar_Secuenciafactura_PagoModelo($datosFactura);								
								
								if($proformaNombre === "Factura Proforma"){
									$numero += $incremento;
									pagoFacturaModelo::actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero);
								}

								//ACTUALIZAMOS EL ESTADO DE LA FACTURA PROFORMA
								pagoFacturaModelo::actualizar_estado_factura_proforma_pagos_modelo($res['facturas_id']);

								$accion = "printBill({$res['facturas_id']})";
							}

							$datos = [
								"modulo" => 'Pagos',
								"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
								"status" => "Registrar",
								"observacion" => "Se registro el pago para la factura {$no_factura} al contado",
								"fecha_registro" => date("Y-m-d H:i:s")
							];	
							
							mainModel::guardarHistorial($datos);
							
							$alert = [
								"type" => "success",
								"title" => "Registro almacenado",
								"text" => "El registro se ha almacenado correctamente",                
								"funcion" => "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();".$accion,
								"form" => "formEfectivoBill",
								"closeAllModals" => true
							];
						}
					}else{
						$alert = [
							"type" => "error",
							"title" => "Ocurrió un error inesperado",
							"text" => "No hemos podido procesar su solicitud"
						];   		
					}					
				}else{
					$alert = [
						"type" => "error",
						"title" => "El abono es mayor al importe",
						"text" => "No hemos podido procesar su solicitud"
					];   

					return $alert;
				}
			}else{//CUANDO LA FACTURA ES AL CONTADO				
				//VERIFICAMOS QUE NO SE HA INGRESADO EL PAGO, SI NO SE HA REALIZADO EL INGRESO, PROCEDEMOS A ALMACENAR EL PAGO
				$result_valid_pagos_facturas = pagoFacturaModelo::valid_pagos_factura($res['facturas_id']);
				if($result_valid_pagos_facturas->num_rows==0){	
					$query = pagoFacturaModelo::agregar_pago_factura_modelo($res);
	
					if($query){
						//ACTUALIZAMOS EL DETALLE DEL PAGO						
						$consulta_pago = pagoFacturaModelo::getLastInserted()->fetch_assoc();
						$pagos_id = $consulta_pago['id'];						
													
						$datos_pago_detalle = [
							"pagos_id" => $pagos_id,
							"tipo_pago_id" => $res['tipo_pago_id'],
							"banco_id" => $res['banco_id'],
							"efectivo" => $res['importe'],
							"descripcion1" => $res['referencia_pago1'],
							"descripcion2" => $res['referencia_pago2'],
							"descripcion3" => $res['referencia_pago3'],
						];
											
						$result_valid_pagos_detalles_facturas = pagoFacturaModelo::valid_pagos_detalles_facturas($pagos_id, $res['tipo_pago_id']);
						
						//VALIDAMOS QUE NO EXISTA EL DETALLE DEL PAGO, DE NO EXISTIR SE ALMACENA EL DETALLE DEL PAGO
						if($result_valid_pagos_detalles_facturas->num_rows==0){
							pagoFacturaModelo::agregar_pago_detalles_factura_modelo($datos_pago_detalle);
						}					
						
						//ACTUALIZAMOS EL ESTADO DE LA FACTURA
						pagoFacturaModelo::update_status_factura($res['facturas_id']);
						pagoFacturaModelo::update_status_factura_cuentas_por_cobrar($res['facturas_id'],2,0);				
	
						//ACTUALIZAMOS EL ESTADO DE LA FACTURA Y EL NUMERO DE FACTURACION
						$datos_update_factura = [
							"facturas_id" => $res['facturas_id'],
							"estado" => 2//PAGADA
						];
						
						pagoFacturaModelo::actualizar_factura($datos_update_factura);

						//CONSULTAMOS EL NUMERO DE LA FACTURA
						$numero = 0;
						$consultaNumeroFactura = pagoFacturaModelo::consultar_numero_factura_pago_modelo($res['facturas_id'])->fetch_assoc();
						$numero = $consultaNumeroFactura['number'] ?? 0;

						//GUARDAR HISTORIAL												
						$datos = [
							"modulo" => 'Pagos',
							"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
							"status" => "Registrar",
							"observacion" => "Se registro el pago para la factura {$numero} al contado",
							"fecha_registro" => date("Y-m-d H:i:s")
						];	
						
						mainModel::guardarHistorial($datos);

						$alert = [
							"alert" => "save_simple",
							"title" => "Registro almacenado",
							"text" => "El registro se ha almacenado correctamente",
							"type" => "success",
							"btn-class" => "btn-primary",
							"btn-text" => "¡Bien Hecho!",
							
							"id" => "proceso_pagos",
							"valor" => "Registro",	
							"funcion" => "",
							"modal" => "modal_pagos",
													
						];

						$alert = [
							"type" => "success",
							"title" => "Registro modificado",
							"text" => "El registro se ha modificado correctamente",                
							"funcion" => "printBill(".$res['facturas_id'].",".$res['print_comprobante'].");listar_cuentas_por_cobrar_clientes();mailBill(".$res['facturas_id'].");getCollaboradoresModalPagoFacturas();",
							"closeAllModals" => true
						]; 						
					}else{
						$alert = [
							"type" => "error",
							"title" => "Ocurrio un error inesperado",
							"text" => "No hemos podido procesar su solicitud"
						];						
					}					
				}else{
					$alert = [
						"type" => "error",
						"title" => "Error al ingresar el pago",
						"text" => "Habilite nuevamente la seccion de Pagos Multiples"
					]; 				
				}						
			}			
			
			return $alert;
		}
	}