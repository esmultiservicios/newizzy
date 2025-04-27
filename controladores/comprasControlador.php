<?php
    if($peticionAjax){
        require_once "../modelos/comprasModelo.php";
    }else{
        require_once "./modelos/comprasModelo.php";
    }

	class comprasControlador extends comprasModelo{
		public function agregar_compras_controlador(){
			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}

            $mainModel = new mainModel();
            $planConfig = $mainModel->getPlanConfiguracionMainModel();
            
            // Solo validar si existe configuración de plan
            if (!empty($planConfig)) {
                $limiteCompras = (int)($planConfig['compras'] ?? 0);
                
                // Caso 1: Límite es 0 (sin permisos)
                if ($limiteCompras === 0) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Acceso restringido",
                        "text" => "Su plan no incluye la creación de compras."
                    ]);
                }
                
                // Caso 2: Validar disponibilidad
                $totalRegistradas = (int)comprasModelo::getTotalComprasRegistradas();
                
                if ($totalRegistradas >= $limiteCompras) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Límite alcanzado",
                        "text" => "Ha excedido el límite mensual de compras (Máximo: $limiteCompras)."
                    ]);
                }
            }

			$usuario = $_SESSION['colaborador_id_sd'];
			$empresa_id = $_SESSION['empresa_id_sd'];			
			//ENCABEZADO DE LA COMPRA
			$proveedores_id = $_POST['proveedores_id'];
			$proveedor = $_POST['proveedor'];
			$colaboradores_id = $_POST['colaborador_id'];	
			$recordatorio = $_POST['recordatorio'] ?? 0;//EVALUAMOS SI EL VALOR NO ES NULO O ESTA DEFINIDO DE LO CONTRARIO DEVOLVEMOS CERO
			$tipoPurchase = $_POST['tipoPurchase'] ?? 2;
			
			if($tipoPurchase === "1"){
				$recordatorio = 0;
			}

			$number = $_POST['facturaPurchase'];
			$no_factura = $number;
			$notas = mainModel::cleanString($_POST['notesPurchase']);
			$fecha = $_POST['fechaPurchase'];
			$fecha_registro = date("Y-m-d H:i:s");
			$cuentas_id = 0;
			
			if($tipoPurchase == 1){
				$estado = 2;//PAGADA
			}else{
				$estado = 3;//CRÉDITO
			}	

			$datos = [
				"proveedores_id" => $proveedores_id,
				"number" => $number,
				"tipoPurchase" => $tipoPurchase,				
				"colaboradores_id" => $colaboradores_id,
				"importe" => 0,
				"notas" => $notas,
				"fecha" => $fecha,				
				"estado" => $estado,
				"usuario" => $usuario,
				"fecha_registro" => $fecha_registro,
				"empresa" => $empresa_id,
				"cuentas_id" => $cuentas_id,
				"recordatorio" => $recordatorio
			];
						
			if($proveedores_id != "" && $colaboradores_id != "" && $number != ""){
				if(comprasModelo::validNumberCompras($proveedores_id, $fecha, $number, $colaboradores_id)->num_rows==0){
					//OBTENEMOS EL TAMAÑO DE LA TABLA
					if(isset($_POST['productNamePurchase'])){	
						if($_POST['productos_idPurchase'][0] && $_POST['productNamePurchase'][0] != "" && $_POST['quantityPurchase'][0] && $_POST['pricePurchase'][0]){
							$tamano_tabla = count( $_POST['productNamePurchase']);
						}else{
							$tamano_tabla = 0;
						}
					}else{
						$tamano_tabla = 0;
					}									

					//SI EXITE VALORES EN LA TABLA, PROCEDEMOS ALMACENAR LA FACTURA Y EL DETALLE DE ESTA
					if($tamano_tabla >0){
						//EINICIO FACTURA CONTADO
						if($tipoPurchase == 1){		
							$query = comprasModelo::agregar_compras_modelo($datos);						

							if($query){
								//ALMACENAMOS LOS DETALLES DE LA FACTURA
								$consulta_compra = comprasModelo::obtener_compraID_modelo($proveedores_id, $fecha, $number, $colaboradores_id)->fetch_assoc();
								$compras_id = $consulta_compra['compras_id'];							
								$total_valor = 0;
								$descuentos = 0;
								$isv_neto = 0;
								$total_despues_isv = 0;
								$discount = 0;
								$isv_valor = 0;
								$valor = 0;
								$fecha_vencimiento = "";
								
								for ($i = 0; $i < count( $_POST['productNamePurchase']); $i++){//INICIO CICLO FOR
									$productos_id = $_POST['productos_idPurchase'][$i];
									$productName = $_POST['productNamePurchase'][$i];
									$quantity = $_POST['quantityPurchase'][$i];
									$price = $_POST['pricePurchase'][$i];
									$medida= $_POST['medidaPurchase'][$i];
									$bodega = $_POST['almacenPurchase'][$i] === "" ? 0 : $_POST['almacenPurchase'][$i];
									$fecha_vencimiento = $_POST['vencimientoPurchase'][$i] === "" ? null : $_POST['vencimientoPurchase'][$i];

									if($_POST['discountPurchase'][$i] != "" || $_POST['discountPurchase'][$i] != null){
										$discount = $_POST['discountPurchase'][$i];	
									}								

									$total = $_POST['totalPurchase'][$i];			

									if($_POST['isvPurchaseWrite'][$i] != "" || $_POST['isvPurchaseWrite'][$i] != null){
										$isv_valor = $_POST['isvPurchaseWrite'][$i];
									}
								
									if($productos_id != "" && $productName != "" && $quantity != "" && $price != "" && $discount != "" && $total != ""){
										//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS
										$datos_detalles_facturas = [
											"compras_id" => $compras_id,
											"productos_id" => $productos_id,
											"cantidad" => $quantity,				
											"precio" => $price,
											"isv_valor" => $isv_valor,
											"descuento" => $discount,	
											"medida" => $medida,			
										];											

										$total_valor += ($price * $quantity);
										$descuentos += $discount;
										$isv_neto += $isv_valor;									

										//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
										comprasModelo::agregar_detalle_compras($datos_detalles_facturas);

										//OBTENEMOS LA CATEOGRIA DEL PRODUCTO PARA EVALUAR SI ES UN PRODUCTO, AGREGAR LA SALIDA DE ESTE
										$result_categoria = comprasModelo::tipo_producto_modelo($productos_id);
							
										$categoria_producto = "";								

										if($result_categoria->num_rows>0){
											$consulta_categoria = $result_categoria->fetch_assoc();
											$categoria_producto = $consulta_categoria["tipo_producto"];											

											//SI LA CATEGORIA ES PRODUCTO PROCEDEMOS A RALIZAR LA SALIDA Y ACTUALIZAMOS LA NUEVA CANTIDAD DEL PRODUCTO, AGREGANDO TAMBIÉN EL MOVIMIENTO DE ESTE
											if($categoria_producto == "Producto" || $categoria_producto == "Insumos"){	
												//ALMACENAMOS EL PRODUCTO TAL CUAL SE FACTURA
												
												// Llamamos a la función para registrar la entrada por lote
												$datos = [
													"productos_id" => $productos_id,
													"clientes_id" => $proveedores_id ?: 0,
													"comentario" => "Entrada inventario por compras",
													"almacen_id" => $bodega ?: 0,
													"fecha_vencimiento" => $fecha_vencimiento,
													"cantidad" => $quantity,
													"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
												];
									
												comprasModelo::registrar_entrada_lote_modelo($datos);												

												$medidaName = strtolower($medida);

												//CONSULTAMOS SI EL PRODUCTO ES UN PADRE
												$producto_padre = comprasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
												$producto_padre_id = $producto_padre['id_producto_superior'];

												//ES UN PRODUCTO PADRE
												if($producto_padre_id == 0){
													//CONSULTAMOS EL HIJO ASOCIADOS AL PRODUCTO PADRE
													$resultTotalHijos = comprasModelo::total_hijos_segun_padre_modelo($productos_id);

													if($resultTotalHijos->num_rows>0){
														$valor = 0;
														while($consultaTotalHijos = $resultTotalHijos->fetch_assoc()){
															$producto_id_hijo = intval($consultaTotalHijos['productos_id']);
															
															if($medidaName == "ton"){ // MEDIDA EN TON DEL PADRE
																$quantity = $quantity * 2204.623;
															}	
															
															if($medidaName == "lbs"){ // MEDIDA EN LBS DEL PADRE
																$quantity = $quantity / 2204.623;
															}																													
															
															// Llamamos a la función para registrar la entrada por lote
															$datos = [
																"productos_id" => $producto_id_hijo,
																"clientes_id" => $proveedores_id ?: 0,
																"comentario" => "Entrada inventario por compras",
																"almacen_id" => $bodega ?: 0,
																"fecha_vencimiento" => $fecha_vencimiento,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
															];
												
															comprasModelo::registrar_entrada_lote_modelo($datos);															
														}
													}

												}else{//ES UN PRODUCTO HIJO
													//CONSULTAMOS EL PADRE ASOCIADO AL PRODUCTO HIJO
									    			$resultTotalPadre = comprasModelo::cantidad_producto_modelo($productos_id);

													if($resultTotalPadre->num_rows>0){
														
														while($consultaTotalPadre = $resultTotalPadre->fetch_assoc()){
															$producto_id_padre = intval($consultaTotalPadre['id_producto_superior']);
															
															if($medidaName == "ton"){ // MEDIDA EN TON DEL PADRE
																$quantity = $quantity * 2204.623;
															}	
															
															if($medidaName == "lbs"){ // MEDIDA EN LBS DEL PADRE
																$quantity = $quantity / 2204.623;
															}																												
															
															// Llamamos a la función para registrar la entrada por lote
															$datos = [
																"productos_id" => $producto_id_padre,
																"clientes_id" => $proveedores_id ?: 0,
																"comentario" => "Entrada inventario por compras",
																"almacen_id" => $bodega ?: 0,
																"fecha_vencimiento" => $fecha_vencimiento,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
															];
												
															comprasModelo::registrar_entrada_lote_modelo($datos);															
														}
													}
												}																						
											
												$datos_prices_list = [
													"compras_id" => $compras_id,
													"productos_id" => $productos_id,
													"prices" => $price,
													"fecha" => $fecha,				
													"usuario" => $usuario,
													"fecha_registro" => $fecha_registro,				
												];												

												//AGREGARMOS LA LISTA DE PRECIOS PARA EL PRODUCTO
												comprasModelo::insert_price_list($datos_prices_list);												
											}								
										}							
									}

								}//FIN CICLO FOR

								$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
							
								//ACTUALIZAMOS EL IMPORTE EN LA COMPRA
								$datos_factura = [
									"compras_id" => $compras_id,
									"importe" => $total_despues_isv		
								];								
							
								$parametro2 = "";

								$alert = [
									"alert" => "save_simple",
									"title" => "Registro almacenado",
									"text" => "El registro se ha almacenado correctamente",
									"type" => "success",
									"btn-class" => "btn-primary",
									"btn-text" => "¡Bien Hecho!",
									"form" => "purchase-form",	
									"id" => "proceso_Purchase",
									"valor" => "Registro",
									"funcion" => "limpiarTablaCompras(); pagoCompras('" . $compras_id . "', '" . $parametro2 . "', '" . $tipoPurchase . "'); getColaboradorCompras(); cleanFooterValuePurchase(); resetRowPurchase();",
									"modal" => "",
								];

								//AGREGAMOS LA CUENTA POR COBRAR CLIENTES
								$estado_cuenta_pagar = 3;///1. Pendiente de Cobrar 2. Pago Realizado 3. Efectivo con abonos
								
								$datos_cobrar_clientes = [
									"proveedores_id" => $proveedores_id,
									"compras_id" => $compras_id,
									"fecha" => $fecha,				
									"saldo" => $total_despues_isv,
									"estado" => $estado_cuenta_pagar,
									"usuario" => $usuario,
									"fecha_registro" => $fecha_registro,
									"empresa" => $empresa_id
								];		
								
								comprasModelo::agregar_cuenta_por_pagar_proveedores($datos_cobrar_clientes);	
								
								//ACTUALIZAMOS EL IMPORTE EN LA COMPRA
								$datos_factura = [
									"compras_id" => $compras_id,
									"importe" => $total_despues_isv		
								];
							
								comprasModelo::actualizar_compra_importe($datos_factura);

							}else{
								$alert = [
									"alert" => "simple",
									"title" => "Ocurrio un error inesperado",
									"text" => "No hemos podido procesar su solicitud",
									"type" => "error",
									"btn-class" => "btn-danger",					
								];				
							}

						//FIN FACTURA CONTADO
						}else{//INICIO FACTURA CRÉDITO
							//SI LA FACTURA ES AL CRÉDITO ALMACENAMOS LOS DATOS DE LA FACTURA PERO NO REGISTRAMOS EL PAGO, SIMPLEMENTE DEJAMOS LA CUENTA POR COBRAR A LOS CLIENTES
							$query = comprasModelo::agregar_compras_modelo($datos);
					
							if($query){
								//ALMACENAMOS LOS DETALLES DE LA FACTURA
								$consulta_compra = comprasModelo::obtener_compraID_modelo($proveedores_id, $fecha, $number, $colaboradores_id)->fetch_assoc();
								$compras_id = $consulta_compra['compras_id'];
							
								$total_valor = 0;
								$descuentos = 0;
								$isv_neto = 0;
								$total_despues_isv = 0;
								$discount = 0;
								$isv_valor = 0;
							
								for ($i = 0; $i < count( $_POST['productNamePurchase']); $i++){//INICIO CICLO FOR
									$productos_id = $_POST['productos_idPurchase'][$i];
									$productName = $_POST['productNamePurchase'][$i];
									$quantity = $_POST['quantityPurchase'][$i];
									$medida= $_POST['medidaPurchase'][$i];
									$price = $_POST['pricePurchase'][$i];
									$bodega = $_POST['almacenPurchase'][$i] === "" ? 0 : $_POST['almacenPurchase'][$i];
									$fecha_vencimiento = $_POST['vencimientoPurchase'][$i] === "" ? null : $_POST['vencimientoPurchase'][$i];

									if($_POST['discountPurchase'][$i] != "" || $_POST['discountPurchase'][$i] != null){
										$discount = $_POST['discountPurchase'][$i];	
									}								

									$total = $_POST['totalPurchase'][$i];			

									if($_POST['isvPurchaseWrite'][$i] != "" || $_POST['isvPurchaseWrite'][$i] != null){
										$isv_valor = $_POST['isvPurchaseWrite'][$i];
									}
								
									if($productos_id != "" && $productName != "" && $quantity != "" && $price != "" && $discount != "" && $total != ""){
										//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS
										$result_factura_detalle = comprasModelo::validDetalleCompras($compras_id, $productos_id);	

										$datos_detalles_facturas = [
											"compras_id" => $compras_id,
											"productos_id" => $productos_id,
											"cantidad" => $quantity,				
											"precio" => $price,
											"isv_valor" => $isv_valor,
											"descuento" => $discount,	
											"medida" => $medida			
										];	
									
										$total_valor += ($price * $quantity);
										$descuentos += $discount;
										$isv_neto += $isv_valor;																			

										if($result_factura_detalle->num_rows>0){
											//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
											comprasModelo::actualizar_detalle_compras($datos_detalles_facturas);								
										}else{
											//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
											comprasModelo::agregar_detalle_compras($datos_detalles_facturas);
										}							
										
										//OBTENEMOS LA CATEOGRIA DEL PRODUCTO PARA EVALUAR SI ES UN PRODUCTO, AGREGAR LA SALIDA DE ESTE
										$result_tipo_producto = comprasModelo::tipo_producto_modelo($productos_id);
					
										$tipo_producto = "";									

										if($result_tipo_producto->num_rows>0){
											$consulta_tipo_producto = $result_tipo_producto->fetch_assoc();
											$tipo_producto = $consulta_tipo_producto["tipo_producto"];
											
											//SI LA CATEGORIA ES PRODUCTO PROCEDEMOS A RALIZAR LA SALIDA Y ACTUALIZAMOS LA NUEVA CANTIDAD DEL PRODUCTO, AGREGANDO TAMBIÉN EL MOVIMIENTO DE ESTE
											if($tipo_producto == "Producto" || $tipo_producto == "Insumos"){
												//ALMACENAMOS EL PRODUCTO TAL CUAL SE FACTURA																										
													
												// Llamamos a la función para registrar la entrada por lote
												$datos = [
													"productos_id" => $productos_id,
													"clientes_id" => $proveedores_id ?: 0,
													"comentario" => "Entrada inventario por compras",
													"almacen_id" => $bodega ?: 0,
													"fecha_vencimiento" => $fecha_vencimiento,
													"cantidad" => $quantity,
													"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
												];
									
												comprasModelo::registrar_entrada_lote_modelo($datos);

												$medidaName = strtolower($medida);

												//CONSULTAMOS SI EL PRODUCTO ES UN PADRE
												$producto_padre = comprasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
												$producto_padre_id = $producto_padre['id_producto_superior'];

												//ES UN PRODUCTO PADRE
												if($producto_padre_id == 0){
													//CONSULTAMOS EL HIJO ASOCIADOS AL PRODUCTO PADRE
													$resultTotalHijos = comprasModelo::total_hijos_segun_padre_modelo($productos_id);

													if($resultTotalHijos->num_rows>0){
														$valor = 0;
														while($consultaTotalHijos = $resultTotalHijos->fetch_assoc()){
															$producto_id_hijo = intval($consultaTotalHijos['productos_id']);
															
															if($medidaName == "ton"){ // MEDIDA EN TON DEL PADRE
																$quantity = $quantity * 2204.623;
															}	
															
															if($medidaName == "lbs"){ // MEDIDA EN LBS DEL PADRE
																$quantity = $quantity / 2204.623;
															}														

															// Llamamos a la función para registrar la entrada por lote
															$datos = [
																"productos_id" => $producto_id_hijo,
																"clientes_id" => $proveedores_id ?: 0,
																"comentario" => "Entrada inventario por compras",
																"almacen_id" => $bodega ?: 0,
																"fecha_vencimiento" => $fecha_vencimiento,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
															];
												
															comprasModelo::registrar_entrada_lote_modelo($datos);															
																									
														}
													}

												}else{//ES UN PRODUCTO HIJO
													//CONSULTAMOS EL PADRE ASOCIADO AL PRODUCTO HIJO
													$resultTotalPadre = comprasModelo::cantidad_producto_modelo($productos_id);

													if($resultTotalPadre->num_rows>0){
														$valor = 0;
														while($consultaTotalPadre = $resultTotalPadre->fetch_assoc()){
															$producto_id_padre = intval($consultaTotalPadre['id_producto_superior']);
															
															if($medidaName == "ton"){ // MEDIDA EN TON DEL PADRE
																$quantity = $quantity * 2204.623;
															}	
															
															if($medidaName == "lbs"){ // MEDIDA EN LBS DEL PADRE
																$quantity = $quantity / 2204.623;
															}																
															
															// Llamamos a la función para registrar la entrada por lote
															$datos = [
																"productos_id" => $producto_id_padre,
																"clientes_id" => $proveedores_id ?: 0,
																"comentario" => "Entrada inventario por compras",
																"almacen_id" => $bodega ?: 0,
																"fecha_vencimiento" => $fecha_vencimiento,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
															];
												
															comprasModelo::registrar_entrada_lote_modelo($datos);															
														}
													}
												}
											
												$datos_prices_list = [
													"compras_id" => $compras_id,
													"productos_id" => $productos_id,
													"prices" => $price,
													"fecha" => $fecha,				
													"usuario" => $usuario,
													"fecha_registro" => $fecha_registro,				
												];	
												
												//AGREGARMOS LA LISTA DE PRECIOS PARA EL PRODUCTO
												comprasModelo::insert_price_list($datos_prices_list);												
											}								
										}							
									}

								}//FIN CICLO FOR

								$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
								
								//ACTUALIZAMOS EL IMPORTE EN LA COMPRA
								$datos_factura = [
									"compras_id" => $compras_id,
									"importe" => $total_despues_isv		
								];
							
								comprasModelo::actualizar_compra_importe($datos_factura);
								
								//AGREGAMOS LA CUENTA POR COBRAR CLIENTES
								$estado_cuenta_cobrar = 1;//CRÉDITO
								
								$datos_cobrar_clientes = [
									"proveedores_id" => $proveedores_id,
									"compras_id" => $compras_id,
									"fecha" => $fecha,				
									"saldo" => $total_despues_isv,
									"estado" => $estado_cuenta_cobrar,
									"usuario" => $usuario,
									"fecha_registro" => $fecha_registro,
									"empresa" => $empresa_id
								];		
								
								comprasModelo::agregar_cuenta_por_pagar_proveedores($datos_cobrar_clientes);
							
								$alert = [
									"alert" => "save_simple",
									"title" => "Registro almacenado",
									"text" => "El registro se ha almacenado correctamente",
									"type" => "success",
									"btn-class" => "btn-primary",
									"btn-text" => "¡Bien Hecho!",
									"form" => "purchase-form",	
									"id" => "proceso_Purchase",
									"valor" => "Registro",
									"funcion" => "limpiarTablaCompras();getColaboradorCompras();printPurchase(".$compras_id.");cleanFooterValuePurchase();resetRowPurchase();",
									"modal" => "",
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

						}//FIN FACTURA CRÉDITO

					}else{
						$alert = [
							"alert" => "simple",
							"title" => "Error Registros en Blanco",
							"text" => "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la compra, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación",
							"type" => "error",
							"btn-class" => "btn-danger",
						];						
					}

				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Resgistro ya existe",
						"text" => "Lo sentimos este registro ya existe",
						"type" => "error",
						"btn-class" => "btn-danger",
					];						
				}
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Error Registros en Blanco",
					"text" => "Lo sentimos el proveedor, el usuario y el número de factura, no pueden quedar en blanco, por favor corregir",
					"type" => "error",
					"btn-class" => "btn-danger",
				];					
			}			

			return mainModel::sweetAlert($alert);
		}
	
		public function cancelar_facturas_controlador(){
			$facturas_id = $_POST['facturas_id'];
		
			$query = comprasModelo::cancelar_compra_modelo($facturas_id);			

			if($query){
				$alert = [
					"alert" => "clear",
					"title" => "Registro eliminado",
					"text" => "El registro se ha eliminado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "",	
					"id" => "",
					"valor" => "Cancelar",
					"funcion" => "",
					"modal" => "",
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
			
			return mainModel::sweetAlert($alert);			
		}
	}