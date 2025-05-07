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
    
    protected function consultar_codigo_pago_modelo($facturas_id) {
        $query = "SELECT pagos_id
            FROM pagos
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }

    protected function consultar_numero_factura_pago_modelo($facturas_id) {
        $query = "SELECT number
            FROM facturas
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }        

    protected function getLastInserted() {
        $query = "SELECT MAX(pagos_id) AS id
        FROM pagos";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
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
    
    protected function consultar_factura_cuentas_por_cobrar($facturas_id) {
        $query = "SELECT *
            FROM cobrar_clientes
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
    
        return $result;                
    }    

    protected function consultar_factura_fecha($facturas_id) {
        $query = "SELECT fecha
            FROM facturas
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }
    
    protected function consultar_tipo_factura($facturas_id) {
        $query = "SELECT tipo_factura
            FROM facturas
            WHERE facturas_id = '$facturas_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;    
    }

    protected function consultar_numero_factura($facturas_id) {
        $query = "SELECT number, secuencia_facturacion_id
            FROM facturas
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
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

    protected function consultar_numero_factura_modelo($facturas_id) {
        $query = "SELECT number FROM facturas WHERE facturas_id = '$facturas_id'";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;                
    }

    protected function consultar_factura_proforma_pagos_modelo($facturas_id) {
        $result = mainModel::getConsultaFacturaProforma($facturas_id);
        
        return $result;            
    }        

    // Función principal para procesar pagos
    protected function agregar_pago_factura_base($datos) {
        $existeProforma = $this->verificarProforma($datos['facturas_id']);
        $proformaNombre = $existeProforma ? "Factura Proforma" : "Factura Electronica";
        
        if ($datos['estado_factura'] == 2 || $datos['multiple_pago'] == 1) {
            return $this->procesarPagoCredito($datos, $proformaNombre);
        } else {
            return $this->procesarPagoContado($datos);
        }
    }

    protected function verificarProforma($facturaId) {
        $result = $this->consultar_factura_proforma_pagos_modelo($facturaId);
        return $result->num_rows > 0;
    }

    protected function procesarPagoCredito($datos, $proformaNombre) {
        $saldoCredito = $this->obtenerSaldoCredito($datos['facturas_id']);
        
        if ($datos['abono'] > $saldoCredito) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "El abono es mayor al importe pendiente"
            ];
        }

        $nuevoSaldo = $this->actualizarSaldoCredito($datos, $saldoCredito);
        $query = $this->agregar_pago_factura_modelo($datos);

        if (!$query) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se pudo registrar el pago"
            ];
        }

        $pagoId = $this->getLastInserted()->fetch_assoc()['id'];
        $this->agregarDetallePago($pagoId, $datos);
        $this->registrarIngresoContable($datos);

        if ($datos['multiple_pago'] == 1 && $nuevoSaldo > 0) {
            return $this->procesarPagoMultiple($datos, $proformaNombre);
        } else {
            return $this->finalizarProcesoPago($datos, $proformaNombre, $nuevoSaldo);
        }
    }

    protected function procesarPagoContado($datos) {
        if ($this->valid_pagos_factura($datos['facturas_id'])->num_rows > 0) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "Ya existe un pago para esta factura. Habilite pagos múltiples si desea agregar otro pago."
            ];
        }

        $query = $this->agregar_pago_factura_modelo($datos);
        
        if (!$query) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se pudo registrar el pago"
            ];
        }

        $pagoId = $this->getLastInserted()->fetch_assoc()['id'];
        $this->agregarDetallePago($pagoId, $datos);
        
        $this->update_status_factura($datos['facturas_id']);
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], 2, 0);
        
        $datosUpdate = ["facturas_id" => $datos['facturas_id'], "estado" => 2];
        $this->actualizar_factura($datosUpdate);

        $this->registrarHistorial("Se registró pago para factura al contado");

        return [
            "status" => true,
            "title" => "Pago registrado",
            "message" => "El pago se registró correctamente",
            "funcion" => "printBill(".$datos['facturas_id'].",".$datos['print_comprobante'].");listar_cuentas_por_cobrar_clientes();mailBill(".$datos['facturas_id'].");getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ];
    }

    protected function obtenerSaldoCredito($facturaId) {
        $result = $this->consultar_factura_cuentas_por_cobrar($facturaId);
        return $result->num_rows > 0 ? $result->fetch_assoc()['saldo'] : 0;
    }

    protected function actualizarSaldoCredito($datos, $saldoCredito) {
        $nuevoSaldo = $saldoCredito - $datos['abono'];
        $estado = ($nuevoSaldo == 0) ? 2 : 1;
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], $estado, $nuevoSaldo);
        return $nuevoSaldo;
    }

    protected function agregarDetallePago($pagoId, $datos) {
        $datosDetalle = [
            "pagos_id" => $pagoId,
            "tipo_pago_id" => $datos['tipo_pago_id'],
            "banco_id" => $datos['banco_id'],
            "efectivo" => $datos['importe'],
            "descripcion1" => $datos['referencia_pago1'],
            "descripcion2" => $datos['referencia_pago2'],
            "descripcion3" => $datos['referencia_pago3'],
        ];
        
        if ($this->valid_pagos_detalles_facturas($pagoId, $datos['tipo_pago_id'])->num_rows == 0) {
            $this->agregar_pago_detalles_factura_modelo($datosDetalle);
        }
    }

    protected function registrarIngresoContable($datos) {
        $cuenta = $this->consulta_cuenta_pago_modelo($datos['tipo_pago_id'])->fetch_assoc();
        $factura = mainModel::getFactura($datos['facturas_id'])->fetch_assoc();
        
        // Verificar y manejar el número de factura
        $numeroFactura = $factura['number'] ?? '00000000'; // Valor por defecto si no existe
        $relleno = $factura['relleno'] ?? 8; // Valor por defecto si no existe
        
        $datosIngreso = [
            "clientes_id" => $factura['clientes_id'] ?? 0,
            "cuentas_id" => $cuenta['cuentas_id'] ?? 0,
            "empresa_id" => $datos['empresa'],
            "fecha" => date("Y-m-d"),
            "factura" => str_pad($numeroFactura, $relleno, "0", STR_PAD_LEFT),
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
        
        $this->agregar_ingresos_contabilidad_pagos_modelo($datosIngreso);
        $this->registrarMovimientoCuenta($cuenta['cuentas_id'] ?? 0, $datos);
    }

    protected function registrarMovimientoCuenta($cuentaId, $datos) {
        $saldo = $this->consultar_saldo_movimientos_cuentas_pagos_contabilidad($cuentaId)->fetch_assoc();
        $saldoActual = $saldo['saldo'] ?? 0;
        $nuevoSaldo = $saldoActual + $datos['abono'];
        
        $datosMovimiento = [
            "cuentas_id" => $cuentaId,
            "empresa_id" => $datos['empresa'],
            "fecha" => date("Y-m-d"),
            "ingreso" => $datos['abono'],
            "egreso" => 0,
            "saldo" => $nuevoSaldo,
            "colaboradores_id" => $datos['colaboradores_id'],
            "fecha_registro" => date("Y-m-d H:i:s"),                
        ];
        
        $this->agregar_movimientos_contabilidad_pagos_modelo($datosMovimiento);
    }

    protected function procesarPagoMultiple($datos, $proformaNombre) {
        $this->registrarHistorial("Se registró pago múltiple para factura");
        
        return [
            "status" => true,
            "title" => "Pago registrado",
            "message" => "Pago múltiple registrado correctamente",                
            "funcion" => "pago(".$datos['facturas_id'].");saldoFactura(".$datos['facturas_id'].");"
        ];
    }

    protected function finalizarProcesoPago($datos, $proformaNombre, $saldoNuevo) {
        $accion = "";
        
        // Verificar si es pago completo (saldo = 0)
        if($saldoNuevo == 0) {
            // Forzar impresión independientemente de otros parámetros
            $accion = "printBill(".$datos['facturas_id'].",1);";
            
            // Si era proforma, actualizar a factura normal
            if($proformaNombre === "Factura Proforma") {
                $documento = $this->getDocumentoSecuenciaFacturacion("Factura Electronica")->fetch_assoc();
                $secuencia = $this->secuencia_facturacion_modelo($datos['empresa'], $documento['documento_id'])->fetch_assoc();
                $nuevoNumero = $secuencia['numero'] + $secuencia['incremento'];
                $this->actualizar_secuencia_facturacion_modelo($secuencia['secuencia_facturacion_id'], $nuevoNumero);
                $this->actualizar_estado_factura_proforma_pagos_modelo($datos['facturas_id']);
            }
        }
        
        return [
            "status" => true,
            "title" => "Pago completado",
            "message" => "El pago se completó correctamente",                
            "funcion" => "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();".$accion,
            "closeAllModals" => true
        ];
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