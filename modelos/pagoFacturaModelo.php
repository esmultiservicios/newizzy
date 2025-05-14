<?php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";    
}

class pagoFacturaModelo extends mainModel {
    protected function agregar_pago_factura_modelo($datos) {
        $importe = $datos['importe'];

        if($datos['abono'] > 0) {
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
    
    protected function agregar_pago_detalles_factura_modelo($datos) {    
        $pagos_detalles_id = mainModel::correlativo("pagos_detalles_id", "pagos_detalles");
        $insert = "INSERT INTO pagos_detalles 
            VALUES('$pagos_detalles_id','".$datos['pagos_id']."','".$datos['tipo_pago_id']."','".$datos['banco_id']."','".$datos['efectivo']."','".$datos['descripcion1']."','".$datos['descripcion2']."','".$datos['descripcion3']."')";

        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
    
        return $result;            
    }
    
    protected function cancelar_pago_modelo($pagos_id) {
        $estado = 2; // Pago CANCELADO
        $update = "UPDATE pagos
            SET
                estado = '$estado'
            WHERE pagos_id = '$pagos_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;                
    }
    
    protected function valid_pagos_factura($facturas_id) {
        $query = "SELECT pagos_id
            FROM pagos
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }
    
    protected function valid_pagos_detalles_facturas($pagos_id, $tipo_pago) {
        $query = "SELECT pagos_detalles_id
                FROM pagos_detalles
                WHERE pagos_id = '$pagos_id' AND tipo_pago_id = '$tipo_pago'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }

    protected function secuencia_facturacion_modelo($empresa_id, $documento_id) {
        $query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS 'numero', rango_final, fecha_limite, incremento, relleno
           FROM secuencia_facturacion
           WHERE activo = '1' AND empresa_id = '$empresa_id' AND documento_id = '$documento_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;
    }    

    protected function consulta_cuenta_pago_modelo($tipo_pago_id) {
        $query = "SELECT cuentas_id
           FROM tipo_pago
           WHERE tipo_pago_id = '$tipo_pago_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;
    }            
    
    protected function actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero) {
        $update = "UPDATE secuencia_facturacion
            SET
                siguiente = '$numero'
            WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";

        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);    

        return $result;                
    }    

    protected function actualizar_estado_factura_proforma_pagos_modelo($facturas_id) {
        $update = "UPDATE facturas_proforma
            SET
                estado = '1'
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);    

        return $result;                
    }    
    
    protected function actualizar_factura($datos) {
        $update = "UPDATE facturas
        SET
            estado = '".$datos['estado']."'
        WHERE facturas_id = '".$datos['facturas_id']."'";

        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);    

        return $result;                    
    }

    protected function actualizar_Secuenciafactura_PagoModelo($datos) {
        $update = "UPDATE facturas
        SET
            secuencia_facturacion_id = '".$datos['secuencia_facturacion_id']."',
            number = '".$datos['number']."',
            fecha = CURDATE()
        WHERE facturas_id = '".$datos['facturas_id']."'";

        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);    

        return $result;                    
    }        
        
    protected function agregar_ingresos_contabilidad_pagos_modelo($datos) {    
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");        
        $insert = "INSERT INTO ingresos VALUES('".$ingresos_id."','".$datos['cuentas_id']."','".$datos['clientes_id']."','".$datos['empresa_id']."','".$datos['tipo_ingreso']."','".$datos['fecha']."','".$datos['factura']."','".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."','".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['recibide']."')";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;            
    }

    protected function agregar_movimientos_contabilidad_pagos_modelo($datos) {
        $movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
        $insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;            
    }

    protected function consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentas_id) {
        $query = "SELECT ingreso, egreso, saldo
            FROM movimientos_cuentas
            WHERE cuentas_id = '$cuentas_id'
            ORDER BY movimientos_cuentas_id DESC LIMIT 1";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;                
    }

    protected function consultar_factura_proforma_pagos_modelo($facturas_id) {
        $result = mainModel::getConsultaFacturaProforma($facturas_id);
        
        return $result;            
    }

    protected function consultar_factura_cuentas_por_cobrar($facturas_id) {
        $query = "SELECT *
            FROM cobrar_clientes
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
    
        return $result;                
    }

    protected function update_status_factura_cuentas_por_cobrar($facturas_id, $estado = 2, $importe = '') {            
        if($importe != '' || $importe == 0) {
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

    protected function update_status_factura($facturas_id) {
        $estado = 2; // FACTURA PAGADA
        $update = "UPDATE facturas
            SET
                estado = '$estado'
            WHERE facturas_id = '$facturas_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
    
        return $result;                    
    }

    protected function getLastInserted() {
        $query = "SELECT MAX(pagos_id) AS id
        FROM pagos";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }

    protected function bloquear_y_obtener_secuencia_modelo($empresa_id, $documento_id) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        
        try {
            // Bloquear la fila para evitar condición de carrera
            $query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS numero, rango_final, incremento, relleno
                      FROM secuencia_facturacion
                      WHERE activo = '1' AND empresa_id = '$empresa_id' AND documento_id = '$documento_id'
                      FOR UPDATE";
            
            $result = $conexion->query($query);
            
            if($result->num_rows == 0) {
                $conexion->rollback();
                return false;
            }
            
            return $result->fetch_assoc();
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en bloquear_y_obtener_secuencia_modelo: " . $e->getMessage());
            return false;
        }
    }

    protected function es_factura_proforma($facturas_id) {
        $query = "SELECT sf.documento_id 
                  FROM facturas f
                  JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
                  WHERE f.facturas_id = '$facturas_id'";
        
        $result = mainModel::connection()->query($query);
        
        if($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            return $data['documento_id'] == 4; // 4 es el ID para Factura Proforma
        }
        
        return false;
    }

    protected function convertir_proforma_a_factura($facturas_id) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        
        try {
            // 1. Obtener datos de la factura proforma
            $query_factura = "SELECT f.clientes_id, f.apertura_id, f.colaboradores_id, f.importe, 
                             f.notas, f.usuario, f.empresa_id, f.fecha_dolar
                             FROM facturas f WHERE f.facturas_id = '$facturas_id'";
            $factura = $conexion->query($query_factura)->fetch_assoc();
            
            if(!$factura) {
                throw new Exception("No se encontró la factura proforma");
            }
            
            // 2. Obtener secuencia para factura electrónica (documento_id = 1)
            $secuencia = $this->bloquear_y_obtener_secuencia_modelo($factura['empresa_id'], 1);
            
            if(!$secuencia) {
                throw new Exception("No se encontró secuencia para factura electrónica");
            }
            
            // Verificar rango final
            $nuevo_numero = $secuencia['numero'] + $secuencia['incremento'];
            if($nuevo_numero > $secuencia['rango_final']) {
                throw new Exception("Se ha alcanzado el límite del rango autorizado de facturación");
            }
            
            // 3. Actualizar la factura con los nuevos datos de factura electrónica
            $update_factura = "UPDATE facturas 
                             SET secuencia_facturacion_id = '".$secuencia['secuencia_facturacion_id']."',
                                 number = '".$secuencia['numero']."',
                                 tipo_factura = 1, -- Cambiar a contado
                                 estado = 2 -- Marcarla como pagada
                             WHERE facturas_id = '$facturas_id'";
            
            if(!$conexion->query($update_factura)) {
                throw new Exception("Error al actualizar la factura");
            }
            
            // 4. Actualizar la secuencia
            $update_secuencia = "UPDATE secuencia_facturacion 
                               SET siguiente = '$nuevo_numero'
                               WHERE secuencia_facturacion_id = '".$secuencia['secuencia_facturacion_id']."'";
            
            if(!$conexion->query($update_secuencia)) {
                throw new Exception("Error al actualizar la secuencia");
            }
            
            // 5. Marcar la proforma como convertida
            $update_proforma = "UPDATE facturas_proforma 
                              SET estado = 1 -- Marcada como convertida
                              WHERE facturas_id = '$facturas_id'";
            
            if(!$conexion->query($update_proforma)) {
                throw new Exception("Error al actualizar la proforma");
            }
            
            $conexion->commit();
            
            return [
                'success' => true,
                'numero_factura' => $secuencia['prefijo'].str_pad($secuencia['numero'], $secuencia['relleno'], "0", STR_PAD_LEFT)
            ];
            
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en convertir_proforma_a_factura: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Función principal para procesar pagos con manejo de condición de carrera
    protected function agregar_pago_factura_base($datos) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        
        try {
            // Verificar si es proforma
            $esProforma = $this->es_factura_proforma($datos['facturas_id']);
            
            if ($datos['estado_factura'] == 2 || $datos['multiple_pago'] == 1) {
                // Procesar pago a crédito
                $resultado = $this->procesar_pago_credito_transaccion($conexion, $datos, $esProforma);
            } else {
                // Procesar pago de contado
                $resultado = $this->procesar_pago_contado_transaccion($conexion, $datos, $esProforma);
            }
            
            // Si todo salió bien, confirmar la transacción
            $conexion->commit();
            return $resultado;
            
        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en agregar_pago_factura_base: " . $e->getMessage());
            return [
                "status" => false,
                "title" => "Error",
                "message" => "Ocurrió un error al procesar el pago: " . $e->getMessage()
            ];
        }
    }

    protected function procesar_pago_contado_transaccion($conexion, $datos, $esProforma) {
        // Validar que no existan pagos previos para esta factura
        if ($this->valid_pagos_factura($datos['facturas_id'])->num_rows > 0) {
            throw new Exception("Ya existe un pago para esta factura. Habilite pagos múltiples si desea agregar otro pago.");
        }
        
        // Registrar el pago
        $query = $this->agregar_pago_factura_modelo($datos);
        if (!$query) {
            throw new Exception("No se pudo registrar el pago");
        }
        
        // Obtener ID del pago recién insertado
        $pagoId = $this->getLastInserted()->fetch_assoc()['id'];
        
        // Agregar detalles del pago
        $datosDetalle = [
            "pagos_id" => $pagoId,
            "tipo_pago_id" => $datos['tipo_pago_id'],
            "banco_id" => $datos['banco_id'],
            "efectivo" => $datos['importe'],
            "descripcion1" => $datos['referencia_pago1'],
            "descripcion2" => $datos['referencia_pago2'],
            "descripcion3" => $datos['referencia_pago3'],
        ];
        
        $this->agregar_pago_detalles_factura_modelo($datosDetalle);
        
        // Actualizar estado de la factura
        $this->update_status_factura($datos['facturas_id']);
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], 2, 0);
        
        // Si es proforma, convertir a factura electrónica
            if ($esProforma) {
                $conversion = $this->convertir_proforma_a_factura($datos['facturas_id']);
                if (!$conversion['success']) {
                    throw new Exception($conversion['message']);
                }
            }
            
            // Registrar en contabilidad
            $this->registrar_contabilidad_pago($datos);
            
            // Registrar en historial
            $this->registrarHistorial("Se registró pago para factura al contado");
            
            return [
                "status" => true,
                "title" => "Pago registrado",
                "message" => "El pago se registró correctamente",
                "funcion" => "printBill(".$datos['facturas_id'].",".$datos['print_comprobante'].");listar_cuentas_por_cobrar_clientes();mailBill(".$datos['facturas_id'].");getCollaboradoresModalPagoFacturas();",
                "closeAllModals" => true
            ];
        }
        
        protected function procesar_pago_credito_transaccion($conexion, $datos, $esProforma) {
            // Obtener saldo actual
            $saldoCredito = $this->obtener_saldo_credito($datos['facturas_id']);
            
            if ($datos['abono'] > $saldoCredito) {
                throw new Exception("El abono es mayor al importe pendiente");
            }
            
            // Calcular nuevo saldo
            $nuevoSaldo = $saldoCredito - $datos['abono'];
            
            // Registrar el pago
            $query = $this->agregar_pago_factura_modelo($datos);
            if (!$query) {
                throw new Exception("No se pudo registrar el pago");
            }
            
            // Obtener ID del pago recién insertado
            $pagoId = $this->getLastInserted()->fetch_assoc()['id'];
            
            // Agregar detalles del pago
            $datosDetalle = [
                "pagos_id" => $pagoId,
                "tipo_pago_id" => $datos['tipo_pago_id'],
                "banco_id" => $datos['banco_id'],
                "efectivo" => $datos['importe'],
                "descripcion1" => $datos['referencia_pago1'],
                "descripcion2" => $datos['referencia_pago2'],
                "descripcion3" => $datos['referencia_pago3'],
            ];
            
            $this->agregar_pago_detalles_factura_modelo($datosDetalle);
            
            // Actualizar saldo en cuenta por cobrar
            $estado = ($nuevoSaldo == 0) ? 2 : 1; // 2 = Pagado, 1 = Pendiente
            $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], $estado, $nuevoSaldo);
            
            // Si el saldo llegó a cero, actualizar estado de la factura
            if ($nuevoSaldo == 0) {
                $this->update_status_factura($datos['facturas_id']);
                
                // Si es proforma, convertir a factura electrónica
                if ($esProforma) {
                    $conversion = $this->convertir_proforma_a_factura($datos['facturas_id']);
                    if (!$conversion['success']) {
                        throw new Exception($conversion['message']);
                    }
                }
            }
            
            // Registrar en contabilidad
            $this->registrar_contabilidad_pago($datos);
            
            // Registrar en historial
            $this->registrarHistorial("Se registró pago para factura al crédito");
            
            // Determinar la respuesta según si es pago múltiple
            if ($datos['multiple_pago'] == 1 && $nuevoSaldo > 0) {
                return [
                    "status" => true,
                    "title" => "Pago registrado",
                    "message" => "Pago múltiple registrado correctamente",                
                    "funcion" => "pago(".$datos['facturas_id'].");saldoFactura(".$datos['facturas_id'].");"
                ];
            } else {
                $accion = ($nuevoSaldo == 0) ? "printBill(".$datos['facturas_id'].",1);" : "";
                
                return [
                    "status" => true,
                    "title" => "Pago completado",
                    "message" => "El pago se completó correctamente",                
                    "funcion" => "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();".$accion,
                    "closeAllModals" => true
                ];
            }
        }
        
        protected function obtener_saldo_credito($facturas_id) {
            $result = $this->consultar_factura_cuentas_por_cobrar($facturas_id);
            if($result->num_rows == 0) {
                throw new Exception("No se encontró la cuenta por cobrar para esta factura");
            }
            
            $data = $result->fetch_assoc();
            return $data['saldo'];
        }
        
        protected function registrar_contabilidad_pago($datos) {
            // Obtener cuenta contable asociada al tipo de pago
            $cuenta = $this->consulta_cuenta_pago_modelo($datos['tipo_pago_id'])->fetch_assoc();
            if(!$cuenta) {
                throw new Exception("No se encontró la cuenta contable asociada al tipo de pago");
            }
            
            // Obtener datos de la factura
            $factura = mainModel::getFactura($datos['facturas_id'])->fetch_assoc();
            if(!$factura) {
                throw new Exception("No se encontró la factura");
            }
            
            // Preparar datos para el ingreso contable
            $datosIngreso = [
                "clientes_id" => $factura['clientes_id'],
                "cuentas_id" => $cuenta['cuentas_id'],
                "empresa_id" => $datos['empresa'],
                "fecha" => date("Y-m-d"),
                "factura" => str_pad($factura['number'], 8, "0", STR_PAD_LEFT),
                "subtotal" => $datos['abono'],
                "isv" => 0,
                "descuento" => 0,
                "nc" => 0,
                "total" => $datos['abono'],
                "observacion" => "Ingresos por venta",
                "estado" => 1,
                "fecha_registro" => date("Y-m-d H:i:s"),
                "colaboradores_id" => $datos['colaboradores_id'],
                "tipo_ingreso" => 2,
                "recibide" => ""                                
            ];
            
            // Registrar ingreso
            if(!$this->agregar_ingresos_contabilidad_pagos_modelo($datosIngreso)) {
                throw new Exception("Error al registrar el ingreso contable");
            }
            
            // Registrar movimiento de cuenta
            $this->registrar_movimiento_cuenta($cuenta['cuentas_id'], $datos);
        }
        
        protected function registrar_movimiento_cuenta($cuenta_id, $datos) {
            // Obtener saldo actual
            $saldo = $this->consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuenta_id)->fetch_assoc();
            $saldoActual = $saldo ? $saldo['saldo'] : 0;
            $nuevoSaldo = $saldoActual + $datos['abono'];
            
            // Preparar datos para el movimiento
            $datosMovimiento = [
                "cuentas_id" => $cuenta_id,
                "empresa_id" => $datos['empresa'],
                "fecha" => date("Y-m-d"),
                "ingreso" => $datos['abono'],
                "egreso" => 0,
                "saldo" => $nuevoSaldo,
                "colaboradores_id" => $datos['colaboradores_id'],
                "fecha_registro" => date("Y-m-d H:i:s"),                
            ];
            
            // Registrar movimiento
            if(!$this->agregar_movimientos_contabilidad_pagos_modelo($datosMovimiento)) {
                throw new Exception("Error al registrar el movimiento contable");
            }
        }
        
        protected function registrarHistorial($observacion) {
            $datos = [
                "modulo" => 'Pagos',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],        
                "status" => "Registrar",
                "observacion" => $observacion,
                "fecha_registro" => date("Y-m-d H:i:s")
            ];    
            
            mainModel::guardarHistorial($datos);
        }
    }