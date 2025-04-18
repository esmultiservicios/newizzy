<?php
if($peticionAjax){
    require_once "../modelos/facturasModelo.php";
}else{
    require_once "./modelos/facturasModelo.php";
}

class facturasControlador extends facturasModelo{
    // Métodos auxiliares para evitar repetición de código
    
    /**
     * Registra el movimiento de productos (padres e hijos)
     */
    private function registrarMovimientoProducto($datos) {
        // Registra el producto principal
        facturasModelo::registrar_salida_lote_modelo($datos);
        
        $medidaName = strtolower($datos['medida']);
        $quantity = $datos['cantidad'];
        $productos_id = $datos['productos_id'];
        $empresa_id = $datos['empresa_id'];
        $clientes_id = $datos['clientes_id'] ?? 0;
        $bodega = $datos['almacen_id'] ?? 0;
        $documentoBase = $datos['documento'];
        
        // Consultamos si el producto es padre o hijo
        $producto_padre = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
        $producto_padre_id = $producto_padre['id_producto_superior'];

        // Si es un producto padre
        if($producto_padre_id == 0){
            $resultTotalHijos = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
            if($resultTotalHijos->num_rows>0){
                $valor = 0;
                while($consultaTotalHijos = $resultTotalHijos->fetch_assoc()){
                    $producto_id_hijo = intval($consultaTotalHijos['productos_id']);
                    $cantidadConvertida = $this->convertirCantidad($medidaName, $quantity);
                    
                    $datosHijo = [
                        "productos_id" => $producto_id_hijo,
                        "empresa" => $empresa_id,
                        "clientes_id" => $clientes_id,
                        "comentario" => "Salida de inventario por venta",
                        "almacen_id" => $bodega,
                        "cantidad" => $cantidadConvertida,
                        "empresa_id" => $empresa_id,
                        "documento" => $documentoBase."_".$valor
                    ];
                    
                    facturasModelo::registrar_salida_lote_modelo($datosHijo);
                    $valor++;
                }
            }
        } else { // Si es un producto hijo
            $resultTotalPadre = facturasModelo::cantidad_producto_modelo($productos_id);
            if($resultTotalPadre->num_rows>0){
                $valor = 0;
                while($consultaTotalPadre = $resultTotalPadre->fetch_assoc()){
                    $producto_id_padre = intval($consultaTotalPadre['id_producto_superior']);
                    $cantidadConvertida = $this->convertirCantidad($medidaName, $quantity);
                    
                    $datosPadre = [
                        "productos_id" => $producto_id_padre,
                        "empresa" => $empresa_id,
                        "clientes_id" => $clientes_id,
                        "comentario" => "Salida de inventario por venta",
                        "almacen_id" => $bodega,
                        "cantidad" => $cantidadConvertida,
                        "empresa_id" => $empresa_id,
                        "documento" => $documentoBase."_".$valor
                    ];
                    
                    facturasModelo::registrar_salida_lote_modelo($datosPadre);
                    $valor++;
                }
            }
        }
    }
    
    /**
     * Convierte cantidades entre toneladas y libras
     */
    private function convertirCantidad($medidaName, $quantity) {
        if($medidaName == "ton"){
            return $quantity * 2204.623;
        }    
        if($medidaName == "lbs"){
            return $quantity / 2204.623;
        }
        return $quantity;
    }
    
    /**
     * Registra el detalle de la factura
     */
    private function registrarDetalleFactura($facturas_id, $productos_id, $quantity, $price, $isv_valor, $discount, $medida) {
        $datos_detalles_facturas = [
            "facturas_id" => $facturas_id,
            "productos_id" => $productos_id,
            "cantidad" => $quantity,                
            "precio" => $price,
            "isv_valor" => $isv_valor,
            "descuento" => $discount,
            "medida" => $medida,    
        ];    

        $result_factura_detalle = facturasModelo::validDetalleFactura($facturas_id, $productos_id);
        if($result_factura_detalle->num_rows>0){
            facturasModelo::actualizar_detalle_facturas($datos_detalles_facturas);
        } else {
            facturasModelo::agregar_detalle_facturas_modelo($datos_detalles_facturas);
        }
    }
    
    /**
     * Registra cambios de precio si hay referencia
     */
    private function registrarCambioPrecio($facturas_id, $productos_id, $clientes_id, $fecha, $referenciaProducto, $price_anterior, $price, $fecha_registro) {
        if($referenciaProducto != ""){
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
    
    /**
     * Procesa los productos de la factura (común para crédito y contado)
     */
    private function procesarProductosFactura($facturas_id, $clientes_id, $colaborador_id, $empresa_id, $fecha, $fecha_registro, &$total_valor, &$descuentos, &$isv_neto) {
        for ($i = 0; $i < count($_POST['productName']); $i++){
            $discount = $_POST['discount'][$i] ?? 0;
            $isv_valor = $_POST['valor_isv'][$i] ?? 0;                                
            $referenciaProducto = $_POST['referenciaProducto'][$i];
            $productos_id = $_POST['productos_id'][$i];
            $productName = $_POST['productName'][$i];
            $quantity = $_POST['quantity'][$i];
            $medida = $_POST['medida'][$i];
            $price_anterior = $_POST['precio_real'][$i];
            $price = $_POST['price'][$i];
            $bodega = $_POST['bodega'][$i];
            $total = $_POST['total'][$i];

            if($productos_id != "" && $productName != "" && $quantity != "" && $price != "" && $total != ""){
                // Registramos el detalle de la factura
                $this->registrarDetalleFactura($facturas_id, $productos_id, $quantity, $price, $isv_valor, $discount, $medida);
                
                $total_valor += ($price * $quantity);
                $descuentos += $discount;
                $isv_neto += $isv_valor;
                
                // Verificamos si es un producto para registrar movimientos
                $result_tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);
                if($result_tipo_producto->num_rows>0){                        
                    $consulta_tipo_producto = $result_tipo_producto->fetch_assoc();
                    if($consulta_tipo_producto["tipo_producto"] == "Producto"){
                        $documento = "Factura ".$facturas_id;
                        
                        $datosMovimiento = [
                            "productos_id" => $productos_id,
                            "empresa" => $empresa_id,
                            "clientes_id" => $clientes_id ?: 0,
                            "comentario" => "Salida de inventario por venta",
                            "almacen_id" => $bodega ?: 0,
                            "cantidad" => $quantity,
                            "empresa_id" => $empresa_id,
                            "documento" => $documento,
                            "medida" => $medida
                        ];
                        
                        $this->registrarMovimientoProducto($datosMovimiento);
                    }
                }
                
                // Registramos cambio de precio si hay referencia
                $this->registrarCambioPrecio($facturas_id, $productos_id, $clientes_id, $fecha, $referenciaProducto, $price_anterior, $price, $fecha_registro);
            }
        }
    }
    
    /**
     * Crea la alerta de respuesta
     */
    private function crearAlerta($tipo, $titulo, $texto, $facturas_id = null, $esProforma = false) {
        $alert = [
            "alert" => $tipo == "error" ? "simple" : "save_simple",
            "title" => $titulo,
            "text" => $texto,
            "type" => $tipo == "error" ? "error" : "success",
            "btn-class" => $tipo == "error" ? "btn-danger" : "btn-primary",
            "btn-text" => $tipo == "error" ? "Cerrar" : "¡Bien Hecho!",
            "form" => "invoice-form",    
            "id" => "proceso_factura",
            "valor" => "Registro",
            "modal" => "",
        ];
        
        if($tipo != "error"){
            if($esProforma){
                $alert["funcion"] = "limpiarTablaFactura();getCajero();printBill(".$facturas_id.");getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
            } else {
                $alert["funcion"] = "limpiarTablaFactura();pago(".$facturas_id.");getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
            }
        }
        
        return $alert;
    }
    
    /**
     * Registra la cuenta por cobrar
     */
    private function registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, $estado, $usuario, $fecha_registro, $empresa_id) {
        $datos_cobrar_clientes = [
            "clientes_id" => $clientes_id,
            "facturas_id" => $facturas_id,
            "fecha" => $fecha,                
            "saldo" => $total_despues_isv,
            "estado" => $estado,
            "usuario" => $usuario,
            "fecha_registro" => $fecha_registro,
            "empresa" => $empresa_id
        ];        
            
        $resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);
        if($resultCobrarClientes->num_rows==0){
            facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);    
        }
    }
    
    /**
     * Registra el historial
     */
    private function registrarHistorial($modulo, $status, $observacion) {
        $datos = [
            "modulo" => $modulo,
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
            "status" => $status,
            "observacion" => $observacion,
            "fecha_registro" => date("Y-m-d H:i:s")
        ];    
        
        mainModel::guardarHistorial($datos);
    }
    
    /**
     * Guarda la factura proforma si es necesario
     */
    private function guardarFacturaProforma($esProforma, $facturas_id, $clientes_id, $secuencia_facturacion_id, $numero, $total_despues_isv, $colaborador_id, $empresa_id, $fecha_registro) {
        if($esProforma){
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

            facturasModelo::agregar_facturas_proforma_modelo($datos_proforma);
            facturasModelo::actualizar_estado_factura_modelo($facturas_id);
        }
    }

    // Métodos principales
    
	public function agregar_facturas_controlador(){
		if(!isset($_SESSION['user_sd'])){ 
			session_start(['name'=>'SD']); 
		}
		
		$usuario = $_SESSION['colaborador_id_sd'];
		$empresa_id = $_SESSION['empresa_id_sd'];        
		$clientes_id = $_POST['cliente_id'];
		$colaborador_id = $_POST['colaborador_id'];            
		$tipo_factura = $_POST['facturas_activo'] ?? 2;
		$tipo_documento = $_POST['facturas_proforma'] ?? 0;
		$documento_id = "1";
		$documento_nombre = "Factura Electronica";
		$esProforma = false;
	
		if($tipo_documento === "1"){
			$documento_id = "4";
			$documento_nombre = "Factura Proforma";
			$esProforma = true;
		}        
		
		$numero = 0;
		$secuenciaFacturacion = facturasModelo::secuencia_facturacion_modelo($empresa_id, $documento_id)->fetch_assoc();
	
		if ($secuenciaFacturacion === null) {
			return mainModel::sweetAlert($this->crearAlerta("error", "Error", "Lo sentimos, no cuenta con una secuencia de facturación activa, por favor comuniquese con su contador para solventar el problema."));
		}
	
		$secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];
		$numero = $secuenciaFacturacion['numero'];
		$incremento = $secuenciaFacturacion['incremento'];
		$notas = mainModel::cleanString($_POST['notesBill']);
		$fecha = $_POST['fecha'];
		$fecha_dolar = $_POST['fecha_dolar'];
		$fecha_registro = date("Y-m-d H:i:s");
		$fac_guardada = false;
	
		if (isset($_POST['facturas_id']) && $_POST['facturas_id'] != "") {
			$facturas_id = $_POST['facturas_id'];
			$fac_guardada = true;
		} else {
			$facturas_id = mainModel::correlativo("facturas_id", "facturas");
		}                    
	
		$estado = 2;
	
		$datos_apertura = [
			"colaboradores_id" => $usuario,
			"fecha" => $fecha,
			"estado" => 1,
		];
	
		$apertura = facturasModelo::getAperturaIDModelo($datos_apertura)->fetch_assoc();
		$apertura_id = $apertura['apertura_id'];
	
		if($clientes_id == "" || $colaborador_id == ""){
			return mainModel::sweetAlert($this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir"));
		}
	
		// Primero verificamos si existe el array productName
		$tamano_tabla = 0;
		if (isset($_POST['productName']) && is_array($_POST['productName'])) {
			// Luego verificamos si el primer elemento tiene los campos requeridos
			if (isset($_POST['productos_id'][0]) && 
				isset($_POST['productName'][0]) && 
				$_POST['productName'][0] != "" && 
				isset($_POST['quantity'][0]) && 
				isset($_POST['price'][0])) {
				$tamano_tabla = count($_POST['productName']);
			}
		}
	
		if($tamano_tabla == 0){
			return mainModel::sweetAlert($this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación"));
		}
	
		$datos_factura = [
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
	
		$query = facturasModelo::guardar_facturas_modelo($datos_factura);
	
		if(!$query){
			return mainModel::sweetAlert($this->crearAlerta("error", "Ocurrio un error inesperado", "No hemos podido procesar su solicitud"));
		}
	
		// Procesamos los productos (común para crédito y contado)
		$total_valor = $descuentos = $isv_neto = 0;
		$this->procesarProductosFactura($facturas_id, $clientes_id, $colaborador_id, $empresa_id, $fecha, $fecha_registro, $total_valor, $descuentos, $isv_neto);
	
		$total_despues_isv = ($total_valor + $isv_neto) - $descuentos;
	
		// Actualizamos el importe en la factura
		$datos_actualizar = [
			"facturas_id" => $facturas_id,
			"importe" => $total_despues_isv        
		];
		
		facturasModelo::actualizar_factura_importe($datos_actualizar);                            
	
		// Obtenemos datos del cliente para el historial
		$campos = ['nombre', 'rtn'];
		$resultados = mainModel::consultar_tabla('clientes', $campos, "clientes_id = {$clientes_id}");
		
		$nombre = $rtn = null;
		if (!empty($resultados)) {
			$primerResultado = $resultados[0];
			$nombre = $primerResultado['nombre'] ?? null;
			$rtn = $primerResultado['rtn'] ?? null;
		}
	
		// Registramos historial según tipo de factura
		if($tipo_factura == 1){
			$this->registrarHistorial('Facturas', 'Registro', "Se registro la factura al contado para el cliente {$nombre} con el RTN {$rtn}");
		} else {
			$this->registrarHistorial('Facturas', 'Registrar', "Se registro la factura {$numero} al crédito para el cliente {$nombre} con el RTN {$rtn}");
		}
	
		// Guardamos factura proforma si es necesario
		$this->guardarFacturaProforma($esProforma, $facturas_id, $clientes_id, $secuencia_facturacion_id, $numero, $total_despues_isv, $colaborador_id, $empresa_id, $fecha_registro);
	
		// Actualizamos secuencia de facturación
		$numero += $incremento;
		facturasModelo::actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero);
	
		// Registramos cuenta por cobrar
		$estado_cuenta = ($tipo_factura == 1) ? 3 : 1; // 1=Crédito, 3=Contado
		$this->registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, $estado_cuenta, $usuario, $fecha_registro, $empresa_id);
	
		// Retornamos respuesta exitosa
		return mainModel::sweetAlert($this->crearAlerta("success", "Registro almacenado", "El registro se ha almacenado correctamente", $facturas_id, $esProforma));
	}
    
    public function agregar_facturas_open_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];        
        $clientes_id = $_POST['cliente_id'];
        $colaborador_id = $_POST['colaborador_id'];
    
        $tipo_factura = isset($_POST['facturas_activo']) && $_POST['facturas_activo'] != "" ? $_POST['facturas_activo'] : 2;
        $numero = 0;
        $Existe = false;

        $fecha = $_POST['fecha'];
        $documento_id = "1";
        $secuenciaFacturacion = facturasModelo::secuencia_facturacion_modelo($empresa_id, $documento_id)->fetch_assoc();
        $secuencia_facturacion_id = $secuenciaFacturacion['secuencia_facturacion_id'];

        $notas = mainModel::cleanString($_POST['notesBill']);
        $fecha_dolar = $_POST['fecha_dolar'];
        $fecha_registro = date("Y-m-d H:i:s");            

        if($_POST['facturas_id'] == "" || $_POST['facturas_id'] == 0){
            $facturas_id = mainModel::correlativo("facturas_id", "facturas");    
        }else{
            $facturas_id = $_POST['facturas_id'];
            $Existe = true;
        }

        $estado = ($tipo_factura == 1) ? 1 : 3;

        $datos_apertura = [
            "colaboradores_id" => $usuario,
            "fecha" => $fecha,
            "estado" => 1,
        ];                

        $apertura = facturasModelo::getAperturaIDModelo($datos_apertura)->fetch_assoc();
        $apertura_id = $apertura['apertura_id'];

        if($clientes_id == "" || $colaborador_id == ""){
            return mainModel::sweetAlert($this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir"));
        }

		$tamano_tabla = (isset($_POST['productName']) && 
		is_array($_POST['productName']) && 
		isset($_POST['productos_id'][0]) && 
		isset($_POST['productName'][0]) && 
		$_POST['productName'][0] != "" && 
		isset($_POST['quantity'][0]) && 
		isset($_POST['price'][0])) 
		? count($_POST['productName']) : 0;

        if($tamano_tabla == 0){
            return mainModel::sweetAlert($this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación"));
        }

        $datos_factura = [
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
            facturasModelo::guardar_facturas_modelo($datos_factura);
        }else{
            facturasModelo::actualizar_factura_importe($datos_factura);
        }

        // Procesamos los productos
        $total_valor = $descuentos = $isv_neto = 0;
        $this->procesarProductosFactura($facturas_id, $clientes_id, $colaborador_id, $empresa_id, $fecha, $fecha_registro, $total_valor, $descuentos, $isv_neto);

        $total_despues_isv = ($total_valor + $isv_neto) - $descuentos;

        // Actualizamos importe en factura
        $datos_actualizar = [
            "facturas_id" => $facturas_id,
            "importe" => $total_despues_isv        
        ];
        
        facturasModelo::actualizar_factura_importe($datos_actualizar);                            

        // Registramos cuenta por cobrar
        $this->registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, 3, $usuario, $fecha_registro, $empresa_id);

        // Retornamos respuesta exitosa
        return mainModel::sweetAlert($this->crearAlerta("success", "Registro almacenado", "El registro se ha almacenado correctamente", $facturas_id, false));
    }

    public function cancelar_facturas_controlador(){
        $facturas_id = $_POST['facturas_id'];        

        $campos = ['number'];
        $resultados = mainModel::consultar_tabla('facturas', $campos, "facturas_id = {$facturas_id}");
        
        $number = null;
        if (!empty($resultados)) {
            $primerResultado = $resultados[0];
            $number = $primerResultado['number'] ?? null;
        }
        
        $query = facturasModelo::cancelar_facturas_modelo($facturas_id);
        
        if($query){
            $this->registrarHistorial('Facturas', 'Cancelar', "Se cancelo la factura {$number}");

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