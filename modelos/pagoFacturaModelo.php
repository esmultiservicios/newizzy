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
            '".(isset($datos['tarjeta']) ? $datos['tarjeta'] : 0)."',
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
        $facturas_id = (int)$facturas_id;
        $query = "SELECT sf.documento_id
                  FROM facturas f
                  JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
                  WHERE f.facturas_id = '$facturas_id'
                  LIMIT 1";
        $rs = mainModel::connection()->query($query);
        if ($rs === false) throw new Exception("Error en es_factura_proforma: ".mainModel::connection()->error);

        if ($rs->num_rows > 0) {
            $d = $rs->fetch_assoc();
            return intval($d['documento_id']) === 4; // 4 = proforma
        }
        return false;
    }

    /* ==========================================================
       PROFORMAS: SOLO ACTUALIZAR ESTADO DESDE PAGOS
       ----------------------------------------------------------
       Regla final:
       - Pagos NO crea registros en facturas_proforma.
       - Pagos NO elimina registros de facturas_proforma.
       - El histórico/identificador se crea únicamente al guardar la proforma
         desde facturación.
       - Si existe el registro en facturas_proforma y la proforma se paga,
         solo se actualiza estado = 1.
       - Si no existe el histórico, pagos no lo inventa; solo continúa para
         no bloquear el cobro. Ese caso debe corregirse en facturación.
       ========================================================== */
    protected function actualizar_estado_facturas_proforma_pago($facturas_id, $estado = 1) {
        $conexion = mainModel::connection();
        $facturas_id = (int)$facturas_id;
        $estado = (int)$estado;

        if ($facturas_id <= 0) {
            return false;
        }

        if (!$this->es_factura_proforma($facturas_id)) {
            return true;
        }

        $stmtCheck = $conexion->prepare("SELECT facturas_proforma_id FROM facturas_proforma WHERE facturas_id = ? LIMIT 1");
        if (!$stmtCheck) {
            throw new Exception("Error preparando validación de facturas_proforma: " . $conexion->error);
        }

        $stmtCheck->bind_param("i", $facturas_id);
        if (!$stmtCheck->execute()) {
            $error = $stmtCheck->error;
            $stmtCheck->close();
            throw new Exception("Error validando facturas_proforma: " . $error);
        }

        $rsCheck = $stmtCheck->get_result();
        $existe = ($rsCheck && $rsCheck->num_rows > 0);
        $stmtCheck->close();

        if (!$existe) {
            error_log("Pago registrado en proforma sin histórico facturas_proforma. facturas_id={$facturas_id}. Pagos no crea el histórico; revisar guardado de facturación.");
            return true;
        }

        $stmt = $conexion->prepare("UPDATE facturas_proforma SET estado = ? WHERE facturas_id = ?");
        if (!$stmt) {
            throw new Exception("Error preparando actualización de facturas_proforma: " . $conexion->error);
        }

        $stmt->bind_param("ii", $estado, $facturas_id);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Error actualizando estado de proforma por pago: " . $error);
        }

        $stmt->close();
        return true;
    }

    /* ==========================================================
       NUMERACIÓN (ROBUSTA)
       ========================================================== */
    /**
     * Genera/recupera número de factura (con soporte a reuso de fallidas).
     * - Si $conexion es null, el método controla su propia transacción.
     * - Si se pasa $conexion, NO abre/commit/rollback aquí (lo maneja el llamador).
     */
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

    /** Suma totales de la factura desde facturas_detalles. */
    protected function obtener_totales_factura($facturas_id) {
        $q = "SELECT 
                ROUND(SUM(fd.cantidad * fd.precio), 2) AS subtotal,
                ROUND(SUM(fd.descuento), 2)            AS descuento,
                ROUND(SUM(fd.isv_valor + fd.isv_valor1), 2) AS impuesto
              FROM facturas_detalles fd
              WHERE fd.facturas_id = '$facturas_id'";
        $rs = mainModel::connection()->query($q);
        if ($rs === false) throw new Exception("Error al obtener totales de la factura: ".mainModel::connection()->error);

        $sub = 0.00; $desc = 0.00; $imp = 0.00;
        if ($rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            $sub  = (float)$row['subtotal'];
            $desc = (float)$row['descuento'];
            $imp  = (float)$row['impuesto'];
        }
        $total = round($sub + $imp - $desc + 1e-9, 2);

        return ['subtotal'=>$sub, 'descuento'=>$desc, 'impuesto'=>$imp, 'total'=>$total];
    }

    /* ==========================================================
       CONVERSIÓN: PROFORMA → FACTURA NORMAL
       ========================================================== */
    protected function convertir_proforma_a_factura($facturas_id) {
        $conexion = mainModel::connection();
        $conexion->begin_transaction();
        try {
            // Traer datos base de la factura proforma
            $qf = "SELECT f.clientes_id, f.apertura_id, f.colaboradores_id, f.importe,
                          f.notas, f.usuario, f.empresa_id, f.fecha_dolar
                   FROM facturas f WHERE f.facturas_id = '$facturas_id'";
            $rsF = $conexion->query($qf);
            if(!$rsF || $rsF->num_rows === 0) { throw new Exception("No se encontró la factura proforma"); }
            $factura = $rsF->fetch_assoc();

            // Obtener número/serie usando el método robusto (documento_id = 1 => factura)
            $num = $this->obtenerNumeroFactura((int)$factura['empresa_id'], 1, $conexion);
            if ($num['error']) {
                throw new Exception($num['mensaje']);
            }
            $secuencia_id = (int)$num['data']['secuencia_facturacion_id'];
            $numero       = (int)$num['data']['numero'];

            // Actualizar factura con secuencia y number
            $uf = "UPDATE facturas
                   SET secuencia_facturacion_id = '".$secuencia_id."',
                       number = '".$numero."',
                       tipo_factura = 1,
                       estado = 2
                   WHERE facturas_id = '$facturas_id'";
            if(!$conexion->query($uf)) { throw new Exception("Error al actualizar la factura"); }

            // Marcar proforma como utilizada/cerrada
            $up = "UPDATE facturas_proforma SET estado = 1 WHERE facturas_id = '$facturas_id'";
            if(!$conexion->query($up)) { throw new Exception("Error al actualizar la proforma"); }

            $conexion->commit();

            return [
                'success' => true,
                'numero_factura' => $numero, // prefijo+relleno se obtienen al consultar/imprimir
                'secuencia_facturacion_id' => $secuencia_id
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
    
            /* -----------------------------------------------------------
             * Normalizar flags que vienen del front
             * ----------------------------------------------------------- */
            $tipoFactura   = isset($datos['tipo_factura']) ? intval($datos['tipo_factura']) : 1;
            $multipleFlag  = 0;
            if (isset($datos['flag_multiple'])) {
                $multipleFlag = intval($datos['flag_multiple']);
            } elseif (isset($_POST['multiple_pago'])) {
                $multipleFlag = intval($_POST['multiple_pago']);
            }
    
            // Si el usuario habilitó PAGOS MÚLTIPLES pero por error vino tipo_factura=1,
            // lo forzamos a crédito/abono (2) para permitir abonos adicionales.
            if ($multipleFlag === 1 && $tipoFactura === 1) {
                $tipoFactura = 2;
                $datos['tipo_factura'] = 2; // reflejar el cambio aguas abajo
            }
    
            /* -----------------------------------------------------------
             * Regla anti-doble pago de contado:
             * ----------------------------------------------------------- */
            $rsExist = $this->valid_pagos_factura($datos['facturas_id']);
            if ($tipoFactura === 1 && $rsExist->num_rows > 0) {
                throw new Exception("Ya existe un pago para esta factura. Habilite pagos múltiples si desea agregar otro pago.");
            }
    
            /* -----------------------------------------------------------
             * Enrutamiento por tipo de factura
             * ----------------------------------------------------------- */
            if ($tipoFactura === 1) {
                // Contado
                $res = $this->procesar_pago_contado_transaccion($conexion, $datos, $esProforma);
            } else {
                // Crédito / Abono (incluye flujo de pagos múltiples)
                $res = $this->procesar_pago_credito_transaccion($conexion, $datos, $esProforma);
            }
    
            $conexion->commit();
            return $res;
    
        } catch (Exception $e) {
            $conexion->rollback();
            return [
                "status"  => false,
                "title"   => "Error",
                "message" => $e->getMessage()
            ];
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

        // NOTA: No asignamos secuencia aquí.
        // - Proforma se convierte en el flujo contable (ver crédito y método convertir_proforma_a_factura)
        // - Factura normal ya trae su número por el flujo de creación (obtenerNumeroFactura)

        // Cerrar factura y CxC
        $this->update_status_factura($datos['facturas_id']);
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], 2, 0);

        // Si es proforma, mantener el histórico y marcarla como pagada.
        // No se elimina ni se convierte en factura normal.
        if ($esProforma) {
            $this->actualizar_estado_facturas_proforma_pago($datos['facturas_id'], 1);
        }

        // Contabilidad:
        // - Factura normal contado desde facturación → NO registra ingreso aquí; lo registra el cierre de caja.
        // - Proforma contado desde facturación → NO registra ingreso aquí; lo registra el cierre de caja.
        // - Pagos desde CxC → SÍ registra ingreso inmediato.
        $origen = isset($datos['origen_pago']) ? $datos['origen_pago'] : 'facturacion';
        if ($origen === 'cxc') {
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

        // Actualizar CxC y factura si queda saldada.
        // IMPORTANTE:
        // - Proforma crédito NO se convierte a factura normal.
        // - Si queda saldada, solo se marca cerrada/pagada manteniendo su secuencia de proforma.
        $this->update_status_factura_cuentas_por_cobrar($datos['facturas_id'], ($nuevoSaldo==0?2:1), $nuevoSaldo);
        if ($nuevoSaldo == 0) {
            $this->update_status_factura($datos['facturas_id']);

            // Si es proforma y quedó pagada, mantener el histórico y marcarla como pagada.
            // No se elimina ni se convierte en factura normal.
            if ($esProforma) {
                $this->actualizar_estado_facturas_proforma_pago($datos['facturas_id'], 1);
            }
        }

        // Crédito/Abono o Proforma → registrar ingreso+mov y marcar contabilizado
        $ingreso_id = $this->registrar_contabilidad_pago($datos);
        $this->marcar_pago_contabilizado($pagoId, $ingreso_id);

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

        // Referencia a guardar en ingresos.factura => prefijo + numero relleno (si aplica)
        $prefijo  = isset($factura['prefijo']) ? $factura['prefijo'] : '';
        $relleno  = isset($factura['relleno']) ? intval($factura['relleno']) : 8;
        $numero   = isset($factura['numero_factura']) ? intval($factura['numero_factura']) : 0;
        $referenciaFactura = ($numero > 0) ? ($prefijo . str_pad($numero, $relleno, "0", STR_PAD_LEFT)) : '';

        // Totales de la factura (para desglosar correctamente)
        $tot = $this->obtener_totales_factura($datos['facturas_id']);
        $totalFactura = max(0.0, $tot['total']);

        // Importe de este pago
        $pagoImporte = round((float)$datos['importe'] + 1e-9, 2);

        // Prorrateo si el pago es parcial. Si liquida (o es muy cercano), usa los totales completos.
        $EPS = 0.005;
        if ($totalFactura > 0 && ($pagoImporte + $EPS) < $totalFactura) {
            $ratio = $pagoImporte / $totalFactura;

            $sub  = round($tot['subtotal']  * $ratio + 1e-9, 2);
            $desc = round($tot['descuento'] * $ratio + 1e-9, 2);
            $imp  = round($tot['impuesto']  * $ratio + 1e-9, 2);

            // Ajuste de centavos para que sub+imp-desc == pagoImporte
            $calcTotal = round($sub + $imp - $desc + 1e-9, 2);
            $delta = round($pagoImporte - $calcTotal, 2);
            if (abs($delta) >= 0.01) {
                // Ajustamos impuesto por simplicidad
                $imp = round($imp + $delta, 2);
                if ($imp < 0) { $imp = 0; }
                $calcTotal = round($sub + $imp - $desc + 1e-9, 2);
            }

            $ing_subtotal  = $sub;
            $ing_descuento = $desc;
            $ing_impuesto  = $imp;
            $ing_total     = $calcTotal; // igual a $pagoImporte
        } else {
            // Pago total o no hay desglose: usa totales completos
            $ing_subtotal  = $tot['subtotal'];
            $ing_descuento = $tot['descuento'];
            $ing_impuesto  = $tot['impuesto'];
            $ing_total     = round($ing_subtotal + $ing_impuesto - $ing_descuento + 1e-9, 2);
        }

        // tipo_ingreso: 1=Ingresos Ventas
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");
        $obs = ($datos['tipo_factura']==1 ? "Pago de factura al contado" : "Abono a factura al crédito");

        $insert = "INSERT INTO ingresos (
            ingresos_id,
            cuentas_id,
            clientes_id,
            empresa_id,
            tipo_ingreso,
            fecha,
            factura,
            subtotal,
            descuento,
            nc,
            impuesto,
            total,
            observacion,
            estado,
            colaboradores_id,
            fecha_registro
        ) VALUES (
            '".$ingresos_id."',
            '".$cuenta['cuentas_id']."',
            '".$factura['clientes_id']."',
            '".$datos['empresa']."',
            '1',
            '".date("Y-m-d")."',
            '".$referenciaFactura."',
            '".$ing_subtotal."',
            '".$ing_descuento."',
            '0',
            '".$ing_impuesto."',
            '".$ing_total."',
            '".$obs."',
            '1',
            '".$datos['colaboradores_id']."',
            '".date("Y-m-d H:i:s")."'
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
        $nuevoSaldo = $saldoActual + floatval($ing_total);

        $mov_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
        $insMov = "INSERT INTO movimientos_cuentas (
            movimientos_cuentas_id,
            cuentas_id,
            empresa_id,
            fecha,
            ingreso,
            egreso,
            saldo,
            colaboradores_id,
            fecha_registro
        ) VALUES (
            '$mov_id',
            '".$cuenta['cuentas_id']."',
            '".$datos['empresa']."',
            '".date("Y-m-d")."',
            '".$ing_total."',
            '0',
            '".$nuevoSaldo."',
            '".$datos['colaboradores_id']."',
            '".date("Y-m-d H:i:s")."'
        )";
        $ok2 = mainModel::connection()->query($insMov);
        if (!$ok2) throw new Exception("Error al registrar movimiento contable: ".mainModel::connection()->error);

        return $ingresos_id;
    }

    /* ==========================================================
       HISTORIAL
       ========================================================== */
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