<?php
if($peticionAjax){
    require_once "../modelos/facturasModelo.php";
}else{
    require_once "./modelos/facturasModelo.php";
}

class facturasControlador extends facturasModelo {
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
                $tamano_tabla = $this->validarProductos();

                //SI EXITE VALORES EN LA TABLA, PROCEDEMOS ALMACENAR LA FACTURA Y EL DETALLE DE ESTA
                if($tamano_tabla > 0){                        
                    if($tipo_factura == 1){    //INICIO FACTURA CONTADO
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
                            $totales = $this->procesarProductos($facturas_id, $clientes_id, $empresa_id, $fecha, $fecha_registro);
                            
                            $total_despues_isv = ($totales['total_valor'] + $totales['isv_neto']) - $totales['descuentos'];
                            
                            //ACTUALIZAMOS EL IMPORTE EN LA FACTURA
                            $datos_factura = [
                                "facturas_id" => $facturas_id,
                                "importe" => $total_despues_isv        
                            ];
                            
                            facturasModelo::actualizar_factura_importe($datos_factura);                            
                            
                            //GUARDAR HISTORIAL
                            $this->guardarHistorialFactura($clientes_id, $numero, $tipo_factura);
                            
                            $alert = $this->crearAlerta("success", "Registro almacenado", "El registro se ha almacenado correctamente", $documento_nombre, $facturas_id, $tipo_factura);

                            if($documento_nombre === "Factura Proforma"){
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

                                facturasModelo::agregar_facturas_proforma_modelo($datos_proforma);
                                facturasModelo::actualizar_estado_factura_modelo($facturas_id);                                        
                            }    
                            
                            $numero += $incremento;
                            facturasModelo::actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero);

                            //AGREGAMOS LA CUENTA POR COBRAR CLIENTES
                            $estado_cuenta_cobrar = 3; //EFECTIVO CON ABONOS                      
            
                            $this->registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, $estado_cuenta_cobrar, $usuario, $fecha_registro, $empresa_id);
                        }else{
                            $alert = $this->crearAlerta("error", "Ocurrio un error inesperado", "No hemos podido procesar su solicitud");
                        }
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
                            $totales = $this->procesarProductos($facturas_id, $clientes_id, $empresa_id, $fecha, $fecha_registro);
                            
                            $total_despues_isv = ($totales['total_valor'] + $totales['isv_neto']) - $totales['descuentos'];
                            
                            //ACTUALIZAMOS EL IMPORTE EN LA FACTURA
                            $datos_factura = [
                                "facturas_id" => $facturas_id,
                                "importe" => $total_despues_isv        
                            ];
                            
                            facturasModelo::actualizar_factura_importe($datos_factura);                            
                            
                            //GUARDAR HISTORIAL
                            $this->guardarHistorialFactura($clientes_id, $numero, $tipo_factura);
                            
                            //AGREGAMOS LA CUENTA POR COBRAR CLIENTES
                            $estado_cuenta_cobrar = 1;//CRÉDITO                      
            
                            $this->registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, $estado_cuenta_cobrar, $usuario, $fecha_registro, $empresa_id);
                            
                            $alert = $this->crearAlerta("success", "Registro almacenado", "El registro se ha almacenado correctamente", $documento_nombre, $facturas_id, $tipo_factura);
                        }else{
                            $alert = $this->crearAlerta("error", "Ocurrio un error inesperado", "No hemos podido procesar su solicitud");
                        }
                    }//FIN FACTURA CRÉDITO
                }else{
                    $alert = $this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación");
                }                
            }else{
                $alert = $this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir");
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

    protected function validarProductos() {
        if(isset($_POST['productName'])){
            if($_POST['productos_id'][0] && $_POST['productName'][0] != "" && $_POST['quantity'][0] && $_POST['price'][0]){
                return count($_POST['productName']);
            }
        }
        return 0;
    }
    
    protected function procesarProductos($facturas_id, $clientes_id, $empresa_id, $fecha, $fecha_registro) {
        $total_valor = $descuentos = $isv_neto = 0;
        
        for ($i = 0; $i < count($_POST['productName']); $i++){
            $producto = $this->obtenerDatosProducto($i);
            
            if($this->validarDatosProducto($producto)) {
                $this->procesarDetalleFactura($facturas_id, $producto);
                
                $total_valor += ($producto['price'] * $producto['quantity']);
                $descuentos += $producto['discount'];
                $isv_neto += $producto['isv_valor'];
                
                $this->procesarInventario($facturas_id, $clientes_id, $empresa_id, $producto);
                
                if($producto['referenciaProducto'] != "") {
                    $this->registrarCambioPrecio($facturas_id, $clientes_id, $fecha, $fecha_registro, $producto);
                }
            }
        }
        
        return [
            'total_valor' => $total_valor,
            'descuentos' => $descuentos,
            'isv_neto' => $isv_neto
        ];
    }
    
    protected function obtenerDatosProducto($i) {
        return [
            'referenciaProducto' => $_POST['referenciaProducto'][$i],
            'productos_id' => $_POST['productos_id'][$i],
            'productName' => $_POST['productName'][$i],
            'quantity' => $_POST['quantity'][$i],
            'medida' => $_POST['medida'][$i],
            'price_anterior' => $_POST['precio_real'][$i],
            'price' => $_POST['price'][$i],
            'bodega' => $_POST['bodega'][$i],
            'discount' => $_POST['discount'][$i] ?? 0,
            'total' => $_POST['total'][$i],
            'isv_valor' => $_POST['valor_isv'][$i] ?? 0
        ];
    }
    
    protected function validarDatosProducto($producto) {
        return $producto['productos_id'] != "" && $producto['productName'] != "" && 
               $producto['quantity'] != "" && $producto['price'] != "" && $producto['total'] != "";
    }
    
    protected function procesarDetalleFactura($facturas_id, $producto) {
        $datos_detalles_facturas = [
            "facturas_id" => $facturas_id,
            "productos_id" => $producto['productos_id'],
            "cantidad" => $producto['quantity'],                
            "precio" => $producto['price'],
            "isv_valor" => $producto['isv_valor'],
            "descuento" => $producto['discount'],
            "medida" => $producto['medida'],    
        ];
        
        facturasModelo::agregar_detalle_facturas_modelo($datos_detalles_facturas);
    }
    
    protected function procesarInventario($facturas_id, $clientes_id, $empresa_id, $producto) {
        $result_tipo_producto = facturasModelo::tipo_producto_modelo($producto['productos_id']);
        
        if($result_tipo_producto->num_rows > 0) {
            $consulta_tipo_producto = $result_tipo_producto->fetch_assoc();
            
            if($consulta_tipo_producto["tipo_producto"] == "Producto") {
                $this->registrarSalidaProducto($facturas_id, $clientes_id, $empresa_id, $producto);
            }
        }
    }
    
    protected function registrarSalidaProducto($facturas_id, $clientes_id, $empresa_id, $producto) {
        $documento = "Factura ".$facturas_id;
        
        $datos = [
            "productos_id" => $producto['productos_id'],
            "empresa" => $empresa_id,
            "clientes_id" => $clientes_id ?: 0,
            "comentario" => "Salida de inventario por venta",
            "almacen_id" => $producto['bodega'] ?: 0,
            "cantidad" => $producto['quantity'],
            "empresa_id" => $empresa_id,
            "documento" => $documento
        ];
        
        facturasModelo::registrar_salida_lote_modelo($datos);
        
        $this->procesarRelacionesProducto($facturas_id, $clientes_id, $empresa_id, $producto);
    }
    
    protected function procesarRelacionesProducto($facturas_id, $clientes_id, $empresa_id, $producto) {
        $producto_padre = facturasModelo::cantidad_producto_modelo($producto['productos_id'])->fetch_assoc();
        $producto_padre_id = $producto_padre['id_producto_superior'];
        $medidaName = strtolower($producto['medida']);
        
        if($producto_padre_id == 0) {
            $this->procesarHijos($facturas_id, $clientes_id, $empresa_id, $producto['productos_id'], $producto['quantity'], $producto['bodega'], $medidaName);
        } else {
            $this->procesarPadre($facturas_id, $clientes_id, $empresa_id, $producto['productos_id'], $producto['quantity'], $producto['bodega'], $medidaName);
        }
    }
    
    protected function procesarHijos($facturas_id, $clientes_id, $empresa_id, $productos_id, $quantity, $bodega, $medidaName) {
        $resultTotalHijos = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
        
        if($resultTotalHijos->num_rows > 0) {
            $valor = 0;
            while($consultaTotalHijos = $resultTotalHijos->fetch_assoc()) {
                $producto_id_hijo = intval($consultaTotalHijos['productos_id']);
                $cantidadConvertida = $this->convertirCantidad($quantity, $medidaName);
                
                $this->registrarSalidaHijo($facturas_id, $clientes_id, $empresa_id, $producto_id_hijo, $cantidadConvertida, $bodega, $valor);
                $valor++;
            }
        }
    }
    
    protected function procesarPadre($facturas_id, $clientes_id, $empresa_id, $productos_id, $quantity, $bodega, $medidaName) {
        $resultTotalPadre = facturasModelo::cantidad_producto_modelo($productos_id);
        
        if($resultTotalPadre->num_rows > 0) {
            $valor = 0;
            while($consultaTotalPadre = $resultTotalPadre->fetch_assoc()) {
                $producto_id_padre = intval($consultaTotalPadre['id_producto_superior']);
                $cantidadConvertida = $this->convertirCantidad($quantity, $medidaName);
                
                $this->registrarSalidaHijo($facturas_id, $clientes_id, $empresa_id, $producto_id_padre, $cantidadConvertida, $bodega, $valor);
                $valor++;
            }
        }
    }
    
    protected function convertirCantidad($quantity, $medidaName) {
        if($medidaName == "ton") {
            return $quantity * 2204.623;
        } elseif($medidaName == "lbs") {
            return $quantity / 2204.623;
        }
        return $quantity;
    }
    
    protected function registrarSalidaHijo($facturas_id, $clientes_id, $empresa_id, $producto_id, $quantity, $bodega, $valor) {
        $documento = "Factura ".$facturas_id."_".$valor;
        
        $datos = [
            "productos_id" => $producto_id,
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
    
    protected function registrarCambioPrecio($facturas_id, $clientes_id, $fecha, $fecha_registro, $producto) {
        $datos_precio_factura = [
            "facturas_id" => $facturas_id,
            "productos_id" => $producto['productos_id'],
            "clientes_id" => $clientes_id,                
            "fecha" => $fecha,
            "referencia" => $producto['referenciaProducto'],
            "precio_anterior" => $producto['price_anterior'],
            "precio_nuevo" => $producto['price'],                                            
            "fecha_registro" => $fecha_registro                                            
        ];    
        
        $resultPrecioFactura = facturasModelo::valid_precio_factura_modelo($datos_precio_factura);
        
        if($resultPrecioFactura->num_rows == 0) {
            facturasModelo::agregar_precio_factura_clientes($datos_precio_factura);
        }
    }
    
    protected function registrarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total_despues_isv, $estado_cuenta_cobrar, $usuario, $fecha_registro, $empresa_id) {
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
                
        $resultCobrarClientes = facturasModelo::validar_cobrarClientes_modelo($facturas_id);

        if($resultCobrarClientes->num_rows == 0) {
            facturasModelo::agregar_cuenta_por_cobrar_clientes($datos_cobrar_clientes);    
        }
    }
    
    protected function guardarHistorialFactura($clientes_id, $numero, $tipo_factura) {
        $campos = ['nombre', 'rtn'];
        $resultados = mainModel::consultar_tabla('clientes', $campos, "clientes_id = {$clientes_id}");
        
        $nombre = $rtn = null;
        if (!empty($resultados)) {
            $primerResultado = $resultados[0];
            $nombre = $primerResultado['nombre'] ?? null;
            $rtn = $primerResultado['rtn'] ?? null;
        }
        
        $tipo = ($tipo_factura == 1) ? "al contado" : "al crédito";
        
        $datos = [
            "modulo" => 'Facturas',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
            "status" => "Registro",
            "observacion" => "Se registro la factura $numero $tipo para el cliente {$nombre} con el RTN {$rtn}",
            "fecha_registro" => date("Y-m-d H:i:s")
        ];    
        
        mainModel::guardarHistorial($datos);
    }
    
    protected function crearAlerta($tipo, $titulo, $texto, $documento_nombre = null, $facturas_id = null, $tipo_factura = null) {
        $alert = [
            "alert" => "save_simple",
            "title" => $titulo,
            "text" => $texto,
            "type" => $tipo,
            "btn-class" => $tipo == "error" ? "btn-danger" : "btn-primary",
            "btn-text" => "¡Bien Hecho!",
            "form" => "invoice-form",    
            "id" => "proceso_factura",
            "valor" => "Registro",
            "funcion" => "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();",
            "modal" => "",
        ];
        
        if($documento_nombre === "Factura Proforma") {
            $alert['funcion'] = "limpiarTablaFactura();getCajero();printBill(".$facturas_id.");getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
        }
        
        if($tipo_factura == 1 && $documento_nombre !== "Factura Proforma") {
            $alert['funcion'] = "limpiarTablaFactura();pago(".$facturas_id.");getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
        }
        
        return $alert;
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
            $tamano_tabla = $this->validarProductos();

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
                $totales = $this->procesarProductos($facturas_id, $clientes_id, $empresa_id, $fecha, $fecha_registro);
                
                $total_despues_isv = ($totales['total_valor'] + $totales['isv_neto']) - $totales['descuentos'];
                
                //ACTUALIZAMOS EL IMPORTE EN LA FACTURA
                $datos_factura = [
                    "facturas_id" => $facturas_id,
                    "importe" => $total_despues_isv        
                ];
                
                facturasModelo::actualizar_factura_importe($datos_factura);                            
                
                $alert = $this->crearAlerta("success", "Registro almacenado", "El registro se ha almacenado correctamente", null, $facturas_id, $tipo_factura);
                    
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
                $alert = $this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos al parecer no ha seleccionado un producto en el detalle de la factura, antes de proceder debe seleccionar por lo menos un producto para realizar la facturación");
            }                
        }else{
            $alert = $this->crearAlerta("error", "Error Registros en Blanco", "Lo sentimos el cliente y el vendedor no pueden quedar en blanco, por favor corregir");
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