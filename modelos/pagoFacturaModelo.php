<?php
//pagoFacturaModelo.php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class pagoFacturaModelo extends mainModel {

    /* ==========================================================
       CRUD PAGO / DETALLE PAGO
       ========================================================== */

    // Inserta en pagos
    protected function agregar_pago_factura_modelo($datos) {
        $pagos_id = mainModel::correlativo("pagos_id", "pagos");

        $insert = "INSERT INTO pagos (
            pagos_id, facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta,
            usuario, estado, empresa_id, fecha_registro, contabilizado, referencia_ingreso_id
        ) VALUES (
            '$pagos_id',
            '".$datos['facturas_id']."',
            '".$datos['tipo_pago_id']."',
            '".$datos['fecha']."',
            '".$datos['importe']."',
            '".$datos['efectivo']."',
            '".$datos['cambio']."',
            '".$datos['tarjeta']."',
            '".$datos['usuario']."',
            '".$datos['estado']."',
            '".$datos['empresa']."',
            '".$datos['fecha_registro']."',
            0,
            NULL
        )";

        $ok = mainModel::connection()->query($insert);
        if (!$ok) throw new Exception("Error al insertar pago: ".mainModel::connection()->error);

        return $pagos_id;
    }

    protected function agregar_pago_detalles_factura_modelo($datos) {
        $pagos_detalles_id = mainModel::correlativo("pagos_detalles_id", "pagos_detalles");
        $insert = "INSERT INTO pagos_detalles (
            pagos_detalles_id, pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1, descripcion2, descripcion3
        ) VALUES (
            '$pagos_detalles_id',
            '".$datos['pagos_id']."',
            '".$datos['tipo_pago_id']."',
            '".$datos['banco_id']."',
            '".$datos['efectivo']."',
            '".$datos['descripcion1']."',
            '".$datos['descripcion2']."',
            '".$datos['descripcion3']."'
        )";
        $ok = mainModel::connection()->query($insert);
        if (!$ok) throw new Exception("Error al insertar detalle de pago: ".mainModel::connection()->error);
        return true;
    }

    protected function marcar_pago_contabilizado($pagos_id, $ingresos_id){
        $update = "UPDATE pagos
            SET contabilizado = 1, referencia_ingreso_id = '$ingresos_id'
            WHERE pagos_id = '$pagos_id'";
        $ok = mainModel::connection()->query($update);
        if (!$ok) throw new Exception("Error al marcar pago contabilizado: ".mainModel::connection()->error);
        return true;
    }

    protected function cancelar_pago_modelo($pagos_id) {
        $update = "UPDATE pagos SET estado = 2 WHERE pagos_id = '$pagos_id'";
        $ok = mainModel::connection()->query($update);
        if (!$ok) throw new Exception("Error al cancelar pago: ".mainModel::connection()->error);
        return true;
    }

    protected function valid_pagos_factura($facturas_id) {
        $query = "SELECT pagos_id FROM pagos WHERE facturas_id = '$facturas_id' AND estado = 1";
        $rs = mainModel::connection()->query($query);
        if ($rs === false) throw new Exception("Error en valid_pagos_factura: ".mainModel::connection()->error);
        return $rs;
    }

    protected function consulta_cuenta_pago_modelo($tipo_pago_id) {
        $query = "SELECT cuentas_id FROM tipo_pago WHERE tipo_pago_id = '$tipo_pago_id'";
        $rs = mainModel::connection()->query($query);
        if ($rs === false) throw new Exception("Error en consulta_cuenta_pago_modelo: ".mainModel::connection()->error);
        return $rs;
    }

    protected function consultar_factura_cuentas_por_cobrar($facturas_id) {
        $query = "SELECT cobrar_clientes_id, facturas_id, clientes_id,
                         DATE(fecha) AS fecha,
                         ROUND(saldo,2) AS saldo,
                         estado, tipo_factura, usuario, empresa_id, fecha_registro
                  FROM cobrar_clientes
                  WHERE facturas_id = '$facturas_id'";
        $rs = mainModel::connection()->query($query);
        if ($rs === false) throw new Exception("Error en consultar_factura_cuentas_por_cobrar: ".mainModel::connection()->error);
        return $rs;
    }    

    protected function update_status_factura_cuentas_por_cobrar($facturas_id, $estado = 2, $importe = '') {
        if ($importe !== '' || $importe == 0) {
            // fuerza 2 decimales exactos en BD
            $importe = ', saldo = '.sprintf('%.2f', round((float)$importe + 1e-9, 2));
        }
        $update = "UPDATE cobrar_clientes SET estado = '$estado' $importe WHERE facturas_id = '$facturas_id'";
        $ok = mainModel::connection()->query($update);
        if (!$ok) throw new Exception("Error en update_status_factura_cuentas_por_cobrar: ".mainModel::connection()->error);
        return true;
    }    

    protected function update_status_factura($facturas_id) {
        $update = "UPDATE facturas SET estado = 2 WHERE facturas_id = '$facturas_id'";
        $ok = mainModel::connection()->query($update);
        if (!$ok) throw new Exception("Error en update_status_factura: ".mainModel::connection()->error);
        return true;
    }

    protected function es_factura_proforma($facturas_id) {
        $query = "SELECT sf.documento_id
                  FROM facturas f
                  JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
                  WHERE f.facturas_id = '$facturas_id'";
        $rs = mainModel::connection()->query($query);
        if ($rs === false) throw new Exception("Error en es_factura_proforma: ".mainModel::connection()->error);

        if ($rs->num_rows > 0) {
            $d = $rs->fetch_assoc();
            return intval($d['documento_id']) === 4; // 4 = proforma
        }
        return false;
    }

    protected function convertir_proforma_a_factura($facturas_id) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        try {
            $qf = "SELECT f.clientes_id, f.apertura_id, f.colaboradores_id, f.importe,
                          f.notas, f.usuario, f.empresa_id, f.fecha_dolar
                   FROM facturas f WHERE f.facturas_id = '$facturas_id'";
            $rsF = $conexion->query($qf);
            if(!$rsF || $rsF->num_rows === 0) { throw new Exception("No se encontró la factura proforma"); }
            $factura = $rsF->fetch_assoc();

            $qs = "SELECT secuencia_facturacion_id, prefijo, siguiente AS numero, rango_final, fecha_limite, incremento, relleno
                   FROM secuencia_facturacion
                   WHERE activo = '1' AND empresa_id = '".$conexion->real_escape_string($factura['empresa_id'])."' AND documento_id = '1'
                   FOR UPDATE";
            $rsS = $conexion->query($qs);
            if(!$rsS || $rsS->num_rows === 0) { throw new Exception("No se encontró secuencia para factura electrónica"); }
            $secuencia = $rsS->fetch_assoc();

            $nuevo_numero = intval($secuencia['numero']) + intval($secuencia['incremento']);
            if($nuevo_numero > intval($secuencia['rango_final'])) {
                throw new Exception("Se ha alcanzado el límite del rango autorizado de facturación");
            }

            $uf = "UPDATE facturas
                   SET secuencia_facturacion_id = '".$secuencia['secuencia_facturacion_id']."',
                       number = '".$secuencia['numero']."',
                       tipo_factura = 1,
                       estado = 2
                   WHERE facturas_id = '$facturas_id'";
            if(!$conexion->query($uf)) { throw new Exception("Error al actualizar la factura"); }

            $us = "UPDATE secuencia_facturacion
                   SET siguiente = '$nuevo_numero'
                   WHERE secuencia_facturacion_id = '".$secuencia['secuencia_facturacion_id']."'";
            if(!$conexion->query($us)) { throw new Exception("Error al actualizar la secuencia"); }

            $up = "UPDATE facturas_proforma SET estado = 1 WHERE facturas_id = '$facturas_id'";
            if(!$conexion->query($up)) { throw new Exception("Error al actualizar la proforma"); }

            $conexion->commit();
            return [
                'success' => true,
                'numero_factura' => $secuencia['prefijo'].str_pad($secuencia['numero'], $secuencia['relleno'], "0", STR_PAD_LEFT)
            ];
        } catch (Exception $e) {
            $conexion->rollback();
            return ['success'=>false,'message'=>$e->getMessage()];
        }
    }

    /* ==========================================================
       ENTRADA PRINCIPAL
       ========================================================== */

    protected function agregar_pago_factura_base($datos) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        try {
            $esProforma = $this->es_factura_proforma($datos['facturas_id']);

            // Evitar doble pago en contado si ya existe
            $rsExist = $this->valid_pagos_factura($datos['facturas_id']);
            if ($datos['tipo_factura'] == 1 && $rsExist->num_rows > 0) {
                throw new Exception("Ya existe un pago para esta factura. Habilite pagos múltiples si desea agregar otro pago.");
            }

            // 1 = contado, 2 = crédito/abono
            if ($datos['tipo_factura'] == 1) {
                $res = $this->procesar_pago_contado_transaccion($conexion, $datos, $esProforma);
            } else {
                $res = $this->procesar_pago_credito_transaccion($conexion, $datos, $esProforma);
            }

            $conexion->commit();
            return $res;

        } catch (Exception $e) {
            $conexion->rollback();
            return ["status"=>false,"title"=>"Error","message"=>$e->getMessage()];
        }
    }

    protected function procesar_pago_contado_transaccion($conexion, $datos, $esProforma) {
        // Insert pago
        $pagoId = $this->agregar_pago_factura_modelo($datos);

        // Detalle
        $this->agregar_pago_detalles_factura_modelo([
            "pagos_id"=>$pagoId,
            "tipo_pago_id"=>$datos['tipo_pago_id'],
            "banco_id"=>$datos['banco_id'],
            "efectivo"=>$datos['importe'],
            "descripcion1"=>$datos['referencia_pago1'],
            "descripcion2"=>$datos['referencia_pago2'],
            "descripcion3"=>$datos['referencia_pago3'],
        ]);

        // Cerrar factura y CxC
        $this->update_status_factura($datos['facturas_id']);
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], 2, 0);

        // Contabilidad:
        // - Proforma → registrar ingreso+mov y marcar contabilizado
        // - Origen CXC (contado desde CxC) → registrar ingreso+mov y marcar contabilizado
        // - Origen FACTURACIÓN (contado normal) → NO registrar ingreso/mov aquí
        $origen = isset($datos['origen_pago']) ? $datos['origen_pago'] : 'facturacion';
        if ($esProforma || $origen === 'cxc') {
            $ingreso_id = $this->registrar_contabilidad_pago($datos);
            $this->marcar_pago_contabilizado($pagoId, $ingreso_id);
        }

        $this->registrarHistorial("Se registró pago al contado");

        return [
            "status"=>true,
            "title"=>"Pago registrado",
            "message"=>"El pago se registró correctamente",
            "funcion"=>"printBill(".$datos['facturas_id'].",".$datos['print_comprobante'].");listar_cuentas_por_cobrar_clientes();mailBill(".$datos['facturas_id'].");getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=>true
        ];
    }

    protected function procesar_pago_credito_transaccion($conexion, $datos, $esProforma) {
        // Validar saldo
        $saldoRes = $this->consultar_factura_cuentas_por_cobrar($datos['facturas_id']);
        if ($saldoRes->num_rows == 0) { throw new Exception("No se encontró la cuenta por cobrar para esta factura"); }
        $saldoRow = $saldoRes->fetch_assoc();
        
        $saldo = round((float)$saldoRow['saldo'] + 1e-9, 2);

        // valida con tolerancia
        $EPS = 0.005;
        if ($datos['importe'] - $saldo > $EPS) {
            throw new Exception("El abono es mayor al importe pendiente (L. ".number_format($saldo,2).")");
        }

        // nuevo saldo redondeado (nunca negativo)
        $nuevoSaldo = max(0, round($saldo - $datos['importe'] + 1e-9, 2));

        // Insert pago
        $pagoId = $this->agregar_pago_factura_modelo($datos);

        // Detalle
        $this->agregar_pago_detalles_factura_modelo([
            "pagos_id"=>$pagoId,
            "tipo_pago_id"=>$datos['tipo_pago_id'],
            "banco_id"=>$datos['banco_id'],
            "efectivo"=>$datos['importe'],
            "descripcion1"=>$datos['referencia_pago1'],
            "descripcion2"=>$datos['referencia_pago2'],
            "descripcion3"=>$datos['referencia_pago3'],
        ]);

        // Actualizar CxC y factura si queda saldada
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], ($nuevoSaldo==0?2:1), $nuevoSaldo);
        if ($nuevoSaldo == 0) {
            $this->update_status_factura($datos['facturas_id']);
        }

        // Crédito/Abono o Proforma → registrar ingreso+mov y marcar contabilizado
        $ingreso_id = $this->registrar_contabilidad_pago($datos);
        $this->marcar_pago_contabilizado($pagoId, $ingreso_id);

        // Si era proforma y quedó saldada, convertir a factura
        if ($esProforma && $nuevoSaldo == 0) {
            $conv = $this->convertir_proforma_a_factura($datos['facturas_id']);
            if (!$conv['success']) { throw new Exception($conv['message']); }
        }

        $this->registrarHistorial("Se registró pago al crédito/abono");

        if ($nuevoSaldo > 0) {
            return [
                "status"=>true,
                "title"=>"Pago registrado",
                "message"=>"Pago múltiple registrado correctamente",
                "funcion"=>"pago(".$datos['facturas_id'].");saldoFactura(".$datos['facturas_id'].");"
            ];
        } else {
            return [
                "status"=>true,
                "title"=>"Pago completado",
                "message"=>"El pago se completó correctamente",
                "funcion"=>"printBill(".$datos['facturas_id'].",1);listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
                "closeAllModals"=>true
            ];
        }
    }

    /* ==========================================================
       CONTABILIDAD (INGRESOS + MOVIMIENTOS)
       ========================================================== */

    protected function registrar_contabilidad_pago($datos) {
        // Cuenta contable según tipo de pago
        $rsCuenta = $this->consulta_cuenta_pago_modelo($datos['tipo_pago_id']);
        if (!$rsCuenta || $rsCuenta->num_rows === 0) {
            throw new Exception("No se encontró la cuenta contable asociada al tipo de pago");
        }
        $cuenta = $rsCuenta->fetch_assoc();

        // Traer factura con prefijo/numero_factura/relleno para referencia
        $rsFactura = mainModel::getFactura($datos['facturas_id']);
        if(!$rsFactura || $rsFactura->num_rows === 0) {
            throw new Exception("No se encontró la factura");
        }
        $factura = $rsFactura->fetch_assoc();

        // Referencia a guardar en ingresos.factura => prefijo + numero relleno
        $prefijo  = isset($factura['prefijo']) ? $factura['prefijo'] : '';
        $relleno  = isset($factura['relleno']) ? intval($factura['relleno']) : 8;
        $numero   = isset($factura['numero_factura']) ? intval($factura['numero_factura']) : 0;
        $referenciaFactura = ($numero > 0) ? ($prefijo . str_pad($numero, $relleno, "0", STR_PAD_LEFT)) : '';

        // tipo_ingreso: 1=Ingresos Ventas
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");
        $obs = ($datos['tipo_factura']==1 ? "Pago de factura al contado" : "Abono a factura al crédito");

        $insert = "INSERT INTO ingresos VALUES(
            '".$ingresos_id."',                  -- ingresos_id
            '".$cuenta['cuentas_id']."',         -- cuentas_id
            '".$factura['clientes_id']."',       -- clientes_id
            '".$datos['empresa']."',             -- empresa_id
            '1',                                 -- tipo_ingreso (1=Ventas)
            '".date("Y-m-d")."',                 -- fecha
            '".$referenciaFactura."',            -- factura (char20)
            '".$datos['importe']."',             -- subtotal
            '0',                                 -- descuento
            '0',                                 -- nc
            '0',                                 -- impuesto
            '".$datos['importe']."',             -- total
            '".$obs."',                          -- observacion
            '1',                                 -- estado
            '".$datos['colaboradores_id']."',    -- colaboradores_id
            '".date("Y-m-d H:i:s")."',           -- fecha_registro
            ''                                   -- recibide
        )";
        $ok = mainModel::connection()->query($insert);
        if (!$ok) throw new Exception("Error al registrar ingreso contable: ".mainModel::connection()->error);

        // Movimiento en cuenta: sumar al saldo actual
        $qSaldo = "SELECT saldo FROM movimientos_cuentas 
                   WHERE cuentas_id = '".$cuenta['cuentas_id']."' 
                   ORDER BY movimientos_cuentas_id DESC LIMIT 1";
        $r = mainModel::connection()->query($qSaldo);
        if ($r === false) throw new Exception("Error al consultar saldo: ".mainModel::connection()->error);

        $saldoActual = 0.0;
        if ($r->num_rows > 0) {
            $row = $r->fetch_assoc();
            $saldoActual = floatval($row['saldo']);
        }
        $nuevoSaldo = $saldoActual + floatval($datos['importe']);

        $mov_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
        $insMov = "INSERT INTO movimientos_cuentas VALUES(
            '$mov_id',
            '".$cuenta['cuentas_id']."',
            '".$datos['empresa']."',
            '".date("Y-m-d")."',
            '".$datos['importe']."',  -- ingreso
            '0',                      -- egreso
            '".$nuevoSaldo."',        -- saldo
            '".$datos['colaboradores_id']."',
            '".date("Y-m-d H:i:s")."'
        )";
        $ok2 = mainModel::connection()->query($insMov);
        if (!$ok2) throw new Exception("Error al registrar movimiento contable: ".mainModel::connection()->error);

        return $ingresos_id;
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