<?php
// facturasControlador.php
if($peticionAjax){
    require_once "../modelos/facturasModelo.php";
}else{
    require_once "./modelos/facturasModelo.php";
}

class facturasControlador extends facturasModelo {

    /* ===========================
     * SECUENCIA: N° FACTURA (con FOR UPDATE y recuperación de fallidos)
     * =========================== */
    protected function obtenerNumeroFactura($empresa_id, $documento_id, $conexion = null) {
        $conexionLocal = false;
        try {
            if($conexion === null) {
                $conexion = mainModel::connection();
                $conexionLocal = true;
                $conexion->begin_transaction();
            }

            // 1) Reusar número fallido si existe
            $sql_fallidos = "SELECT numero FROM secuencia_factura_fallida 
                             WHERE empresa_id = ? AND documento_id = ? 
                             ORDER BY numero ASC LIMIT 1 FOR UPDATE";
            $stmt_fallidos = $conexion->prepare($sql_fallidos);
            $stmt_fallidos->bind_param("ii", $empresa_id, $documento_id);
            $stmt_fallidos->execute();
            $result_fallidos = $stmt_fallidos->get_result();

            if ($result_fallidos->num_rows > 0) {
                $row = $result_fallidos->fetch_assoc();
                $numero_usado = (int)$row['numero'];
                $stmt_fallidos->close();

                $sql_secuencia = "SELECT secuencia_facturacion_id, prefijo, relleno
                                  FROM secuencia_facturacion
                                  WHERE empresa_id = ? AND documento_id = ? AND activo = 1
                                  LIMIT 1";
                $stmt_sec = $conexion->prepare($sql_secuencia);
                $stmt_sec->bind_param("ii", $empresa_id, $documento_id);
                $stmt_sec->execute();
                $res_sec = $stmt_sec->get_result();

                if($res_sec->num_rows === 0){
                    $stmt_sec->close();
                    if($conexionLocal) $conexion->rollback();
                    return ['error'=>true, 'mensaje'=>'No se encontró secuencia activa para esta empresa y documento'];
                }
                $sec = $res_sec->fetch_assoc();
                $stmt_sec->close();

                $del = $conexion->prepare("DELETE FROM secuencia_factura_fallida WHERE empresa_id=? AND documento_id=? AND numero=?");
                $del->bind_param("iii", $empresa_id, $documento_id, $numero_usado);
                $del->execute();
                $del->close();

                if($conexionLocal) $conexion->commit();

                return [
                    'error'=>false,
                    'data'=>[
                        'secuencia_facturacion_id'=>$sec['secuencia_facturacion_id'],
                        'numero'=>$numero_usado,
                        'prefijo'=>$sec['prefijo'] ?? '',
                        'relleno'=>$sec['relleno'] ?? ''
                    ]
                ];
            }
            $stmt_fallidos->close();

            // 2) Secuencia normal
            $sql = "SELECT secuencia_facturacion_id, prefijo, siguiente, rango_final, incremento, relleno
                    FROM secuencia_facturacion
                    WHERE empresa_id = ? AND documento_id = ? AND activo = 1
                    LIMIT 1 FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $empresa_id, $documento_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if($result->num_rows === 0){
                $stmt->close();
                if($conexionLocal) $conexion->rollback();
                return ['error'=>true,'mensaje'=>'No se encontró secuencia activa'];
            }
            $sec = $result->fetch_assoc();
            $stmt->close();

            $siguiente = (int)$sec['siguiente'];
            if($siguiente > (int)$sec['rango_final']){
                if($conexionLocal) $conexion->rollback();
                return ['error'=>true,'mensaje'=>'Se ha alcanzado el límite del rango de numeración'];
            }

            $nuevo = $siguiente + (int)$sec['incremento'];

            $upd = $conexion->prepare("UPDATE secuencia_facturacion SET siguiente=? WHERE secuencia_facturacion_id=?");
            $upd->bind_param("ii", $nuevo, $sec['secuencia_facturacion_id']);
            if(!$upd->execute()){
                $upd->close();
                if($conexionLocal) $conexion->rollback();
                return ['error'=>true,'mensaje'=>'Error al actualizar secuencia'];
            }
            $upd->close();

            if($conexionLocal) $conexion->commit();

            return [
                'error'=>false,
                'data'=>[
                    'secuencia_facturacion_id'=>$sec['secuencia_facturacion_id'],
                    'numero'=>$siguiente,
                    'prefijo'=>$sec['prefijo'],
                    'relleno'=>$sec['relleno']
                ]
            ];
        } catch (Exception $e) {
            if($conexionLocal && isset($conexion)) $conexion->rollback();
            error_log("Error en obtenerNumeroFactura: ".$e->getMessage());
            return ['error'=>true, 'mensaje'=>'Error al generar número de factura: '.$e->getMessage()];
        }
    }

    /* ===========================
     * MAPEO DE DATOS DE FACTURA
     * =========================== */
    protected function prepararDatosFactura($tipo_factura, $tipo_documento) {
        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];

        // Documentos: 0=Factura Electrónica, 1=Proforma (para el front es checkbox)
        $documento_id = ($tipo_documento === "1") ? "4" : "1";
        $documento_nombre = ($tipo_documento === "1") ? "Factura Proforma" : "Factura Electronica";

        // Estado: 2=contado, 3=crédito
        $estado = ($tipo_factura == 1) ? 2 : 3;

        return compact('usuario','empresa_id','documento_id','documento_nombre','estado');
    }

    /* ===========================
     * VALIDACIONES BÁSICAS
     * =========================== */
    protected function validarDatosFormulario() {
        if(empty($_POST['cliente_id']) || empty($_POST['colaborador_id'])) {
            return ['error'=>true,'notification'=>[
                "title"=>"Error","text"=>"El cliente y el vendedor no pueden quedar en blanco","type"=>"error"
            ]];
        }
        if(empty($_POST['productName']) || empty($_POST['productName'][0])) {
            return ['error'=>true,'notification'=>[
                "title"=>"Error","text"=>"Debe seleccionar por lo menos un producto","type"=>"error"
            ]];
        }
        return ['error'=>false];
    }

    /* ===========================
    * DETALLE DE FACTURA (totales por ISV separados)
    * =========================== */
    protected function procesarDetalleFactura($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id) {
        $total_valor  = 0.0;
        $descuentos   = 0.0;
        $isv15_total  = 0.0; // isv_valor
        $isv18_total  = 0.0; // isv_valor1

        for ($i = 0; $i < count($_POST['productName']); $i++) {
            if (empty($_POST['productos_id'][$i]) || empty($_POST['productName'][$i]) ||
                $_POST['quantity'][$i] === '' || $_POST['price'][$i] === '') {
                continue;
            }

            $p = $this->procesarProducto($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id, $i);

            $total_valor += $p['subtotal'];
            $descuentos  += $p['descuento'];
            $isv15_total += $p['isv_valor'];
            $isv18_total += $p['isv_valor1'];
        }

        return [
            'total_valor'       => $total_valor,
            'descuentos'        => $descuentos,
            'isv15_total'       => $isv15_total,
            'isv18_total'       => $isv18_total,
            'total_despues_isv' => ($total_valor - $descuentos) + $isv15_total + $isv18_total,
        ];
    } 

    /* ===========================
    * PRODUCTO (envía isv_valor e isv_valor1 por separado)
    * =========================== */
    protected function procesarProducto($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id, $index) {
        $isv_1 = number_format((float)($_POST['valor_isv'][$index]  ?? 0), 4, '.', '');  // ISV id=1 (monto)
        $isv_2 = number_format((float)($_POST['valor_isv1'][$index] ?? 0), 4, '.', '');  // ISV id=2 (monto)

        $discount       = number_format((float)($_POST['discount'][$index] ?? 0), 4, '.', '');
        $productos_id   = $_POST['productos_id'][$index];
        $quantity       = (float)$_POST['quantity'][$index];
        $price          = number_format((float)$_POST['price'][$index], 4, '.', '');
        $medida         = $_POST['medida'][$index] ?? 'Und';  // ← evita undefined
        $bodega         = $_POST['bodega'][$index] ?? 0;
        $referenciaProd = $_POST['referenciaProducto'][$index] ?? '';
        $price_anterior = number_format((float)($_POST['precio_real'][$index] ?? 0), 4, '.', '');

        $this->guardarDetalleFactura(
            $facturas_id,
            $productos_id,
            $quantity,
            $price,
            $isv_1,  // isv_valor
            $isv_2,  // isv_valor1
            $discount,
            $medida
        );

        $this->procesarInventario($facturas_id, $clientes_id, $productos_id, $quantity, $bodega, $empresa_id, $medida);

        if ($referenciaProd !== "") {
            $this->registrarCambioPrecio($facturas_id, $productos_id, $clientes_id, $fecha, $referenciaProd, $price_anterior, $price, $fecha_registro);
        }

        return [
            'subtotal'   => (float)$price * (float)$quantity,
            'descuento'  => (float)$discount,
            'isv_valor'  => (float)$isv_1,
            'isv_valor1' => (float)$isv_2,
        ];
    }

    /* ===========================
    * GUARDA DETALLE (con isv_valor1)
    * =========================== */
    protected function guardarDetalleFactura($facturas_id, $productos_id, $quantity, $price, $isv_valor, $isv_valor1, $discount, $medida){
        $datos = [
            "facturas_id"  => $facturas_id,
            "productos_id" => $productos_id,
            "cantidad"     => $quantity,
            "precio"       => $price,
            "isv_valor"    => $isv_valor,
            "isv_valor1"   => $isv_valor1,
            "descuento"    => $discount,
            "medida"       => $medida
        ];
        $result = facturasModelo::validDetalleFactura($facturas_id,$productos_id);
        if ($result->num_rows > 0) {
            facturasModelo::actualizar_detalle_facturas($datos);
        } else {
            facturasModelo::agregar_detalle_facturas_modelo($datos);
        }
    }

    /* ===========================
     * INVENTARIO
     * =========================== */
    protected function procesarInventario($facturas_id,$clientes_id,$productos_id,$quantity,$bodega,$empresa_id,$medida){
        $tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);
        if($tipo_producto->num_rows>0){
            $consulta = $tipo_producto->fetch_assoc();
            if($consulta["tipo_producto"] === "Producto"){
                $this->registrarSalidaInventario($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida);
            }
        }
    }

    protected function registrarSalidaInventario($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida){
        $doc = "Factura ".$facturas_id;
        $datos = [
            "productos_id"=>$productos_id,"empresa"=>$empresa_id,"clientes_id"=>$clientes_id ?: 0,
            "comentario"=>"Salida de inventario por venta","almacen_id"=>$bodega ?: 0,
            "cantidad"=>$quantity,"empresa_id"=>$empresa_id,"documento"=>$doc
        ];
        facturasModelo::registrar_salida_lote_modelo($datos);
        $this->procesarRelacionProductos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida);
    }

    protected function procesarRelacionProductos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medida){
        $producto = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
        $producto_padre_id = (int)$producto['id_producto_superior'];
        $medidaName = strtolower($medida);

        if($producto_padre_id === 0) $this->procesarHijos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName);
        else $this->procesarPadre($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName);
    }

    protected function procesarHijos($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName){
        $result = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
        if($result->num_rows>0){
            $valor=0;
            while($c = $result->fetch_assoc()){
                $producto_hijo = (int)$c['productos_id'];
                $cantidad = $this->convertirMedida($quantity,$medidaName,true);
                $this->registrarSalidaHijo($facturas_id,$producto_hijo,$clientes_id,$cantidad,$bodega,$empresa_id,$valor);
                $valor++;
            }
        }
    }

    protected function procesarPadre($facturas_id,$productos_id,$clientes_id,$quantity,$bodega,$empresa_id,$medidaName){
        $result = facturasModelo::cantidad_producto_modelo($productos_id);
        if($result->num_rows>0){
            $valor=0;
            while($c = $result->fetch_assoc()){
                $producto_padre = (int)$c['id_producto_superior'];
                $cantidad = $this->convertirMedida($quantity,$medidaName,false);
                $this->registrarSalidaPadre($facturas_id,$producto_padre,$clientes_id,$cantidad,$bodega,$empresa_id,$valor);
                $valor++;
            }
        }
    }

    protected function convertirMedida($quantity,$medidaName,$esPadre){
        if($medidaName === "ton") return $quantity * 2204.623;
        if($medidaName === "lbs") return $quantity / 2204.623;
        return $quantity;
    }

    protected function registrarSalidaHijo($facturas_id,$producto_id,$clientes_id,$cantidad,$bodega,$empresa_id,$valor){
        $datos = [
            "productos_id"=>$producto_id,"empresa"=>$empresa_id,"clientes_id"=>$clientes_id ?: 0,
            "comentario"=>"Salida de inventario por venta","almacen_id"=>$bodega ?: 0,
            "cantidad"=>$cantidad,"empresa_id"=>$empresa_id,"documento"=>"Factura ".$facturas_id."_".$valor
        ];
        facturasModelo::registrar_salida_lote_modelo($datos);
    }
    protected function registrarSalidaPadre($facturas_id,$producto_id,$clientes_id,$cantidad,$bodega,$empresa_id,$valor){
        $this->registrarSalidaHijo($facturas_id,$producto_id,$clientes_id,$cantidad,$bodega,$empresa_id,$valor);
    }

    /* ===========================
     * PRECIOS
     * =========================== */
    protected function registrarCambioPrecio($facturas_id,$productos_id,$clientes_id,$fecha,$referencia,$precio_anterior,$precio_nuevo,$fecha_registro){
        $datos = [
            "facturas_id"=>$facturas_id,"productos_id"=>$productos_id,"clientes_id"=>$clientes_id,
            "fecha"=>$fecha,"referencia"=>$referencia,"precio_anterior"=>$precio_anterior,
            "precio_nuevo"=>$precio_nuevo,"fecha_registro"=>$fecha_registro
        ];
        $res = facturasModelo::valid_precio_factura_modelo($datos);
        if($res->num_rows==0) facturasModelo::agregar_precio_factura_clientes($datos);
    }

    /* ===========================
     * CXC
     * =========================== */
    protected function guardarCuentaPorCobrar($clientes_id,$facturas_id,$fecha,$total,$estado,$tipo_factura,$usuario,$fecha_registro,$empresa_id){
        $datos = [
            "clientes_id"=>$clientes_id,"facturas_id"=>$facturas_id,"fecha"=>$fecha,
            "saldo"=>$total,"estado"=>$estado,"usuario"=>$usuario,"fecha_registro"=>$fecha_registro,
            "empresa"=>$empresa_id,"tipo_factura"=>$tipo_factura // 1=Contado, 2=Crédito
        ];
        $val = facturasModelo::validar_cobrarClientes_modelo($facturas_id);
        if($val->num_rows==0){
            $ok = facturasModelo::agregar_cuenta_por_cobrar_clientes($datos);
            if(!$ok){ error_log("Error CxC factura: ".$facturas_id); return false; }
        }
        return true;
    }

    /* ===========================
     * HISTORIAL
     * =========================== */
    protected function guardarHistorialFactura($modulo,$status,$observacion){
        $datos = [
            "modulo"=>$modulo,"colaboradores_id"=>$_SESSION['colaborador_id_sd'],
            "status"=>$status,"observacion"=>$observacion,"fecha_registro"=>date("Y-m-d H:i:s")
        ];
        mainModel::guardarHistorial($datos);
    }

    /* ===========================
     * PAGOS MÚLTIPLES
     * =========================== */
    protected function procesarPagosMultiples($facturas_id,$total_pagado){
        $saldo_cero = facturasModelo::verificar_saldo_cero($facturas_id);
        if($saldo_cero){
            facturasModelo::actualizar_estado_pago_completo($facturas_id);
            return "printBill(".$facturas_id.");";
        }
        return "";
    }

    /* ===========================
     * CADENA JS POST-GUARDADO CENTRALIZADA
     * =========================== */
    protected function armarFuncionesPostGuardado($facturas_id, $tipo_documento, $tipo_factura, $funcion_pagos = ""){
        // Comunes
        $base = "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
        // Siempre dejamos la UI en Contado para evitar errores de los usuarios
        $resetTipo = "setTipoFactura(\"contado\");";

        if($tipo_documento === "1"){ // Proforma
            return $base."printBill({$facturas_id});".$resetTipo;
        }

        // Factura normal:
        if((int)$tipo_factura === 1){
            // Contado: va a flujo de pago y luego quedamos en contado igual
            return $base."pago({$facturas_id}, 1);getTotalFacturasDisponibles();".$funcion_pagos.$resetTipo;
        }else{
            // Crédito: imprimimos de una vez y dejamos contado al final
            return $base."printBill({$facturas_id});".$funcion_pagos.$resetTipo;
        }
    }

    /* ===========================
     * AGREGAR FACTURAS
     * =========================== */
    public function agregar_facturas_controlador() {
        $conexionPrincipal = mainModel::connection();
        $conexionPrincipal->begin_transaction();
    
        try {
            // 0) Sesión
            $validacion = mainModel::validarSesion();
            if ($validacion['error']) {
                $conexionPrincipal->rollback();
                return mainModel::showNotification([
                    "title"=>"Error de sesión","text"=>$validacion['mensaje'],"type"=>"error",
                    "funcion"=>"window.location.href = '".$validacion['redireccion']."'"
                ]);
            }
    
            // 1) Límite del plan (igual que tenías)
            $mainModel = new mainModel();
            $planConfig = $mainModel->getPlanConfiguracionMainModel();
            if (isset($planConfig['facturas'])) {
                $limite = (int)$planConfig['facturas'];
                if ($limite === 0) {
                    $conexionPrincipal->rollback();
                    return mainModel::showNotification([
                        "type"=>"error","title"=>"Acceso restringido","text"=>"Su plan no incluye la creación de facturas."
                    ]);
                }
                $totalReg = (int)facturasModelo::getTotalFacturasRegistradas();
                if ($totalReg >= $limite) {
                    $conexionPrincipal->rollback();
                    return mainModel::showNotification([
                        "type"=>"error","title"=>"Límite alcanzado","text"=>"Ha excedido el límite mensual de facturas (Máximo: $limite)."
                    ]);
                }
            }
    
            // 2) Normalización UI: 1=contado, 0=crédito  -> backend: 1/2
            $tipo_factura_input = isset($_POST['facturas_activo']) ? intval($_POST['facturas_activo']) : 1;
            $tipo_factura       = ($tipo_factura_input === 1) ? 1 : 2;
    
            // Proforma
            $tipo_documento_input = isset($_POST['facturas_proforma']) ? intval($_POST['facturas_proforma']) : 0; // 1=proforma
            $tipo_documento       = ($tipo_documento_input === 1) ? "1" : "0";
    
            // 3) Datos base
            $datosBasicos = $this->prepararDatosFactura($tipo_factura,$tipo_documento); // aquí estado= 2/3 (finalizada)
            $estado_final = $datosBasicos['estado']; // 2 contado / 3 crédito
    
            // 4) Validación
            $valid = $this->validarDatosFormulario();
            if($valid['error']){
                $conexionPrincipal->rollback();
                return mainModel::showNotification($valid['notification']);
            }
    
            // 5) Datos comunes
            $clientes_id    = $_POST['cliente_id'];
            $colaborador_id = $_POST['colaborador_id'];
            $notas          = mainModel::cleanString($_POST['notesBill']);
            $fecha          = $_POST['fecha'];
            $fecha_dolar    = $_POST['fecha_dolar'];
            $fecha_registro = date("Y-m-d H:i:s");
    
            // Exoneración (evitar warnings)
            $exoneracion_orden          = $_POST['exoneracion_orden']          ?? null;
            $exoneracion_constancia     = $_POST['exoneracion_constancia']     ?? null;
            $exoneracion_sag            = $_POST['exoneracion_sag']            ?? null;
            $exoneracion_orden_interno  = $_POST['exoneracion_orden_interno']  ?? null;
    
            // 6) ¿Viene desde borrador?
            $facturas_id = empty($_POST['facturas_id']) ? null : $_POST['facturas_id'];
            $esBorradorPrevio = false;
            $numeroFactura = null;
    
            if ($facturas_id) {
                // verificar si era borrador (estado=1 y number=0) o simplemente actualizar una ya existente
                $rowF = mainModel::consultar_tabla('facturas',['estado','number','secuencia_facturacion_id'],"facturas_id = {$facturas_id}");
                if (!empty($rowF)) {
                    $estado_actual = (int)$rowF[0]['estado'];
                    $numero_actual = (int)$rowF[0]['number'];
                    $esBorradorPrevio = ($estado_actual === 1); // te basta este check
                }
            } else {
                // generar nuevo id si no vino
                $facturas_id = mainModel::correlativo("facturas_id","facturas");
            }
    
            // 7) Apertura de caja
            $apertura = facturasModelo::getAperturaIDModelo([
                "colaboradores_id"=>$datosBasicos['usuario'],"fecha"=>$fecha,"estado"=>1
            ])->fetch_assoc();
            $apertura_id = $apertura['apertura_id'];
    
            // 8) Tomar número SOLO ahora (finalización)
            $empresa_id   = $_SESSION['empresa_id_sd'];
            $documento_id = ($tipo_documento === "1") ? "4" : "1";
            $numeroFactura = $this->obtenerNumeroFactura($empresa_id,$documento_id,$conexionPrincipal);
            if($numeroFactura['error']){
                $conexionPrincipal->rollback();
                return mainModel::showNotification(["title"=>"Error","text"=>$numeroFactura['mensaje'],"type"=>"error"]);
            }
    
            // 9) Guardar/actualizar encabezado con importe provisional (0). Estado final 2/3.
            $datosFactura = [
                "facturas_id"               => $facturas_id,
                "clientes_id"               => $clientes_id,
                "secuencia_facturacion_id"  => $numeroFactura['data']['secuencia_facturacion_id'],
                "apertura_id"               => $apertura_id,
                "tipo_factura"              => $tipo_factura,
                "numero"                    => $numeroFactura['data']['numero'],
                "colaboradores_id"          => $colaborador_id,
                "importe"                   => 0,
                "notas"                     => $notas,
                "fecha"                     => $fecha,
                "estado"                    => $estado_final,  // << FINALIZADA (2/3)
                "usuario"                   => $datosBasicos['usuario'],
                "fecha_registro"            => $fecha_registro,
                "empresa"                   => $datosBasicos['empresa_id'],
                "fecha_dolar"               => $fecha_dolar,
                "exoneracion_orden"         => $exoneracion_orden,
                "exoneracion_constancia"    => $exoneracion_constancia,
                "exoneracion_sag"           => $exoneracion_sag,
                "exoneracion_orden_interno" => $exoneracion_orden_interno
            ];
            // Si tu modelo hace upsert, perfecto; si no, usa update cuando $esBorradorPrevio sea true.
            $okHead = facturasModelo::guardar_facturas_modelo($datosFactura);
            if(!$okHead){
                $conexionPrincipal->rollback();
                return mainModel::showNotification(["title"=>"Error","text"=>"No hemos podido procesar su solicitud","type"=>"error"]);
            }
    
            // 10) Detalle + totales (aquí ya finaliza: se afectará inventario según tu lógica actual)
            $totales = $this->procesarDetalleFactura($facturas_id,$clientes_id,$fecha,$fecha_registro,$datosBasicos['empresa_id']);
    
            // 11) Actualizar importe real
            $okImporte = facturasModelo::actualizar_factura_importe([
                "facturas_id"=>$facturas_id,"importe"=>$totales['total_despues_isv']
            ]);
            if(!$okImporte){
                $conexionPrincipal->rollback();
                return mainModel::showNotification(["title"=>"Error","text"=>"Error al actualizar el importe de la factura","type"=>"error"]);
            }
    
            // 12) CxC (solo finalizadas)
            $estado_cuenta = 1;
            $okCxC = $this->guardarCuentaPorCobrar(
                $clientes_id,$facturas_id,$fecha,$totales['total_despues_isv'],
                $estado_cuenta,$tipo_factura,$datosBasicos['usuario'],$fecha_registro,$datosBasicos['empresa_id']
            );
            if(!$okCxC){
                $conexionPrincipal->rollback();
                return mainModel::showNotification(["title"=>"Error","text"=>"Error al registrar la cuenta por cobrar","type"=>"error"]);
            }
    
            // 13) Historial
            $cliente = mainModel::consultar_tabla('clientes',['nombre','rtn'],"clientes_id = {$clientes_id}")[0];
            $tipoTxt = ($tipo_factura==1)?'contado':'crédito';
            $this->guardarHistorialFactura('Facturas','Registro',"Se registró la factura al {$tipoTxt} para el cliente {$cliente['nombre']} con el RTN {$cliente['rtn']}");
    
            // 14) Pagos múltiples si aplica
            $funcion_pagos = "";
            if(isset($_POST['total_pagado'])) $funcion_pagos = $this->procesarPagosMultiples($facturas_id, $_POST['total_pagado']);
    
            // 15) Cadena JS de salida (centralizada)
            $funcion = $this->armarFuncionesPostGuardado($facturas_id, $tipo_documento, $tipo_factura, $funcion_pagos);
    
            $conexionPrincipal->commit();
    
            return mainModel::showNotification([
                "type"=>"success","title"=>"Registro almacenado","text"=>"El registro se ha almacenado correctamente",
                "form"=>"invoice-form","funcion"=>$funcion
            ]);
    
        } catch (Exception $e) {
            if(isset($conexionPrincipal)) $conexionPrincipal->rollback();
    
            // Si ya tomamos número, márcalo como fallido para reuso
            if (isset($numeroFactura['data']['numero'])) {
                $numero = $numeroFactura['data']['numero'];
                $conexion = mainModel::connection();
                $stmt = $conexion->prepare("INSERT IGNORE INTO secuencia_factura_fallida (empresa_id, documento_id, numero) VALUES (?, ?, ?)");
                $documento_id_fallido = isset($documento_id) ? (int)$documento_id : 1;
                $empresa_ctx = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
                $stmt->bind_param("iii", $empresa_ctx, $documento_id_fallido, $numero);
                $stmt->execute();
                $stmt->close();
            }
    
            error_log("Error en agregar_facturas_controlador: ".$e->getMessage());
            return mainModel::showNotification([
                "title"=>"Error","text"=>"Ocurrió un error al procesar la factura: ".$e->getMessage(),"type"=>"error"
            ]);
        }
    }    

    /* ===========================
    * AGREGAR FACTURAS (BORRADOR)
    * =========================== */
    public function agregar_facturas_open_controlador() {
        if(!isset($_SESSION['user_sd'])) session_start(['name'=>'SD']);

        // 1) Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return mainModel::showNotification([
                "title"=>"Error de sesión","text"=>$validacion['mensaje'],"type"=>"error",
                "funcion"=>"window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        // 2) Normalización UI: 1=contado, 0=crédito  -> backend: 1/2
        $tipo_factura_input = isset($_POST['facturas_activo']) ? (int)$_POST['facturas_activo'] : 1;
        $tipo_factura       = ($tipo_factura_input === 1) ? 1 : 2;

        // (Opcional) Proforma en borrador
        $tipo_documento_input = isset($_POST['facturas_proforma']) ? (int)$_POST['facturas_proforma'] : 0;
        $tipo_documento       = ($tipo_documento_input === 1) ? "1" : "0";

        // 3) Datos base
        $datosBasicos = $this->prepararDatosFactura($tipo_factura, $tipo_documento);
        $estado_borrador = 1; // SIEMPRE borrador

        // 4) Validación mínima
        $valid = $this->validarDatosFormulario();
        if($valid['error']) return mainModel::showNotification($valid['notification']);

        // 5) Datos comunes
        $clientes_id     = $_POST['cliente_id'];
        $colaborador_id  = $_POST['colaborador_id'];
        $notas           = mainModel::cleanString($_POST['notesBill']);
        $fecha           = $_POST['fecha'];
        $fecha_dolar     = $_POST['fecha_dolar'];
        $fecha_registro  = date("Y-m-d H:i:s");

        // Exoneración
        $exoneracion_orden          = $_POST['exoneracion_orden']          ?? null;
        $exoneracion_constancia     = $_POST['exoneracion_constancia']     ?? null;
        $exoneracion_sag            = $_POST['exoneracion_sag']            ?? null;
        $exoneracion_orden_interno  = $_POST['exoneracion_orden_interno']  ?? null;

        // correlativo y bandera de edición
        $facturas_id = empty($_POST['facturas_id']) ? mainModel::correlativo("facturas_id","facturas") : $_POST['facturas_id'];
        $Existe      = !empty($_POST['facturas_id']);

        // 6) Transacción
        $cn = mainModel::connection();
        $cn->begin_transaction();
        try {
            // 6.1) Apertura de caja
            $apertura = facturasModelo::getAperturaIDModelo([
                "colaboradores_id"=>$datosBasicos['usuario'],
                "fecha"=>$fecha,
                "estado"=>1
            ])->fetch_assoc();
            $apertura_id = $apertura['apertura_id'];

            // 6.2) OBTENER **SOLO** secuencia activa (NO consumir número)
            $documento_id = $datosBasicos['documento_id']; // "1" normal o "4" proforma (según tu mapeo)
            $sec_id = null;
            $stmtSec = $cn->prepare("
                SELECT secuencia_facturacion_id 
                FROM secuencia_facturacion 
                WHERE empresa_id = ? AND documento_id = ? AND activo = 1
                LIMIT 1
            ");
            $empresa_id = (int)$datosBasicos['empresa_id'];
            $doc_id_int = (int)$documento_id;
            $stmtSec->bind_param("ii", $empresa_id, $doc_id_int);
            $stmtSec->execute();
            $resSec = $stmtSec->get_result();
            if ($resSec->num_rows === 0) {
                $stmtSec->close();
                $cn->rollback();
                return mainModel::showNotification([
                    "title"=>"Error","text"=>"No hay secuencia de facturación activa para este documento.","type"=>"error"
                ]);
            }
            $sec_id = (int)$resSec->fetch_assoc()['secuencia_facturacion_id'];
            $stmtSec->close();

            // 6.3) Guardar/actualizar encabezado (numero=0, secuencia sí se guarda)
            $datosFactura = [
                "facturas_id"               => $facturas_id,
                "clientes_id"               => $clientes_id,
                "secuencia_facturacion_id"  => $sec_id,          // << SE CUELGA LA SECUENCIA
                "apertura_id"               => $apertura_id,
                "tipo_factura"              => $tipo_factura,
                "numero"                    => 0,                // << SIN NÚMERO EN BORRADOR
                "colaboradores_id"          => $colaborador_id,
                "importe"                   => 0,                // provisional
                "notas"                     => $notas,
                "fecha"                     => $fecha,
                "estado"                    => $estado_borrador, // 1 = borrador
                "usuario"                   => $datosBasicos['usuario'],
                "fecha_registro"            => $fecha_registro,
                "empresa"                   => $empresa_id,
                "fecha_dolar"               => $fecha_dolar,
                "exoneracion_orden"         => $exoneracion_orden,
                "exoneracion_constancia"    => $exoneracion_constancia,
                "exoneracion_sag"           => $exoneracion_sag,
                "exoneracion_orden_interno" => $exoneracion_orden_interno,
            ];
            facturasModelo::guardar_facturas_modelo($datosFactura);

            // 6.4) Detalle + totales
            $totales = $this->procesarDetalleFactura(
                $facturas_id,
                $clientes_id,
                $fecha,
                $fecha_registro,
                $empresa_id
            );

            // 6.5) Actualizar IMPORTE real
            $okImporte = facturasModelo::actualizar_factura_importe([
                "facturas_id" => $facturas_id,
                "importe"     => $totales['total_despues_isv']
            ]);
            if(!$okImporte){
                $cn->rollback();
                return mainModel::showNotification([
                    "title"=>"Error","text"=>"Error al actualizar el importe de la factura (borrador)","type"=>"error"
                ]);
            }

            // 6.6) Borrador: NO CxC

            // 6.7) Commit
            $cn->commit();

            // 6.8) UI
            $tipo_txt = ($tipo_factura === 1 ? 'contado' : 'crédito');
            $funcion  = "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";

            return mainModel::showNotification([
                "type"=>"success",
                "title"=>"Registro almacenado",
                "text"=>"Factura en borrador registrada al {$tipo_txt}",
                "form"=>"invoice-form",
                "funcion"=>$funcion
            ]);

        } catch (Exception $e) {
            if(isset($cn)) $cn->rollback();
            // No hubo consumo de número → no registrar fallidos
            error_log("Error en agregar_facturas_open_controlador: ".$e->getMessage());
            return mainModel::showNotification([
                "title"=>"Error",
                "text"=>"Ocurrió un error al procesar la factura en borrador: ".$e->getMessage(),
                "type"=>"error"
            ]);
        }
    }
    
    /* ===========================
     * CANCELAR FACTURA
     * =========================== */
    public function cancelar_facturas_controlador() {
        $facturas_id = $_POST['facturas_id'];
        $factura = mainModel::consultar_tabla('facturas',['number'],"facturas_id = {$facturas_id}");
        $number = $factura[0]['number'] ?? null;

        $ok = facturasModelo::cancelar_facturas_modelo($facturas_id);
        if($ok){
            $this->guardarHistorialFactura('Facturas','Cancelar',"Se canceló la factura {$number}");
            return mainModel::showNotification(["type"=>"success","title"=>"Registro eliminado","text"=>"El registro se ha eliminado correctamente","funcion"=>"" ]);
        }

        return mainModel::showNotification(["title"=>"Error","text"=>"No hemos podido procesar su solicitud","type"=>"error"]);
    }
}