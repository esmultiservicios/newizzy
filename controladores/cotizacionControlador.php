<?php
    if($peticionAjax){
        require_once "../modelos/cotizacionModelo.php";
    }else{
        require_once "./modelos/cotizacionModelo.php";
    }
	
    class cotizacionControlador extends cotizacionModelo{
        public function agregar_cotizacion_controlador(){
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
			if (isset($planConfig['cotizaciones'])) {
				$limiteCotizaciones = (int)$planConfig['cotizaciones']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
				
               // Caso 1: Límite es 0 (sin permisos)
                if ($limiteCotizaciones === 0) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Acceso restringido",
                        "text" => "Su plan no incluye la creación de cotizaciones."
                    ]);
                }
                
                // Caso 2: Validar disponibilidad
                $totalRegistradas = (int)cotizacionModelo::getTotalCotizacionesRegistradas();
                
                if ($totalRegistradas >= $limiteCotizaciones) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Límite alcanzado",
                        "text" => "Ha excedido el límite mensual de cotizaciones (Máximo: $limiteCotizaciones)."
                    ]);
                }
			}

            $usuario = $_SESSION['colaborador_id_sd'];
            $empresa_id = $_SESSION['empresa_id_sd'];			
            // ENCABEZADO DE COTIZACIÓN
            $clientes_id = $_POST['cliente_id'];
            $colaborador_id = $_POST['colaborador_id'];
            $notas = mainModel::cleanStringConverterCase($_POST['notesQuote']);
            $fecha = $_POST['fecha'];
            $fecha_dolar = $_POST['fecha_dolar'];
            $fecha_registro = date("Y-m-d H:i:s");
            $estado = 1; // ACTIVO
            $cotizacion_id = mainModel::correlativo("cotizacion_id", "cotizacion");
            $numero = mainModel::correlativo("number", "cotizacion");
            $tipo_factura = 1;
            
            // Validar vigencia
            $vigencia_quote = isset($_POST['vigencia_quote']) ? ($_POST['vigencia_quote'] == "" ? 0 : $_POST['vigencia_quote']) : 0;
            
            if($clientes_id == "" || $colaborador_id == ""){
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error en registros",
                    "text" => "El cliente y el vendedor no pueden quedar en blanco, por favor corregir"
                ]);
            }
            
            // VERIFICAR SI HAY PRODUCTOS EN LA TABLA
            $tamano_tabla = 0;
            if(isset($_POST['productNameQuote']) && 
               isset($_POST['productosQuote_id'][0]) && $_POST['productNameQuote'][0] != "" && 
               isset($_POST['quantityQuote'][0]) && isset($_POST['priceQuote'][0])) {
                $tamano_tabla = count($_POST['productNameQuote']);
            }
            
            if($tamano_tabla <= 0){
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error en registros",
                    "text" => "No ha seleccionado productos en el detalle de la cotización, debe seleccionar al menos un producto"
                ]);
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
            
            $query = cotizacionModelo::agregar_cotizacion_modelo($datos);
            
            if(!$query){
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "No se pudo procesar la solicitud de cotización"
                ]);
            }
            
            // ALMACENAR DETALLES DE LA COTIZACIÓN
            $total_valor = 0;
            $descuentos = 0;
            $isv_neto = 0;
            $item = count($_POST['productNameQuote']);
            
            for ($i = 0; $i < $item; $i++){
                $productos_id = $_POST['productosQuote_id'][$i];
                $productName = $_POST['productNameQuote'][$i];
                $quantity = $_POST['quantityQuote'][$i];
                $price = $_POST['priceQuote'][$i];
                $discount = isset($_POST['discountQuote'][$i]) && $_POST['discountQuote'][$i] != "" ? $_POST['discountQuote'][$i] : 0;
                $total = $_POST['totalQuote'][$i];
                $isv_valor = isset($_POST['valorQuote_isv'][$i]) && $_POST['valorQuote_isv'][$i] != "" ? $_POST['valorQuote_isv'][$i] : 0;
                
                if($productos_id != "" && $productName != "" && $quantity != "" && $price != ""){
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
                    
                    cotizacionModelo::agregar_detalle_cotizacion($datos_detalles_cotizacion);
                }
            }
            
            $total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
            
            // ACTUALIZAR EL IMPORTE EN LA COTIZACIÓN
            $datos_factura = [
                "cotizacion_id" => $cotizacion_id,
                "importe" => $total_despues_isv		
            ];
        
            cotizacionModelo::actualizar_cotizacion_importe($datos_factura);
            
            // Registrar en historial
            mainModel::guardarHistorial([
                "modulo" => 'Cotizaciones',
                "colaboradores_id" => $usuario,
                "status" => "Registro",
                "observacion" => "Se registró la cotización #{$numero}",
                "fecha_registro" => $fecha_registro
            ]);
            
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro exitoso",
                "text" => "La cotización se ha registrado correctamente",
                "form" => "quoteForm",
                "funcion" => "limpiarTablaQuote();printQuote(".$cotizacion_id.");mailQuote(".$cotizacion_id.");getConsumidorFinal();getCajero();cleanFooterValueQuote();resetRow();"
            ]);
        }
            
        public function cancelar_cotizacion_controlador(){
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
            
            $cotizacion_id = $_POST['cotizacion_id'];
            
            // Obtener información de la cotización para el historial
            $campos = ['numero'];
            $tabla = "cotizacion";
            $condicion = "cotizacion_id = {$cotizacion_id}";
            
            $cotizacion = mainModel::consultar_tabla($tabla, $campos, $condicion);
            $numero = $cotizacion[0]['numero'] ?? 'desconocido';
            
            if(!cotizacionModelo::cancelar_cotizacion_modelo($cotizacion_id)){
                return mainModel::showNotification([
                    "type" => "error",
                    "title" => "Error",
                    "text" => "No se pudo cancelar la cotización"
                ]);
            }
            
            // Registrar en historial
            mainModel::guardarHistorial([
                "modulo" => 'Cotizaciones',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status" => "Cancelación",
                "observacion" => "Se canceló la cotización #{$numero}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ]);
            
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Cancelación exitosa",
                "text" => "La cotización ha sido cancelada correctamente",
                "funcion" => "listar_cotizaciones();"
            ]);
        }
    }