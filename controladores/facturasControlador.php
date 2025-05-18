<?php
//facturasControlador.php
if($peticionAjax){
    require_once "../modelos/facturasModelo.php";
}else{
    require_once "./modelos/facturasModelo.php";
}

class facturasControlador extends facturasModelo {
    // Método para obtener el número de factura con manejo de condición de carrera
    protected function obtenerNumeroFactura($empresa_id, $documento_id) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        
        try {
            // Obtener y bloquear la secuencia
            $secuenciaData = facturasModelo::bloquear_y_obtener_secuencia_modelo($empresa_id, $documento_id);
            
            if(!$secuenciaData) {
                $conexion->rollback();
                return [
                    'error' => true,
                    'mensaje' => 'No se encontró una secuencia de facturación activa'
                ];
            }
            
            // Verificar rango final
            $siguiente_numero = $secuenciaData['siguiente'] + $secuenciaData['incremento'];
            if($siguiente_numero > $secuenciaData['rango_final']) {
                $conexion->rollback();
                return [
                    'error' => true,
                    'mensaje' => 'Se ha alcanzado el límite del rango autorizado de facturación'
                ];
            }
            
            // Si todo está bien, confirmar la transacción
            $conexion->commit();
            
            return [
                'error' => false,
                'data' => [
                    'secuencia_facturacion_id' => $secuenciaData['secuencia_facturacion_id'],
                    'numero' => $secuenciaData['siguiente'],
                    'incremento' => $secuenciaData['incremento'],
                    'prefijo' => $secuenciaData['prefijo'],
                    'relleno' => $secuenciaData['relleno'],
                    'rango_final' => $secuenciaData['rango_final'],
                    'conexion' => $conexion // Añadimos la conexión al array de retorno
                ]
            ];
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error al obtener número de factura: " . $e->getMessage());
            return [
                'error' => true,
                'mensaje' => 'Error al generar el número de factura'
            ];
        }
    }

    // Método para confirmar la numeración de factura (se llama después de guardar la factura exitosamente)
    protected function confirmarNumeracionFactura($secuencia_facturacion_id, $nuevo_numero, $conexion) {
        try {
            $updateSecuencia = facturasModelo::actualizar_secuencia_modelo(
                $secuencia_facturacion_id, 
                $nuevo_numero,
                $conexion
            );
            
            if(!$updateSecuencia) {
                throw new Exception("Error al actualizar la secuencia de facturación");
            }
            
            $conexion->commit();
            return true;
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error al confirmar numeración: " . $e->getMessage());
            return false;
        }
    }

    // Método para preparar datos básicos de la factura
    protected function prepararDatosFactura($tipo_factura, $tipo_documento) {
        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];
        
        $documento_id = "1";
        $documento_nombre = "Factura Electronica";

        if($tipo_documento === "1"){
            $documento_id = "4";
            $documento_nombre = "Factura Proforma";
        }

        return [
            'usuario' => $usuario,
            'empresa_id' => $empresa_id,
            'documento_id' => $documento_id,
            'documento_nombre' => $documento_nombre,
            'estado' => ($tipo_factura == 1) ? 2 : 3 // 2 para contado, 3 para crédito
        ];
    }

    // Método para validar datos básicos del formulario
    protected function validarDatosFormulario() {
        if(empty($_POST['cliente_id']) || empty($_POST['colaborador_id'])) {
            return [
                'error' => true,
                'notification' => [
                    "title" => "Error",
                    "text" => "El cliente y el vendedor no pueden quedar en blanco",
                    "type" => "error"
                ]
            ];
        }
    
        if(empty($_POST['productName']) || empty($_POST['productName'][0])) {
            return [
                'error' => true,
                'notification' => [
                    "title" => "Error",
                    "text" => "Debe seleccionar por lo menos un producto",
                    "type" => "error"
                ]
            ];
        }
    
        return ['error' => false];
    }

    // Método para procesar el detalle de la factura
    protected function procesarDetalleFactura($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id) {
        $total_valor = 0;
        $descuentos = 0;
        $isv_neto = 0;
        
        for ($i = 0; $i < count($_POST['productName']); $i++) {
            if(empty($_POST['productos_id'][$i]) || empty($_POST['productName'][$i]) || 
               empty($_POST['quantity'][$i]) || empty($_POST['price'][$i])) {
                continue;
            }

            $producto = $this->procesarProducto(
                $facturas_id, 
                $clientes_id, 
                $fecha, 
                $fecha_registro, 
                $empresa_id, 
                $i
            );

            $total_valor += $producto['subtotal'];
            $descuentos += $producto['descuento'];
            $isv_neto += $producto['isv_valor'];
        }

        return [
            'total_valor' => $total_valor,
            'descuentos' => $descuentos,
            'isv_neto' => $isv_neto,
            'total_despues_isv' => ($total_valor + $isv_neto) - $descuentos
        ];
    }

    // Método para procesar cada producto individual
    protected function procesarProducto($facturas_id, $clientes_id, $fecha, $fecha_registro, $empresa_id, $index) {
        $discount = $_POST['discount'][$index] ?? 0;
        $isv_valor = $_POST['valor_isv'][$index] ?? 0;
        $productos_id = $_POST['productos_id'][$index];
        $quantity = $_POST['quantity'][$index];
        $price = $_POST['price'][$index];
        $medida = $_POST['medida'][$index];
        $bodega = $_POST['bodega'][$index] ?? 0;
        $referenciaProducto = $_POST['referenciaProducto'][$index] ?? '';
        $price_anterior = $_POST['precio_real'][$index] ?? 0;

        // Guardar detalle de factura
        $this->guardarDetalleFactura(
            $facturas_id, 
            $productos_id, 
            $quantity, 
            $price, 
            $isv_valor, 
            $discount, 
            $medida
        );

        // Procesar producto en inventario si es necesario
        $this->procesarInventario(
            $facturas_id, 
            $clientes_id, 
            $productos_id, 
            $quantity, 
            $bodega, 
            $empresa_id, 
            $medida
        );

        // Registrar cambio de precio si hay referencia
        if($referenciaProducto != "") {
            $this->registrarCambioPrecio(
                $facturas_id, 
                $productos_id, 
                $clientes_id, 
                $fecha, 
                $referenciaProducto, 
                $price_anterior, 
                $price, 
                $fecha_registro
            );
        }

        return [
            'subtotal' => $price * $quantity,
            'descuento' => $discount,
            'isv_valor' => $isv_valor
        ];
    }

    // Método para guardar detalle de factura
    protected function guardarDetalleFactura($facturas_id, $productos_id, $quantity, $price, $isv_valor, $discount, $medida) {
        $datos = [
            "facturas_id" => $facturas_id,
            "productos_id" => $productos_id,
            "cantidad" => $quantity,                
            "precio" => $price,
            "isv_valor" => $isv_valor,
            "descuento" => $discount,
            "medida" => $medida
        ];

        $result = facturasModelo::validDetalleFactura($facturas_id, $productos_id);
        
        if($result->num_rows > 0) {
            facturasModelo::actualizar_detalle_facturas($datos);
        } else {
            facturasModelo::agregar_detalle_facturas_modelo($datos);
        }
    }

    // Método para procesar inventario
    protected function procesarInventario($facturas_id, $clientes_id, $productos_id, $quantity, $bodega, $empresa_id, $medida) {
        $tipo_producto = facturasModelo::tipo_producto_modelo($productos_id);
        
        if($tipo_producto->num_rows > 0) {
            $consulta = $tipo_producto->fetch_assoc();
            if($consulta["tipo_producto"] == "Producto") {
                $this->registrarSalidaInventario(
                    $facturas_id, 
                    $productos_id, 
                    $clientes_id, 
                    $quantity, 
                    $bodega, 
                    $empresa_id, 
                    $medida
                );
            }
        }
    }

    // Método para registrar salida de inventario
    protected function registrarSalidaInventario($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medida) {
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

        // Procesar productos padre/hijo si es necesario
        $this->procesarRelacionProductos($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medida);
    }

    // Método para procesar relación entre productos (padre/hijo)
    protected function procesarRelacionProductos($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medida) {
        $producto = facturasModelo::cantidad_producto_modelo($productos_id)->fetch_assoc();
        $producto_padre_id = $producto['id_producto_superior'];
        $medidaName = strtolower($medida);

        if($producto_padre_id == 0) { // Es producto padre
            $this->procesarHijos($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medidaName);
        } else { // Es producto hijo
            $this->procesarPadre($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medidaName);
        }
    }

    // Método para procesar productos hijos
    protected function procesarHijos($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medidaName) {
        $result = facturasModelo::total_hijos_segun_padre_modelo($productos_id);
        
        if($result->num_rows > 0) {
            $valor = 0;
            while($consulta = $result->fetch_assoc()) {
                $producto_id_hijo = intval($consulta['productos_id']);
                $cantidad = $this->convertirMedida($quantity, $medidaName, true);
                
                $this->registrarSalidaHijo(
                    $facturas_id, 
                    $producto_id_hijo, 
                    $clientes_id, 
                    $cantidad, 
                    $bodega, 
                    $empresa_id, 
                    $valor
                );
                $valor++;
            }
        }
    }

    // Método para procesar producto padre
    protected function procesarPadre($facturas_id, $productos_id, $clientes_id, $quantity, $bodega, $empresa_id, $medidaName) {
        $result = facturasModelo::cantidad_producto_modelo($productos_id);
        
        if($result->num_rows > 0) {
            $valor = 0;
            while($consulta = $result->fetch_assoc()) {
                $producto_id_padre = intval($consulta['id_producto_superior']);
                $cantidad = $this->convertirMedida($quantity, $medidaName, false);
                
                $this->registrarSalidaPadre(
                    $facturas_id, 
                    $producto_id_padre, 
                    $clientes_id, 
                    $cantidad, 
                    $bodega, 
                    $empresa_id, 
                    $valor
                );
                $valor++;
            }
        }
    }

    // Método para convertir medidas (ton/lbs)
    protected function convertirMedida($quantity, $medidaName, $esPadre) {
        if($medidaName == "ton") {
            return $esPadre ? $quantity * 2204.623 : $quantity * 2204.623;
        }
        
        if($medidaName == "lbs") {
            return $esPadre ? $quantity / 2204.623 : $quantity / 2204.623;
        }
        
        return $quantity;
    }

    // Métodos auxiliares para registrar salidas
    protected function registrarSalidaHijo($facturas_id, $producto_id, $clientes_id, $cantidad, $bodega, $empresa_id, $valor) {
        $datos = [
            "productos_id" => $producto_id,
            "empresa" => $empresa_id,
            "clientes_id" => $clientes_id ?: 0,
            "comentario" => "Salida de inventario por venta",
            "almacen_id" => $bodega ?: 0,
            "cantidad" => $cantidad,
            "empresa_id" => $empresa_id,
            "documento" => "Factura ".$facturas_id."_".$valor
        ];
        
        facturasModelo::registrar_salida_lote_modelo($datos);
    }

    protected function registrarSalidaPadre($facturas_id, $producto_id, $clientes_id, $cantidad, $bodega, $empresa_id, $valor) {
        $this->registrarSalidaHijo($facturas_id, $producto_id, $clientes_id, $cantidad, $bodega, $empresa_id, $valor);
    }

    // Método para registrar cambio de precio
    protected function registrarCambioPrecio($facturas_id, $productos_id, $clientes_id, $fecha, $referencia, $precio_anterior, $precio_nuevo, $fecha_registro) {
        $datos = [
            "facturas_id" => $facturas_id,
            "productos_id" => $productos_id,
            "clientes_id" => $clientes_id,                
            "fecha" => $fecha,
            "referencia" => $referencia,
            "precio_anterior" => $precio_anterior,
            "precio_nuevo" => $precio_nuevo,                                            
            "fecha_registro" => $fecha_registro                                            
        ];

        $result = facturasModelo::valid_precio_factura_modelo($datos);
        
        if($result->num_rows == 0) {
            facturasModelo::agregar_precio_factura_clientes($datos);
        }
    }

    // Método para guardar cuenta por cobrar
    protected function guardarCuentaPorCobrar($clientes_id, $facturas_id, $fecha, $total, $estado, $tipo_factura, $usuario, $fecha_registro, $empresa_id) {
        $datos = [
            "clientes_id" => $clientes_id,
            "facturas_id" => $facturas_id,
            "fecha" => $fecha,                
            "saldo" => $total,
            "estado" => $estado,
            "usuario" => $usuario,
            "fecha_registro" => $fecha_registro,
            "empresa" => $empresa_id,
            "tipo_factura" => $tipo_factura // 1=Contado, 2=Crédito
        ];
    
        $result = facturasModelo::validar_cobrarClientes_modelo($facturas_id);
        
        if($result->num_rows == 0) {
            $insertResult = facturasModelo::agregar_cuenta_por_cobrar_clientes($datos);
            if(!$insertResult) {
                error_log("Error al guardar cuenta por cobrar para factura: " . $facturas_id);
                return false;
            }
            return true;
        }        
        
        return true;
    }

    // Método para guardar historial
    protected function guardarHistorialFactura($modulo, $status, $observacion) {
        $datos = [
            "modulo" => $modulo,
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
            "status" => $status,
            "observacion" => $observacion,
            "fecha_registro" => date("Y-m-d H:i:s")
        ];
        
        mainModel::guardarHistorial($datos);
    }

    // Método para procesar pagos múltiples
    protected function procesarPagosMultiples($facturas_id, $total_pagado) {
        // Verificar si el saldo es cero
        $saldo_cero = facturasModelo::verificar_saldo_cero($facturas_id);
        
        if($saldo_cero) {
            // Actualizar estado a pago completo
            facturasModelo::actualizar_estado_pago_completo($facturas_id);
            
            // Retornar función para imprimir factura
            return "printBill(".$facturas_id.");";
        }
        
        return "";
    }

    // Método principal para agregar facturas
    public function agregar_facturas_controlador() {
        // Iniciar transacción principal
        $conexionPrincipal = mainModel::connection();
        $conexionPrincipal->begin_transaction();

        try {
            // Validar sesión
            $validacion = mainModel::validarSesion();
            if ($validacion['error']) {
                $conexionPrincipal->rollback();
                return mainModel::showNotification([
                    "title" => "Error de sesión",
                    "text" => $validacion['mensaje'],
                    "type" => "error",
                    "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
                ]);
            }
            
            $mainModel = new mainModel();
            $planConfig = $mainModel->getPlanConfiguracionMainModel();
            
            // Validar límite de facturas del plan
            if (!empty($planConfig)) {
                $limiteFacturas = (int)($planConfig['facturas'] ?? 0);
                
                if ($limiteFacturas === 0) {
                    $conexionPrincipal->rollback();
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Acceso restringido",
                        "text" => "Su plan no incluye la creación de facturas."
                    ]);
                }
                
                $totalRegistradas = (int)facturasModelo::getTotalFacturasRegistradas();
                
                if ($totalRegistradas >= $limiteFacturas) {
                    $conexionPrincipal->rollback();
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Límite alcanzado",
                        "text" => "Ha excedido el límite mensual de facturas (Máximo: $limiteFacturas)."
                    ]);
                }
            }

            // Obtener tipo de factura y documento
            $tipo_factura = $_POST['facturas_activo'] ?? 2; //1. CONTADO, 2. CREDITO
            $tipo_documento = $_POST['facturas_proforma'] ?? 0; //0. FACTURA ELECTRONICA, 1. FACTURA PROFORMA
            
            // Preparar datos básicos
            $datosBasicos = $this->prepararDatosFactura($tipo_factura, $tipo_documento);
            
            // Validar datos del formulario
            $validacion = $this->validarDatosFormulario();
            if($validacion['error']) {
                $conexionPrincipal->rollback();
                return mainModel::showNotification($validacion['notification']);
            }
            
            $empresa_id = $_SESSION['empresa_id_sd'];
            $documento_id = "1"; // Factura Electronica por defecto
            if($tipo_documento === "1"){ // Si es proforma
                $documento_id = "4";
            }

            // Obtener número de factura (dentro de una transacción separada que manejaremos)
            $numeroFactura = $this->obtenerNumeroFactura($empresa_id, $documento_id);
            if($numeroFactura['error']) {
                $conexionPrincipal->rollback();
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => $numeroFactura['mensaje'],
                    "type" => "error"
                ]);
            }
            
            // Datos comunes
            $clientes_id = $_POST['cliente_id'];
            $colaborador_id = $_POST['colaborador_id'];
            $notas = mainModel::cleanString($_POST['notesBill']);
            $fecha = $_POST['fecha'];
            $fecha_dolar = $_POST['fecha_dolar'];
            $fecha_registro = date("Y-m-d H:i:s");
            
            // Obtener ID de factura
            $facturas_id = empty($_POST['facturas_id']) ? mainModel::correlativo("facturas_id", "facturas") : $_POST['facturas_id'];
            
            // Obtener apertura
            $apertura = facturasModelo::getAperturaIDModelo([
                "colaboradores_id" => $datosBasicos['usuario'],
                "fecha" => $fecha,
                "estado" => 1
            ])->fetch_assoc();
            
            $apertura_id = $apertura['apertura_id'];
            
            // Guardar factura
            $datosFactura = [
                "facturas_id" => $facturas_id,
                "clientes_id" => $clientes_id,
                "secuencia_facturacion_id" => $numeroFactura['data']['secuencia_facturacion_id'],
                "apertura_id" => $apertura_id,                
                "tipo_factura" => $tipo_factura,                
                "numero" => $numeroFactura['data']['numero'],
                "colaboradores_id" => $colaborador_id,
                "importe" => 0,
                "notas" => $notas,
                "fecha" => $fecha,                
                "estado" => $datosBasicos['estado'],
                "usuario" => $datosBasicos['usuario'],
                "fecha_registro" => $fecha_registro,
                "empresa" => $datosBasicos['empresa_id'],
                "fecha_dolar" => $fecha_dolar
            ];
            
            $query = facturasModelo::guardar_facturas_modelo($datosFactura);
            
            if(!$query) {
                $conexionPrincipal->rollback();
                if(isset($numeroFactura['data']['conexion'])) {
                    $numeroFactura['data']['conexion']->rollback();
                }
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No hemos podido procesar su solicitud",
                    "type" => "error"
                ]);
            }

            // Procesar detalle de factura
            $totales = $this->procesarDetalleFactura(
                $facturas_id, 
                $clientes_id, 
                $fecha, 
                $fecha_registro, 
                $datosBasicos['empresa_id']
            );
            
            // Actualizar importe en factura
            $updateImporte = facturasModelo::actualizar_factura_importe([
                "facturas_id" => $facturas_id,
                "importe" => $totales['total_despues_isv']
            ]);
            
            if(!$updateImporte) {
                $conexionPrincipal->rollback();
                if(isset($numeroFactura['data']['conexion'])) {
                    $numeroFactura['data']['conexion']->rollback();
                }
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Error al actualizar el importe de la factura",
                    "type" => "error"
                ]);
            }
            
            // Guardar cuenta por cobrar
            $estado_cuenta = 1;
            $cuentaCobrar = $this->guardarCuentaPorCobrar(
                $clientes_id, 
                $facturas_id, 
                $fecha, 
                $totales['total_despues_isv'], 
                $estado_cuenta, 
                $tipo_factura,
                $datosBasicos['usuario'], 
                $fecha_registro, 
                $datosBasicos['empresa_id']
            );
            
            if(!$cuentaCobrar) {
                $conexionPrincipal->rollback();
                if(isset($numeroFactura['data']['conexion'])) {
                    $numeroFactura['data']['conexion']->rollback();
                }
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Error al registrar la cuenta por cobrar",
                    "type" => "error"
                ]);
            }
            
            // Actualizar la secuencia solo si todo lo anterior fue exitoso
            $nuevo_numero = $numeroFactura['data']['numero'] + $numeroFactura['data']['incremento'];
            $confirmacionNumeracion = $this->confirmarNumeracionFactura(
                $numeroFactura['data']['secuencia_facturacion_id'], 
                $nuevo_numero,
                $numeroFactura['data']['conexion']
            );
            
            if(!$confirmacionNumeracion) {
                $conexionPrincipal->rollback();
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Error al actualizar la secuencia de facturación",
                    "type" => "error"
                ]);
            }
            
            // Confirmar transacción principal
            $conexionPrincipal->commit();
            
            // Guardar historial
            $cliente = mainModel::consultar_tabla('clientes', ['nombre', 'rtn'], "clientes_id = {$clientes_id}")[0];
            $tipo = ($tipo_factura == 1) ? 'contado' : 'crédito';
            $this->guardarHistorialFactura(
                'Facturas',
                'Registro',
                "Se registró la factura al {$tipo} para el cliente {$cliente['nombre']} con el RTN {$cliente['rtn']}"
            );    
            
            // Preparar respuesta según tipo de documento
            if($tipo_documento === "1") { // Factura Proforma
                $datos_proforma = [
                    "facturas_id" => $facturas_id,
                    "clientes_id" => $clientes_id,
                    "secuencia_facturacion_id" => $numeroFactura['data']['secuencia_facturacion_id'],                
                    "numero" => $numeroFactura['data']['numero'],                                    
                    "importe" => $totales['total_despues_isv'],    
                    "usuario" => $colaborador_id,
                    "empresa_id" => $datosBasicos['empresa_id'],    
                    "estado" => 0,
                    "fecha_creacion" => $fecha_registro
                ];
                
                $proforma = facturasModelo::agregar_facturas_proforma_modelo($datos_proforma);
                $updateEstado = facturasModelo::actualizar_estado_factura_modelo($facturas_id);
                
                if(!$proforma || !$updateEstado) {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "Error al registrar la factura proforma",
                        "type" => "error"
                    ]);
                }
                
                $funcion = "limpiarTablaFactura();getCajero();printBill({$facturas_id});getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();";
            } else { // Factura normal
                // Verificar si hay pagos múltiples y saldo cero
                $funcion_pagos = "";
                if(isset($_POST['total_pagado'])) {
                    $funcion_pagos = $this->procesarPagosMultiples($facturas_id, $_POST['total_pagado']);
                }
                
                $funcion = ($tipo_factura == 1) ? 
                "limpiarTablaFactura();pago({$facturas_id}, 1);getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();getTotalFacturasDisponibles();" . $funcion_pagos :
                "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();" . $funcion_pagos;
            }
            
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro almacenado",
                "text" => "El registro se ha almacenado correctamente",
                "form" => "invoice-form",
                "funcion" => $funcion
            ]);
            
        } catch (Exception $e) {
            // En caso de cualquier error, hacer rollback
            if(isset($conexionPrincipal) && $conexionPrincipal) {
                $conexionPrincipal->rollback();
            }
            if(isset($numeroFactura['data']['conexion']) && $numeroFactura['data']['conexion']) {
                $numeroFactura['data']['conexion']->rollback();
            }
            error_log("Error en agregar_facturas_controlador: " . $e->getMessage());
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "Ocurrió un error al procesar la factura: " . $e->getMessage(),
                "type" => "error"
            ]);
        }
    }
    
    // Método para agregar facturas abiertas (similar al anterior pero simplificado)
    public function agregar_facturas_open_controlador() {
        if(!isset($_SESSION['user_sd'])) { 
            session_start(['name'=>'SD']); 
        }
        
        // Validar sesión
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
            ]);
        }
        
        // Obtener tipo de factura (siempre crédito para facturas abiertas)
        $tipo_factura = 2; // CRÉDITO
        $tipo_documento = 0; // FACTURA ELECTRONICA
        
        // Preparar datos básicos
        $datosBasicos = $this->prepararDatosFactura($tipo_factura, $tipo_documento);
        
        // Validar datos del formulario
        $validacion = $this->validarDatosFormulario();
        if($validacion['error']) {
            return mainModel::showNotification($validacion['notification']);
        }
        
        // Obtener número de factura
        $numeroFactura = $this->obtenerNumeroFactura($datosBasicos['empresa_id'], $datosBasicos['documento_id']);
        if($numeroFactura['error']) {
            return mainModel::showNotification([
                "title" => "Error",
                "text" => $numeroFactura['mensaje'],
                "type" => "error"
            ]);
        }
        
        // Datos comunes
        $clientes_id = $_POST['cliente_id'];
        $colaborador_id = $_POST['colaborador_id'];
        $notas = mainModel::cleanString($_POST['notesBill']);
        $fecha = $_POST['fecha'];
        $fecha_dolar = $_POST['fecha_dolar'];
        $fecha_registro = date("Y-m-d H:i:s");
        
        // Obtener ID de factura
        $facturas_id = empty($_POST['facturas_id']) ? mainModel::correlativo("facturas_id", "facturas") : $_POST['facturas_id'];
        $Existe = !empty($_POST['facturas_id']);
        
        // Obtener apertura
        $apertura = facturasModelo::getAperturaIDModelo([
            "colaboradores_id" => $datosBasicos['usuario'],
            "fecha" => $fecha,
            "estado" => 1
        ])->fetch_assoc();
        
        $apertura_id = $apertura['apertura_id'];
        
        // Guardar factura
        $datosFactura = [
            "facturas_id" => $facturas_id,
            "clientes_id" => $clientes_id,
            "secuencia_facturacion_id" => $numeroFactura['data']['secuencia_facturacion_id'],
            "apertura_id" => $apertura_id,                
            "tipo_factura" => $tipo_factura,                
            "numero" => $numeroFactura['data']['numero'],
            "colaboradores_id" => $colaborador_id,
            "importe" => 0,
            "notas" => $notas,
            "fecha" => $fecha,                
            "estado" => $datosBasicos['estado'],
            "usuario" => $datosBasicos['usuario'],
            "fecha_registro" => $fecha_registro,
            "empresa" => $datosBasicos['empresa_id'],
            "fecha_dolar" => $fecha_dolar
        ];
        
        if($Existe) {
            facturasModelo::actualizar_factura_importe($datosFactura);
        } else {
            facturasModelo::guardar_facturas_modelo($datosFactura);
        }
        
        // Procesar detalle de factura
        $totales = $this->procesarDetalleFactura(
            $facturas_id, 
            $clientes_id, 
            $fecha, 
            $fecha_registro, 
            $datosBasicos['empresa_id']
        );
        
        // Actualizar importe en factura
        facturasModelo::actualizar_factura_importe([
            "facturas_id" => $facturas_id,
            "importe" => $totales['total_despues_isv']
        ]);
        
        // Guardar cuenta por cobrar solo si es nuevo
        if(!$Existe) {
            $this->guardarCuentaPorCobrar(
                $clientes_id, 
                $facturas_id, 
                $fecha, 
                $totales['total_despues_isv'], 
                3, // Efectivo con abonos
                $tipo_factura,
                $datosBasicos['usuario'], 
                $fecha_registro, 
                $datosBasicos['empresa_id']
            );
        }
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro almacenado",
            "text" => "El registro se ha almacenado correctamente",
            "form" => "invoice-form",
            "funcion" => "limpiarTablaFactura();getCajero();getConsumidorFinal();getEstadoFactura();cleanFooterValueBill();resetRow();"
        ]);
    }
    
    // Método para cancelar facturas
    public function cancelar_facturas_controlador() {
        $facturas_id = $_POST['facturas_id'];
        
        $factura = mainModel::consultar_tabla('facturas', ['number'], "facturas_id = {$facturas_id}");
        $number = $factura[0]['number'] ?? null;
        
        $query = facturasModelo::cancelar_facturas_modelo($facturas_id);
        
        if($query) {
            $this->guardarHistorialFactura(
                'Facturas',
                'Cancelar',
                "Se canceló la factura {$number}"
            );
            
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro eliminado",
                "text" => "El registro se ha eliminado correctamente",
                "funcion" => ""
            ]);
        }
        
        return mainModel::showNotification([
            "title" => "Error",
            "text" => "No hemos podido procesar su solicitud",
            "type" => "error"
        ]);
    }
}