<?php

    if($peticionAjax){
        require_once "../modelos/facturasModelo.php";
    }else{
        require_once "./modelos/facturasModelo.php";
    }

	class facturasControlador extends facturasModelo{
		public function agregar_facturas_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$usuario = $_SESSION['colaborador_id_sd'];
			$empresa_id = $_SESSION['empresa_id_sd'];		
			//ENCABEZADO DE FACTURA
			$clientes_id = $_POST['cliente_id'];
			$colaborador_id = $_POST['colaborador_id'];			
			$tipo_factura = $_POST['facturas_activo'] ?? 2; //1. CONTADO, 2. CREDITO
			$tipo_documento = $_POST['facturas_proforma'] ?? 0; //0. FACTURA ELECTRONICA, 1. FACTURA PROFORMA

			$documento_id = "1";
			$documento_nombre = "Factura Electronica";

			if($tipo_documento === "1"){
				$documento_id = "4";
				$documento_nombre = "Factura Proforma";
			}		
			
			$numero = 0;
			$secuenciaFacturacion = facturasModelo::secuencia_facturacion_modelo($empresa_id, $documento_id)->fetch_assoc();

			if ($secuenciaFacturacion !== null) {
				$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
				$numero = $secuenciaFacturacion['numero'];
				$incremento = $secuenciaFacturacion['incremento'];

				$notas = mainModel::cleanString($_POST['notesBill']);
				$fecha = $_POST['fecha'];
				$fecha_dolar = $_POST['fecha_dolar'];
				$fecha_registro = date("Y-m-d H:i:s");
				$fac_guardada = false;

				if (isset($_POST['facturas_id'])){
					if($_POST['facturas_id'] != "") {
						$facturas_id = $_POST['facturas_id'];
						$fac_guardada = true;
					}else{
						$facturas_id = mainModel::correlativo("facturas_id", "facturas");
					}				
				}else{
					$facturas_id = mainModel::correlativo("facturas_id", "facturas");
				}					
	
				//$estado = ($tipo_factura == 1) ? 1 : 3;
				$estado = 2;
		
				//CONSULTAMOS LA APERTURA
				$datos_apertura = [
					"colaboradores_id" => $usuario,
					"fecha" => $fecha,
					"estado" => 1,
				];
	
				$apertura = facturasModelo::getAperturaIDModelo($datos_apertura)->fetch_assoc();
				$apertura_id = $apertura['apertura_id'];
	
				if($clientes_id != "" && $colaborador_id != ""){
					//OBTENEMOS EL TAMAÑO DE LA TABLA
					if(isset($_POST['productName'])){
						if($_POST['productos_id'][0] && $_POST['productName'][0] != "" && $_POST['quantity'][0] && $_POST['price'][0]){
							$tamano_tabla = count( $_POST['productName']);
						}else{
							$tamano_tabla = 0;
						}
					}else{
						$tamano_tabla = 0;
					}				
	
					//SI EXITE VALORES EN LA TABLA, PROCEDEMOS ALMACENAR LA FACTURA Y EL DETALLE DE ESTA
					if($tamano_tabla > 0){						
						if($tipo_factura == 1){	//INICIO FACTURA CONTADO
							//if($fac_guardada === false) {//NO SE HA GUARDADO LA FACTURA
								$datos = [
									"facturas_id" => $facturas_id,
									"clientes_id" => $clientes_id,
									"secuencia_facturacion_id" => $secuencia_facturacion_id,
									"apertura_id" => $apertura_id,				
									"tipo_factura" => $tipo_factura,				
									"numero" => $numero,
									"colaboradores_id" => $colaborador_id,
									"importe" => 0,
									"notas" => $notas,
									"fecha" => $fecha,				
									"estado" => $estado,
									"usuario" => $usuario,
									"fecha_registro" => $fecha_registro,
									"empresa" => $empresa_id,
									"fecha_dolar" => $fecha_dolar
								];							
								
								$query = facturasModelo::guardar_facturas_modelo($datos);
	
								if($query){
									//ALMACENAMOS LOS DETALLES DE LA FACTURA
									$total_valor = 0;
									$descuentos = 0;
									$isv_neto = 0;
									$total_despues_isv = 0;
		
									for ($i = 0; $i < count( $_POST['productName']); $i++){
										//INICIO CICLO FOR
										$discount = 0;
										$isv_valor = 0;								
										$referenciaProducto = $_POST['referenciaProducto'][$i];
										$productos_id = $_POST['productos_id'][$i];
										$productName = $_POST['productName'][$i];
										$quantity = $_POST['quantity'][$i];
										$medida= $_POST['medida'][$i];
										$price_anterior = $_POST['precio_real'][$i];
										$price = $_POST['price'][$i];
										$bodega = $_POST['bodega'][$i];
	
		
										if($_POST['discount'][$i] != "" || $_POST['discount'][$i] != null){
											$discount = $_POST['discount'][$i];
										}								
		
										$total = $_POST['total'][$i];
		
										if($_POST['valor_isv'][$i] != "" || $_POST['valor_isv'][$i] != null){
											$isv_valor = $_POST['valor_isv'][$i];
										}								
									
										if($productos_id != "" && $productName != "" && $quantity != "" && $price != ""  && $total != ""){
											//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS																	
											$datos_detalles_facturas = [
												"facturas_id" => $facturas_id,
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
											facturasModelo::agregar_detalle_facturas_modelo($datos_detalles_facturas);
		
											//OBTENEMOS LA CATEOGRIA DEL PRODUCTO PARA EVALUAR SI ES UN PRODUCTO, AGREGAR LA SALIDA DE ESTE
		
											$result_tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);
		
											$tipo_producto = "";
		
											if($result_tipo_producto->num_rows>0){						
												$consulta_tipo_producto = $result_tipo_producto->fetch_assoc();
												$tipo_producto = $consulta_tipo_producto["tipo_producto"];
		
												//SI EL TIPO DE PRODUCTO, ES UN PRODUCTO PROCEDEMOS A REALIZAR LA SALIDA Y ACTUALIZAMOS LA NUEVA CANTIDAD DEL PRODUCTO, AGREGANDO TAMBIÉN EL MOVIMIENTO DE ESTE
												if($tipo_producto == "Producto"){
													//ALMACENAMOS EL PRODUCTO TAL CUAL SE FACTURA
													$documento = "Factura ".$facturas_id;													

													$datos = [
														"productos_id" => $productos_id,
														"empresa" => $empresa_id,
														"clientes_id" => $clientes_id ?: 0,
														"comentario" => "Salida de inventario por venta",
														"almacen_id" => $bodega ?: 0,
														"cantidad" => $quantity,
														"empresa_id" => $empresa_id,
														"documento" => $documento
													];
										
													facturasModelo::registrar_salida_lote_modelo($datos);													
		
													$medidaName = strtolower($medida);
		
													//CONSULTAMOS SI EL PRODUCTO ES UN PADRE
													$producto_padre = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
													$producto_padre_id = $producto_padre['id_producto_superior'];
		
													//ES UN PRODUCTO PADRE
													if($producto_padre_id == 0){
														//CONSULTAMOS EL HIJO ASOCIADOS AL PRODUCTO PADRE
														$resultTotalHijos = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
		
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
																
																$documento = "Factura ".$facturas_id."_".$valor;
																
																$datos = [
																	"productos_id" => $producto_id_hijo,
																	"empresa" => $empresa_id,
																	"clientes_id" => $clientes_id ?: 0,
																	"comentario" => "Salida de inventario por venta",
																	"almacen_id" => $bodega ?: 0,
																	"cantidad" => $quantity,
																	"empresa_id" => $empresa_id,
																	"documento" => $documento
																];
													
																facturasModelo::registrar_salida_lote_modelo($datos);																
															}
														}		
													}else{//ES UN PRODUCTO HIJO
														//CONSULTAMOS EL PADRE ASOCIADO AL PRODUCTO HIJO
														$resultTotalPadre = facturasModelo::cantidad_producto_modelo($productos_id);

														if($resultTotalPadre->num_rows > 0){
															$valor = 0;
															while($consultaTotalPadre = $resultTotalPadre->fetch_assoc()){
																$producto_id_padre = intval($consultaTotalPadre['id_producto_superior']);
																
																if($medidaName == "ton"){ // MEDIDA EN TON DEL PADRE
																	$quantity = $quantity * 2204.623;
																}    

																if($medidaName == "lbs"){ // MEDIDA EN LBS DEL PADRE
																	$quantity = $quantity / 2204.623;
																}                                                         
																
																$documento = "Factura ".$facturas_id."_".$valor;

																$datos = [
																	"productos_id" => $producto_id_padre,
																	"empresa" => $empresa_id,
																	"clientes_id" => $clientes_id ?: 0,
																	"comentario" => "Salida de inventario por venta",
																	"almacen_id" => $bodega ?: 0,
																	"cantidad" => $quantity,
																	"empresa_id" => $empresa_id,
																	"documento" => $documento
																];
													
																facturasModelo::registrar_salida_lote_modelo($datos);																
															}
														}
													}
		
													//CONSULTAMOS SI EL PRODUCTO TIENE UN PADRE ASIGNADO
													$resultTotalHijos = facturasModelo::cantidad_producto_modelo($productos_id);
		
													//DEVUELVE id_producto_superior SI ES UN HIJO EL QUE TIENE ASIGNADO UN PADRE
													$valor = 1;
													if($resultTotalHijos->num_rows>0){
														//RECORREMOS LA CONSULTA																											
													}												
												}
											}
		
											if($referenciaProducto != ""){
												//ALMACENAMOS LOS DATOS DEL CAMBIO DE PRECIO DEL PRODUCTO EN LA ENTIDAD precio_factura
												$datos_precio_factura = [
													"facturas_id" => $facturas_id,
													"productos_id" => $productos_id,
													"clientes_id" => $clientes_id,				
													"fecha" => $fecha,
													"referencia" => $referenciaProducto,
													"precio_anterior" => $price_anterior,
													"precio_nuevo" => $price,											
													"fecha_registro" => $fecha_registro											
												];	
		
												$resultPrecioFactura = facturasModelo::valid_precio_factura_modelo($datos_precio_factura);
											
												if($resultPrecioFactura->num_rows==0){
													facturasModelo::agregar_precio_factura_clientes($datos_precio_factura);
												}
											}
										}
		
									}//FIN CICLO FOR
		
									$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
								
									//ACTUALIZAMOS EL IMPORTE EN LA FACTURA
									$datos_factura = [
										"facturas_id" => $facturas_id,
										"importe" => $total_despues_isv		
									];
									
									facturasModelo::actualizar_factura_importe($datos_factura);							
									
									//GUARDAR HISTORIAL
									$campos = ['nombre', 'rtn'];
									$resultados = mainModel::consultar_tabla('clientes', $campos, "clientes_id = {$clientes_id}");
									
									// Verifica si hay resultados antes de intentar acceder a los campos
									if (!empty($resultados)) {
										// Obtén el primer resultado (puedes ajustar según tus necesidades)
										$primerResultado = $resultados[0];
									
										// Verifica si las claves existen antes de acceder a ellas
										$nombre = isset($primerResultado['nombre']) ? $primerResultado['nombre'] : null;
										$rtn = isset($primerResultado['rtn']) ? $primerResultado['rtn'] : null;
									
										// Ahora puedes usar $nombre y $rtn de forma segura
									} else {
										// No se encontraron resultados
										$nombre = null;
										$rtn = null;
									}
																
									$datos = [
										"modulo" => 'Facturas',
										"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
										"status" => "Registro",
										"observacion" => "Se registro la factura al contado para el cliente {$nombre} con el RTN {$rtn}",
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
										"form" => "invoice-form",	
										"id" => "proceso_factura",
										"valor" => "Registro",
										"funcion" => "limpiarTablaFactura();pago(".$facturas_id.");getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();",
										"modal" => "",
									];	

									if($documento_nombre === "Factura Proforma"){
										$alert = [
											"alert" => "save_simple",
											"title" => "Registro almacenado",
											"text" => "El registro se ha almacenado correctamente",
											"type" => "success",
											"btn-class" => "btn-primary",
											"btn-text" => "¡Bien Hecho!",
											"form" => "invoice-form",	
											"id" => "proceso_factura",
											"valor" => "Registro",
											"funcion" => "limpiarTablaFactura();getCajero();printBill(".$facturas_id.");getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();",
											"modal" => "",
										];	

										//AGREGAMOS LA FACTURA PROFORMA
										$datos_proforma = [
											"facturas_id" => $facturas_id,
											"clientes_id" => $clientes_id,
											"secuencia_facturacion_id" => $secuencia_facturacion_id,				
											"numero" => $numero,									
											"importe" => $total_despues_isv,	
											"usuario" => $colaborador_id,
											"empresa_id" => $empresa_id,	
											"estado" => 0,
											"fecha_creacion" => $fecha_registro
										];	

										if($documento_nombre === "Factura Proforma"){
											facturasModelo::agregar_facturas_proforma_modelo($datos_proforma);
										}		
										
										//ACTUALIZAMOS EL ESTADO DE LA FACTURA
										facturasModelo::actualizar_estado_factura_modelo($facturas_id);										
									}	
									
									$numero += $incremento;
									facturasModelo::actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero);

									//AGREGAMOS LA CUENTA POR COBRAR CLIENTES
									$estado_cuenta_cobrar = 1;//CRÉDITO						
		
									$datos_cobrar_clientes = [
										"clientes_id" => $clientes_id,
										"facturas_id" => $facturas_id,
										"fecha" => $fecha,				
										"saldo" => $total_despues_isv,
										"estado" => $estado_cuenta_cobrar,
										"usuario" => $usuario,
										"fecha_registro" => $fecha_registro,
										"empresa" => $empresa_id
									];		
									
									//VERIFICAMOS SI EXISTE EL REGISTRO ANTES DE GUARDARLO
									$resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

									if($resultCobrarClientes->num_rows==0){
										facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);	
									}																	
								}else{
									$alert = [
										"alert" => "simple",
										"title" => "Ocurrio un error inesperado",
										"text" => "No hemos podido procesar su solicitud",
										"type" => "error",
										"btn-class" => "btn-danger",					
									];				
								}
		
								//AGREGAMOS LA CUENTA POR COBRAR CLIENTES
								$estado_cuenta_cobrar = 3;///1. Pendiente de Cobrar 2. Pago Realizado 3. Efectivo con abonos
		
								$datos_cobrar_clientes = [
									"clientes_id" => $clientes_id,
									"facturas_id" => $facturas_id,
									"fecha" => $fecha,				
									"saldo" => $total_despues_isv,
									"estado" => $estado_cuenta_cobrar,//1. Pendiente de Cobrar 2. Pago Realizado 3. Efectivo con abonos
									"usuario" => $usuario,
									"fecha_registro" => $fecha_registro,
									"empresa" => $empresa_id
								];		
								
								//VERIFICAMOS SI EXISTE EL REGISTRO ANTES DE GUARDARLO
								$resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

								if($resultCobrarClientes->num_rows==0){
									facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);	
								}
							/*}else{//YA SE GUARDO LA FACTURA
								$alert = [
									"alert" => "simple",
									"title" => "Opción en desarrollo",
									"text" => "Lo sentimos una factura previamente guardada no se puede procesar, estamos trabajando para habilitar esta opción lo más pronto posible.",
									"type" => "warning",
									"btn-class" => "btn-warning",					
								];	
							}*/
						//FIN FACTURA CONTADO
						}else{//INICIO FACTURA CRÉDITO
							//SI LA FACTURA ES AL CRÉDITO ALMACENAMOS LOS DATOS DE LA FACTURA PERO NO REGISTRAMOS EL PAGO, SIMPLEMENTE DEJAMOS LA CUENTA POR COBRAR A LOS CLIENTES						
	
							$datos = [
								"facturas_id" => $facturas_id,
								"clientes_id" => $clientes_id,
								"secuencia_facturacion_id" => $secuencia_facturacion_id,
								"apertura_id" => $apertura_id,				
								"tipo_factura" => $tipo_factura,				
								"numero" => $numero,
								"colaboradores_id" => $colaborador_id,
								"importe" => 0,
								"notas" => $notas,
								"fecha" => $fecha,				
								"estado" => $estado,
								"usuario" => $usuario,
								"fecha_registro" => $fecha_registro,
								"empresa" => $empresa_id,
								"fecha_dolar" => $fecha_dolar
							];	
													
							$query = facturasModelo::guardar_facturas_modelo($datos);
	
							if($query){
								//ALMACENAMOS LOS DETALLES DE LA FACTURA							
								$total_valor = 0;
								$descuentos = 0;
								$isv_neto = 0;
								$total_despues_isv = 0;
								
								for ($i = 0; $i < count( $_POST['productName']); $i++){//INICIO CICLO FOR
									$discount = 0;
									$isv_valor = 0;
	
									$referenciaProducto = $_POST['referenciaProducto'][$i];
									$productos_id = $_POST['productos_id'][$i];
									$productName = $_POST['productName'][$i];
									$quantity = $_POST['quantity'][$i];
									$medida= $_POST['medida'][$i];
									$price_anterior = $_POST['precio_real'][$i];
									$price = $_POST['price'][$i];
									$bodega = $_POST['bodega'][$i];
	
									if($_POST['discount'][$i] != "" || $_POST['discount'][$i] != null){
										$discount = $_POST['discount'][$i];
									}								
	
									$total = $_POST['total'][$i];
	
									if($_POST['valor_isv'][$i] != "" || $_POST['valor_isv'][$i] != null){
										$isv_valor = $_POST['valor_isv'][$i];
									}																
	
									if($productos_id != "" && $productName != "" && $quantity != "" && $price != "" && $discount != "" && $total != ""){
										//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS
										$result_factura_detalle = facturasModelo::validDetalleFactura($facturas_id, $productos_id);	
	
										$datos_detalles_facturas = [
											"facturas_id" => $facturas_id,
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
	
										if($result_factura_detalle->num_rows>0){
											//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
											facturasModelo::actualizar_detalle_facturas($datos_detalles_facturas);								
										}else{
											//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
											facturasModelo::agregar_detalle_facturas_modelo($datos_detalles_facturas);
										}							
										
										//OBTENEMOS LA CATEOGRIA DEL PRODUCTO PARA EVALUAR SI ES UN PRODUCTO, AGREGAR LA SALIDA DE ESTE
										$result_tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);					
										$categoria_producto = "";							
	
										if($result_tipo_producto->num_rows>0){
											$consulta_categoria = $result_tipo_producto->fetch_assoc();
											$categoria_producto = $consulta_categoria["tipo_producto"];
											
											//SI LA CATEGORIA ES PRODUCTO PROCEDEMOS A REALIZAR LA SALIDA Y ACTUALIZAMOS LA NUEVA CANTIDAD DEL PRODUCTO, AGREGANDO TAMBIÉN EL MOVIMIENTO DE ESTE
											if($categoria_producto == "Producto"){
												//ALMACENAMOS EL PRODUCTO TAL CUAL SE FACTURA
												$documento = "Factura ".$facturas_id;											
	
												$datos = [
													"productos_id" => $productos_id,
													"empresa" => $empresa_id,
													"clientes_id" => $clientes_id ?: 0,
													"comentario" => "Salida de inventario por venta",
													"almacen_id" => $bodega ?: 0,
													"cantidad" => $quantity,
													"empresa_id" => $empresa_id,
													"documento" => $documento
												];
									
												facturasModelo::registrar_salida_lote_modelo($datos);

												$medidaName = strtolower($medida);
	
												//CONSULTAMOS SI EL PRODUCTO ES UN PADRE
												$producto_padre = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
												$producto_padre_id = $producto_padre['id_producto_superior'];
	
												//ES UN PRODUCTO PADRE
												if($producto_padre_id == 0){
													//CONSULTAMOS EL HIJO ASOCIADOS AL PRODUCTO PADRE
													$resultTotalHijos = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
	
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
															
															$documento = "Factura ".$facturas_id."_".$valor;	
															
															$datos = [
																"productos_id" => $producto_id_hijo,
																"empresa" => $empresa_id,
																"clientes_id" => $clientes_id ?: 0,
																"comentario" => "Salida de inventario por venta",
																"almacen_id" => $bodega ?: 0,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id,
																"documento" => $documento
															];
												
															facturasModelo::registrar_salida_lote_modelo($datos);															
														}
													}
	
												}else{//ES UN PRODUCTO HIJO
													//CONSULTAMOS EL PADRE ASOCIADO AL PRODUCTO HIJO
													$resultTotalPadre = facturasModelo::cantidad_producto_modelo($productos_id);
	
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
															
															$documento = "Factura ".$facturas_id."_".$valor;
															
															$datos = [
																"productos_id" => $producto_id_padre,
																"empresa" => $empresa_id,
																"clientes_id" => $clientes_id ?: 0,
																"comentario" => "Salida de inventario por venta",
																"almacen_id" => $bodega ?: 0,
																"cantidad" => $quantity,
																"empresa_id" => $empresa_id,
																"documento" => $documento
															];
												
															facturasModelo::registrar_salida_lote_modelo($datos);															
														}
													}
												}
											}								
										}	
										
										if($referenciaProducto != ""){
											//ALMACENAMOS LOS DATOS DEL CAMBIO DE PRECIO DEL PRODUCTO EN LA ENTIDAD precio_factura
											$datos_precio_factura = [
												"facturas_id" => $facturas_id,
												"productos_id" => $productos_id,
												"clientes_id" => $clientes_id,				
												"fecha" => $fecha,
												"referencia" => $referenciaProducto,
												"precio_anterior" => $price_anterior,
												"precio_nuevo" => $price,											
												"fecha_registro" => $fecha_registro											
											];	
	
											$resultPrecioFactura = facturasModelo::valid_precio_factura_modelo($datos_precio_factura);
											
											if($resultPrecioFactura->num_rows==0){
												facturasModelo::agregar_precio_factura_clientes($datos_precio_factura);
											}																			
										}									
									}
	
								}//FIN CICLO FOR
	
								$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
	
								//OBTENEMOS EL DOCUMENTO ID DE LA FACTURACION
								$consultaDocumento = mainModel::getDocumentoSecuenciaFacturacion($documento_nombre)->fetch_assoc();
								$documento_id = $consultaDocumento['documento_id'];							
								
								//ACTUALIZAMOS EL NUMERO SIGUIENTE DE LA SECUENCIA PARA LA FACTURACION
								$secuenciaFacturacion = facturasModelo::secuencia_facturacion_modelo($empresa_id, $documento_id)->fetch_assoc();
								$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
								$numero = $secuenciaFacturacion['numero'];
								$incremento = $secuenciaFacturacion['incremento'];
								$no_factura = $secuenciaFacturacion['prefijo']."".str_pad($secuenciaFacturacion['numero'], $secuenciaFacturacion['relleno'], "0", STR_PAD_LEFT);
	
								$numero += $incremento;
								facturasModelo::actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero);								
	
								//ACTUALIZAMOS EL IMPORTE EN LA FACTURA
								$datos_factura = [
									"facturas_id" => $facturas_id,
									"importe" => $total_despues_isv,
									"number" => $numero,	
								];
								
								facturasModelo::actualizar_factura_importe($datos_factura);						
	
								//AGREGAMOS LA FACTURA PROFORMA
								$datos_proforma = [
									"facturas_id" => $facturas_id,
									"clientes_id" => $clientes_id,
									"secuencia_facturacion_id" => $secuencia_facturacion_id,				
									"numero" => $numero,									
									"importe" => $total_despues_isv,	
									"usuario" => $colaborador_id,
									"empresa_id" => $empresa_id,	
									"estado" => 0,
									"fecha_creacion" => $fecha_registro
								];	

								if($documento_nombre === "Factura Proforma"){
									facturasModelo::agregar_facturas_proforma_modelo($datos_proforma);
								}															

								//AGREGAMOS LA CUENTA POR COBRAR CLIENTES
								$estado_cuenta_cobrar = 1;//CRÉDITO						
	
								$datos_cobrar_clientes = [
									"clientes_id" => $clientes_id,
									"facturas_id" => $facturas_id,
									"fecha" => $fecha,				
									"saldo" => $total_despues_isv,
									"estado" => $estado_cuenta_cobrar,
									"usuario" => $usuario,
									"fecha_registro" => $fecha_registro,
									"empresa" => $empresa_id
								];		
								
								//VERIFICAMOS SI EXISTE EL REGISTRO ANTES DE GUARDARLO
								$resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

								if($resultCobrarClientes->num_rows==0){
									facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);	
								}
								
								//GUARDAR HISTORIAL
								$campos = ['nombre', 'rtn'];
								$resultados = mainModel::consultar_tabla('clientes', $campos, "clientes_id = {$clientes_id}");
								
								// Verifica si hay resultados antes de intentar acceder a los campos
								if (!empty($resultados)) {
									// Obtén el primer resultado (puedes ajustar según tus necesidades)
									$primerResultado = $resultados[0];
								
									// Verifica si las claves existen antes de acceder a ellas
									$nombre = isset($primerResultado['nombre']) ? $primerResultado['nombre'] : null;
									$rtn = isset($primerResultado['rtn']) ? $primerResultado['rtn'] : null;
								
									// Ahora puedes usar $nombre y $rtn de forma segura
								} else {
									// No se encontraron resultados
									$nombre = null;
									$rtn = null;
								}
																
								$datos = [
									"modulo" => 'Facturas',
									"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
									"status" => "Registrar",
									"observacion" => "Se registro la factura {$numero} al crédito para el cliente {$nombre} con el RTN {$rtn}",
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
									"form" => "invoice-form",	
									"id" => "proceso_factura",
									"valor" => "Registro",
									"funcion" => "limpiarTablaFactura();getCajero();printBill(".$facturas_id.");getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();",
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
							"text" => "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación",
							"type" => "error",
							"btn-class" => "btn-danger",
						];					
					}				
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Error Registros en Blanco",
						"text" => "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir",
						"type" => "error",
						"btn-class" => "btn-danger",
					];					
				}									
			} else {
				$alert = [
					"alert" => "simple",
					"title" => "Error",
					"text" => "Lo sentimos, no cuenta con una secuencia de facturación activa, por favor comuniquese con su contador para solventar el problema.",
					"type" => "error",
					"btn-class" => "btn-danger",
				];	
			}

			return mainModel::sweetAlert($alert);
		}
		
		public function agregar_facturas_open_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$usuario = $_SESSION['colaborador_id_sd'];
			$empresa_id = $_SESSION['empresa_id_sd'];		
			//ENCABEZADO DE FACTURA
			$clientes_id = $_POST['cliente_id'];
			$colaborador_id = $_POST['colaborador_id'];
		
			if(isset($_POST['facturas_activo'])){//COMPRUEBO SI LA VARIABLE ESTA DIFINIDA
				if($_POST['facturas_activo'] == ""){
					$tipo_factura = 2;//CREDITO 
				}else{
					$tipo_factura = $_POST['facturas_activo'];
				}
			}else{
				$tipo_factura = 2;
			}

			$numero = 0;
			$Existe = false;//VARIABLE FLAG QUE USAREMOS PARA SABER SI EXISTE LA FACTURA

			$fecha = $_POST['fecha'];
			$documento_id = "1";//FACTURA ELECTRONICA
			$secuenciaFacturacion = facturasModelo::secuencia_facturacion_modelo($empresa_id, $documento_id)->fetch_assoc();
			$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];

			$notas = mainModel::cleanString($_POST['notesBill']);
			$fecha_dolar = $_POST['fecha_dolar'];
			$fecha_registro = date("Y-m-d H:i:s");			

			//VALIDAMOS SI EL CAMPO FACTURA HA SIDO ENVIADO, DE NO SERLO CONSULTAMOS EL NUMERO SIGUIENTE DEL CORRELATIVO
			if($_POST['facturas_id'] == "" || $_POST['facturas_id'] == 0){
				$facturas_id = mainModel::correlativo("facturas_id", "facturas");	
			}else{//SI EL NUMERO ES ENVIADO SIMPLEMETE LO ASIGNAMOS PARA POSTERIORMENTE VALIDARLO
				$facturas_id = $_POST['facturas_id'];
				$Existe = true;
			}

			if($tipo_factura == 1){
				$estado = 1;//BORRADOR
			}else{
				$estado = 3;//CRÉDITO
			}	

			//CONSULTAMOS LA APERTURA
			$datos_apertura = [
				"colaboradores_id" => $usuario,
				"fecha" => $fecha,
				"estado" => 1,
			];				

			$apertura = facturasModelo::getAperturaIDModelo($datos_apertura)->fetch_assoc();
			$apertura_id = $apertura['apertura_id'];

			if($clientes_id != "" && $colaborador_id != ""){
				//OBTENEMOS EL TAMAÑO DE LA TABLA
				if(isset($_POST['productName'])){
					if($_POST['productos_id'][0] && $_POST['productName'][0] != "" && $_POST['quantity'][0] && $_POST['price'][0]){
						$tamano_tabla = count( $_POST['productName']);
					}else{
						$tamano_tabla = 0;
					}
				}else{
					$tamano_tabla = 0;
				}				

				//SI EXITE VALORES EN LA TABLA, PROCEDEMOS ALMACENAR LA FACTURA Y EL DETALLE DE ESTA
				if($tamano_tabla > 0){
					$datos = [
						"facturas_id" => $facturas_id,
						"clientes_id" => $clientes_id,
						"secuencia_facturacion_id" => $secuencia_facturacion_id,
						"apertura_id" => $apertura_id,				
						"tipo_factura" => $tipo_factura,				
						"numero" => $numero,
						"colaboradores_id" => $colaborador_id,
						"importe" => 0,
						"notas" => $notas,
						"fecha" => $fecha,				
						"estado" => $estado,
						"usuario" => $usuario,
						"fecha_registro" => $fecha_registro,
						"empresa" => $empresa_id,
						"fecha_dolar" => $fecha_dolar
					];	
										
					if($Existe == false){										
						facturasModelo::guardar_facturas_modelo($datos);
					}else{
						facturasModelo::actualizar_factura_importe($datos);
					}

					//ALMACENAMOS LOS DETALLES DE LA FACTURA
					$total_valor = 0;
					$descuentos = 0;
					$isv_neto = 0;
					$total_despues_isv = 0;

					for ($i = 0; $i < count( $_POST['productName']); $i++){
						//INICIO CICLO FOR
						$discount = 0;
						$isv_valor = 0;								
						$referenciaProducto = $_POST['referenciaProducto'][$i];
						$productos_id = $_POST['productos_id'][$i];
						$productName = $_POST['productName'][$i];
						$quantity = $_POST['quantity'][$i];
						$medida= $_POST['medida'][$i];
						$price_anterior = $_POST['precio_real'][$i];
						$price = $_POST['price'][$i];
						$bodega = $_POST['bodega'][$i];


						if($_POST['discount'][$i] != "" || $_POST['discount'][$i] != null){
							$discount = $_POST['discount'][$i];
						}								

						$total = $_POST['total'][$i];

						if($_POST['valor_isv'][$i] != "" || $_POST['valor_isv'][$i] != null){
							$isv_valor = $_POST['valor_isv'][$i];
						}								
					
						if($productos_id != "" && $productName != "" && $quantity != "" && $price != ""  && $total != ""){
							//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS					
							
							$datos_detalles_facturas = [
								"facturas_id" => $facturas_id,
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
							
							//VALIDAMOS SI EL PRODUCTO EXISTE EN LA FACTURA DETALLE DE NO EXISTIR SE GUARDA Y SI EXISTE SE ACTUALIZA
							$result_factura_detalle = facturasModelo::validDetalleFactura($facturas_id, $productos_id);

							if($result_factura_detalle->num_rows==0){
								//INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA
								facturasModelo::agregar_detalle_facturas_modelo($datos_detalles_facturas);
							}else{
								//ACTUALIZAMOS EL DETALLE DE LA FACTURA
								facturasModelo::actualizar_detalle_facturas($datos_detalles_facturas);
							}
						
							//OBTENEMOS LA CATEOGRIA DEL PRODUCTO PARA EVALUAR SI ES UN PRODUCTO, AGREGAR LA SALIDA DE ESTE
							$result_tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);

							$tipo_producto = "";

							if($result_tipo_producto->num_rows>0){						
								$consulta_tipo_producto = $result_tipo_producto->fetch_assoc();
								$tipo_producto = $consulta_tipo_producto["tipo_producto"];

								//SI EL TIPO DE PRODUCTO, ES UN PRODUCTO PROCEDEMOS A REALIZAR LA SALIDA Y ACTUALIZAMOS LA NUEVA CANTIDAD DEL PRODUCTO, AGREGANDO TAMBIÉN EL MOVIMIENTO DE ESTE
								if($tipo_producto == "Producto"){
									//ALMACENAMOS EL PRODUCTO TAL CUAL SE FACTURA
									$documento = "Factura ".$facturas_id;	
									
									//OTENEMOS EL SALDO DEL PRODCUTO
									$consultaSaldoProductoPrincipal = facturasModelo::saldo_productos_movimientos_modelo($productos_id)->fetch_assoc();
									$saldoProductoPrincipal = doubleval($consultaSaldoProductoPrincipal['saldo']);											

									$saldoNuevoPricipal = $saldoProductoPrincipal - doubleval($quantity);

									$datos_movimientos_productos = [
										"facturas_id" => $facturas_id,
										"clientes_id" => $clientes_id,
										"secuencia_facturacion_id" => $secuencia_facturacion_id,
										"apertura_id" => $apertura_id,				
										"tipo_factura" => $tipo_factura,				
										"numero" => $numero,
										"colaboradores_id" => $colaborador_id,
										"importe" => 0,
										"notas" => $notas,
										"fecha" => $fecha,				
										"estado" => $estado,
										"usuario" => $usuario,
										"fecha_registro" => $fecha_registro,
										"empresa" => $empresa_id,
										"fecha_dolar" => $fecha_dolar
									];	
										
									if($Existe == false){														
										$datos = [
											"productos_id" => $productos_id,
											"empresa" => $empresa_id,
											"clientes_id" => $clientes_id ?: 0,
											"comentario" => "Salida de inventario por venta",
											"almacen_id" => $bodega ?: 0,
											"cantidad" => $quantity,
											"empresa_id" => $empresa_id,
											"documento" => $documento
										];
							
										facturasModelo::registrar_salida_lote_modelo($datos);											
									}else{
										facturasModelo::actualizar_factura_importe($datos_movimientos_productos);
									}

									$medidaName = strtolower($medida);

									//CONSULTAMOS SI EL PRODUCTO ES UN PADRE
									$producto_padre = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
									$producto_padre_id = $producto_padre['id_producto_superior'];

									//ES UN PRODUCTO PADRE
									if($producto_padre_id == 0){
										//CONSULTAMOS EL HIJO ASOCIADOS AL PRODUCTO PADRE
										$resultTotalHijos = facturasModelo::total_hijos_segun_padre_modelo($productos_id);

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
												
												$documento = "Factura ".$facturas_id."_".$valor;
												
												$datos = [
													"productos_id" => $producto_id_hijo,
													"empresa" => $empresa_id,
													"clientes_id" => $clientes_id ?: 0,
													"comentario" => "Salida de inventario por venta",
													"almacen_id" => $bodega ?: 0,
													"cantidad" => $quantity,
													"empresa_id" => $empresa_id,
													"documento" => $documento
												];
									
												facturasModelo::registrar_salida_lote_modelo($datos);												
											}
										}

									}else{//ES UN PRODUCTO HIJO
										//CONSULTAMOS EL PADRE ASOCIADO AL PRODUCTO HIJO
										$resultTotalPadre = facturasModelo::cantidad_producto_modelo($productos_id);

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
												
												$documento = "Factura ".$facturas_id."_".$valor;
												
												$datos = [
													"productos_id" => $producto_id_padre,
													"empresa" => $empresa_id,
													"clientes_id" => $clientes_id ?: 0,
													"comentario" => "Salida de inventario por venta",
													"almacen_id" => $bodega ?: 0,
													"cantidad" => $quantity,
													"empresa_id" => $empresa_id,
													"documento" => $documento
												];
									
												facturasModelo::registrar_salida_lote_modelo($datos);												
											}
										}
									}

									//CONSULTAMOS SI EL PRODUCTO TIENE UN PADRE ASIGNADO
									$resultTotalHijos = facturasModelo::cantidad_producto_modelo($productos_id);

									//DEVUELVE id_producto_superior SI ES UN HIJO EL QUE TIENE ASIGNADO UN PADRE
									$valor = 1;
									if($resultTotalHijos->num_rows>0){
										//RECORREMOS LA CONSULTA																							
									}												
								}
							}

							if($referenciaProducto != ""){
								//ALMACENAMOS LOS DATOS DEL CAMBIO DE PRECIO DEL PRODUCTO EN LA ENTIDAD precio_factura
								$datos_precio_factura = [
									"facturas_id" => $facturas_id,
									"productos_id" => $productos_id,
									"clientes_id" => $clientes_id,				
									"fecha" => $fecha,
									"referencia" => $referenciaProducto,
									"precio_anterior" => $price_anterior,
									"precio_nuevo" => $price,											
									"fecha_registro" => $fecha_registro											
								];	

								$resultPrecioFactura = facturasModelo::valid_precio_factura_modelo($datos_precio_factura);
							
								if($resultPrecioFactura->num_rows==0){
									facturasModelo::agregar_precio_factura_clientes($datos_precio_factura);
								}
							}
						}

					}//FIN CICLO FOR
					$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
				
					//ACTUALIZAMOS EL IMPORTE EN LA FACTURA
					$datos_factura = [
						"facturas_id" => $facturas_id,
						"importe" => $total_despues_isv		
					];
					
					facturasModelo::actualizar_factura_importe($datos_factura);							
					
					$alert = [
						"alert" => "save_simple",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "invoice-form",	
						"id" => "proceso_factura",
						"valor" => "Registro",
						"funcion" => "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();",
						"modal" => "",
					];			
						
					//EVALUAR SI LA FACTURA YA ESTA REGISTRADA SI NO SOLO ACTUALIZAMOS SU VALOR
					$datos_cobrar_clientes = [
						"clientes_id" => $clientes_id,
						"facturas_id" => $facturas_id,
						"fecha" => $fecha,				
						"saldo" => $total_despues_isv,
						"estado" => 3,//1. Pendiente de Cobrar 2. Pago Realizado 3. Efectivo con abonos
						"usuario" => $usuario,
						"fecha_registro" => $fecha_registro,
						"empresa" => $empresa_id
					];		
					
					//VERIFICAMOS SI EXISTE EL REGISTRO ANTES DE GUARDARLO
					$resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

					if($resultCobrarClientes->num_rows==0){
						facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);	
					}
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Error Registros en Blanco",
						"text" => "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación",
						"type" => "error",
						"btn-class" => "btn-danger",
					];					
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Error Registros en Blanco",
					"text" => "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir",
					"type" => "error",
					"btn-class" => "btn-danger",
				];					
			}	

			return mainModel::sweetAlert($alert);
		}

		public function cancelar_facturas_controlador(){
			$facturas_id = $_POST['facturas_id'];		

			$campos = ['number'];
			$resultados = mainModel::consultar_tabla('facturas', $campos, "facturas_id = {$facturas_id}");
			
			// Verifica si hay resultados antes de intentar acceder a los campos
			if (!empty($resultados)) {
				// Obtén el primer resultado (puedes ajustar según tus necesidades)
				$primerResultado = $resultados[0];
			
				// Verifica si las claves existen antes de acceder a ellas
				$number = isset($primerResultado['number']) ? $primerResultado['number'] : null;
			
				// Ahora puedes usar $nombre y $rtn de forma segura
			} else {
				// No se encontraron resultados
				$number = null;
			}
			
			$query = facturasModelo::cancelar_facturas_modelo($facturas_id);
			
			if($query){
				//GUARDAR HISTORIAL
												
				$datos = [
					"modulo" => 'Facturas',
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
					"status" => "Cancelar",
					"observacion" => "Se cancelo la factura {$number}",
					"fecha_registro" => date("Y-m-d H:i:s")
				];	
				
				mainModel::guardarHistorial($datos);

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