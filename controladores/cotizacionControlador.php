<?php
    if($peticionAjax){
        require_once "../modelos/cotizacionModelo.php";
    }else{
        require_once "./modelos/cotizacionModelo.php";
    }


	class cotizacionControlador extends cotizacionModelo{
		public function agregar_cotizacion_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
	
			$usuario = $_SESSION['colaborador_id_sd'];
			$empresa_id = $_SESSION['empresa_id_sd'];			

			//ENCABEZADO DE FACTURA
			$clientes_id = $_POST['cliente_id'];
			$colaborador_id = $_POST['colaborador_id'];
			$notas = mainModel::cleanStringConverterCase($_POST['notesQuote']);
			$fecha = $_POST['fecha'];
			$fecha_dolar = $_POST['fecha_dolar'];
			$fecha_registro = date("Y-m-d H:i:s");
		    $estado = 1;//CONTADO
			$cotizacion_id = mainModel::correlativo("cotizacion_id", "cotizacion");
			$numero = mainModel::correlativo("number", "cotizacion");
			$tipo_factura = 1;
			
			if(isset($_POST['vigencia_quote'])){//COMPRUEBO SI LA VARIABLE ESTA DIFINIDA
				if($_POST['vigencia_quote'] == ""){
					$vigencia_quote = 0;
				}else{
					$vigencia_quote = $_POST['vigencia_quote'];
				}
			}else{
				$vigencia_quote = 0;
			}

			$datos = [
				"cotizacion_id" => $cotizacion_id,
				"clientes_id" => $clientes_id,				
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
				"vigencia_quote" => $vigencia_quote,					
				"fecha_dolar" => $fecha_dolar
			];	

						

			if($clientes_id != "" && $colaborador_id != ""){

				//OBTENEMOS EL TAMAÑO DE LA TABLA

				if(isset($_POST['productNameQuote'])){	

					if($_POST['productosQuote_id'][0] && $_POST['productNameQuote'][0] != "" && $_POST['quantityQuote'][0] && $_POST['priceQuote'][0]){

						$tamano_tabla = count( $_POST['productNameQuote']);

					}else{

						$tamano_tabla = 0;

					}

				}else{

					$tamano_tabla = 0;

				}				

				

				//SI EXITE VALORES EN LA TABLA, PROCEDEMOS ALMACENAR LA FACTURA Y EL DETALLE DE ESTA

				if($tamano_tabla > 0){	

					$query = cotizacionModelo::agregar_cotizacion_modelo($datos);

				

					if($query){

						//ALMACENAMOS LOS DETALLES DE LA FACTURA

						$total_valor = 0;

						$descuentos = 0;

						$isv_neto = 0;

						$total_despues_isv = 0;

						$discount = 0;

						$isv_valor = 0;

						$item = count( $_POST['productNameQuote']);
				
						for ($i = 0; $i < count( $_POST['productNameQuote']); $i++){//INICIO CICLO FOR

							$referenciaProducto = $_POST['referenciaProductoQuote'][$i];

							$productos_id = $_POST['productosQuote_id'][$i];
							$productName = $_POST['productNameQuote'][$i];
							$quantity = $_POST['quantityQuote'][$i];
							$price_anterior = $_POST['precio_realQuote'][$i];
							$price = $_POST['priceQuote'][$i];


							if($_POST['discountQuote'][$i] != "" || $_POST['discountQuote'][$i] != null){

								$discount = $_POST['discountQuote'][$i];

							}

							

							$total = $_POST['totalQuote'][$i];



							if($_POST['valorQuote_isv'][$i] != "" || $_POST['valorQuote_isv'][$i] != null){

								$isv_valor = $_POST['valorQuote_isv'][$i];

							}	

							

							if($productos_id != "" && $productName != "" && $quantity != "" && $price != "" && $discount != "" && $total != ""){

								//VERIFICAMOS SI NO EXISTE LA FACTURA, DE NO EXISTIR LA ACTUALIZAMOS

								//$result_cotizacion_detalle = cotizacionModelo::validDetalleCotizacion($cotizacion_id, $productos_id);	



								$datos_detalles_cotizacion = [

									"cotizacion_id" => $cotizacion_id,

									"productos_id" => $productos_id,

									"cantidad" => $quantity,				

									"precio" => $price,

									"isv_valor" => $isv_valor,

									"descuento" => $discount,				

								];	

								

								$total_valor += ($price * $quantity);

								$descuentos += $discount;

								$isv_neto += $isv_valor;									
								
								//echo 'INSERTAMOS LOS DE PRODUCTOS EN EL DETALLE DE LA FACTURA';
								cotizacionModelo::agregar_detalle_cotizacion($datos_detalles_cotizacion);

							}

						}//FIN CICLO FOR

						$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
						

						//ACTUALIZAMOS EL IMPORTE EN LA FACTURA

						$datos_factura = [

							"cotizacion_id" => $cotizacion_id,

							"importe" => $total_despues_isv		

						];

					
						cotizacionModelo::actualizar_cotizacion_importe($datos_factura);

						

						$alert = [

							"alert" => "save_simple",

							"title" => "Registro almacenado",

							"text" => "El registro se ha almacenado correctamente".$item,

							"type" => "success",

							"btn-class" => "btn-primary",

							"btn-text" => "¡Bien Hecho!",

							"form" => "quoteForm",

							"id" => "proceso_pagos",

							"valor" => "Registro",	

							"funcion" => "limpiarTablaQuote();printQuote(".$cotizacion_id.");mailQuote(".$cotizacion_id.");	getConsumidorFinal();getCajero();cleanFooterValueQuote();resetRow();",

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

		

		public function cancelar_cotizacion_controlador(){

			$cotizacion_id = $_POST['cotizacion_id'];

			

			$query = cotizacionModelo::cancelar_cotizacion_modelo($cotizacion_id);

			

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