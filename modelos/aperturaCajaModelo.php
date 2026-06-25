<?php
//aperturaCajaModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";
}

class aperturaCajaModelo extends mainModel{

    protected function agregar_apertura_caja_modelo($datos) {
        $apertura_id = mainModel::correlativo("apertura_id", "apertura");

        $insert = "
            INSERT INTO apertura (
                apertura_id,
                colaboradores_id,
                fecha,
                factura_inicial,
                factura_final,
                apertura,
                neto,
                estado,
                fecha_registro,
                empresa_id
            )
            VALUES (
                '$apertura_id',
                '".$datos['colaboradores_id']."',
                '".$datos['fecha']."',
                '".$datos['factura_inicial']."',
                '".$datos['factura_final']."',
                '".$datos['monto']."',
                '".$datos['neto']."',
                '".$datos['estado']."',
                '".$datos['fecha_registro']."',
                '".$datos['empresa_id_sd']."'
            )
        ";

        $ok = mainModel::connection()->query($insert);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ok;
    }

    protected function agregar_ingresos_contabilidad_modelo($datos) {
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");

        $insert = "
            INSERT INTO ingresos (
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
            )
            VALUES (
                '$ingresos_id',
                '".$datos['cuentas_id']."',
                '".$datos['clientes_id']."',
                '".$datos['empresa_id']."',
                '".$datos['tipo_ingreso']."',
                '".$datos['fecha']."',
                '".$datos['factura']."',
                '".$datos['subtotal']."',
                '".$datos['descuento']."',
                '".$datos['nc']."',
                '".$datos['isv']."',
                '".$datos['total']."',
                '".$datos['observacion']."',
                '".$datos['estado']."',
                '".$datos['colaboradores_id']."',
                '".$datos['fecha_registro']."'
            )
        ";

        $ok = mainModel::connection()->query($insert);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ingresos_id;
    }

    protected function agregar_egresos_contabilidad_modelo($datos) {
        $egresos_id = mainModel::correlativo("egresos_id", "egresos");

        $insert = "
            INSERT INTO egresos (
                egresos_id,
                cuentas_id,
                proveedores_id,
                empresa_id,
                tipo_egreso,
                fecha,
                factura,
                factura_pdf,
                subtotal,
                descuento,
                nc,
                impuesto,
                total,
                observacion,
                estado,
                colaboradores_id,
                fecha_registro,
                categoria_gastos_id
            )
            VALUES (
                '$egresos_id',
                '".$datos['cuentas_id']."',
                '".$datos['proveedores_id']."',
                '".$datos['empresa_id']."',
                '".$datos['tipo_egreso']."',
                '".$datos['fecha']."',
                '".$datos['factura']."',
                NULL,
                '".$datos['subtotal']."',
                '".$datos['descuento']."',
                '".$datos['nc']."',
                '".$datos['impuesto']."',
                '".$datos['total']."',
                '".$datos['observacion']."',
                '".$datos['estado']."',
                '".$datos['colaboradores_id']."',
                '".$datos['fecha_registro']."',
                '".$datos['categoria_gastos_id']."'
            )
        ";

        $ok = mainModel::connection()->query($insert);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $egresos_id;
    }

    protected function getNombreClienteModelo($clientes_id){
        return mainModel::getNombreCliente($clientes_id);
    }

    protected function agregar_movimientos_contabilidad_modelo($datos) {
        $movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

        $insert = "
            INSERT INTO movimientos_cuentas (
                movimientos_cuentas_id,
                cuentas_id,
                empresa_id,
                fecha,
                ingreso,
                egreso,
                saldo,
                colaboradores_id,
                fecha_registro
            )
            VALUES (
                '$movimientos_cuentas_id',
                '".$datos['cuentas_id']."',
                '".$datos['empresa_id']."',
                '".$datos['fecha']."',
                '".$datos['ingreso']."',
                '".$datos['egreso']."',
                '".$datos['saldo']."',
                '".$datos['colaboradores_id']."',
                '".$datos['fecha_registro']."'
            )
        ";

        $ok = mainModel::connection()->query($insert);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ok;
    }

    protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $query = "
            SELECT ingreso, egreso, saldo
            FROM movimientos_cuentas
            WHERE cuentas_id = '$cuentas_id'
              AND empresa_id = '$empresa_id'
            ORDER BY movimientos_cuentas_id DESC
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function valid_ingreso_cuentas_modelo($datos){
        $query = "
            SELECT ingresos_id
            FROM ingresos
            WHERE factura = '".$datos['factura']."'
              AND clientes_id = '".$datos['clientes_id']."'
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function valid_apertura_caja_modelo($datos){
        $empresa_id = isset($datos['empresa_id']) ? (int)$datos['empresa_id'] : (isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0);

        $query = "
            SELECT apertura_id, apertura, neto, estado
            FROM apertura
            WHERE colaboradores_id = '".$datos['colaboradores_id']."'
              AND estado = 1
              AND empresa_id = '$empresa_id'
            ORDER BY apertura_id DESC
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function valid_open_caja($datos){
        $query = "
            SELECT apertura
            FROM cajas
            WHERE apertura_id = '".$datos['apertura_id']."'
              AND estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function validar_cierre_contabilizado_modelo($apertura_id){
        $factura = "AP-".$apertura_id;
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $query = "
            SELECT ingresos_id
            FROM ingresos
            WHERE factura = '$factura'
              AND tipo_ingreso = 1
              AND estado = 1
              AND empresa_id = '$empresa_id'
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql->num_rows > 0;
    }

    protected function cerrar_caja_modelo($datos){
        $apertura_id = isset($datos['apertura_id']) ? (int)$datos['apertura_id'] : 0;

        if($apertura_id > 0){
            $update = "
                UPDATE apertura
                SET
                    factura_inicial = '".$datos['factura_inicial']."',
                    factura_final   = '".$datos['factura_final']."',
                    neto            = '".$datos['neto']."',
                    estado          = '".$datos['estado']."'
                WHERE apertura_id = '$apertura_id'
                  AND estado = 1
            ";
        }else{
            $update = "
                UPDATE apertura
                SET
                    factura_inicial = '".$datos['factura_inicial']."',
                    factura_final   = '".$datos['factura_final']."',
                    neto            = '".$datos['neto']."',
                    estado          = '".$datos['estado']."'
                WHERE fecha = '".$datos['fecha']."'
                  AND colaboradores_id = '".$datos['colaboradores_id']."'
                  AND estado = 1
            ";
        }

        $sql = mainModel::connection()->query($update);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function consultar_factura_inicial($apertura_id){
        $query = "
            SELECT
                f.number AS 'numero',
                sf.prefijo AS 'prefijo',
                sf.rango_final AS 'rango_final',
                sf.relleno AS 'relleno'
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre = 'Factura Electronica'
            ORDER BY f.facturas_id ASC
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function consultar_factura_final($apertura_id){
        $query = "
            SELECT
                f.number AS 'numero',
                sf.prefijo AS 'prefijo',
                sf.rango_final AS 'rango_final',
                sf.fecha_limite AS 'fecha_limite',
                sf.relleno AS 'relleno'
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre = 'Factura Electronica'
            ORDER BY f.facturas_id DESC
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function consulta_facturas_electronicas_con_pagos($apertura_id){
        $query = "
            SELECT DISTINCT f.facturas_id
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            INNER JOIN pagos AS p ON f.facturas_id = p.facturas_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre = 'Factura Electronica'
              AND p.estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function consulta_detalles_facturas($facturas_id){
        return mainModel::getDetalleFactura($facturas_id);
    }

    protected function neto_factura($datos){
        $query = "
            SELECT SUM(importe) AS 'neto'
            FROM facturas
            WHERE fecha = '".$datos['fecha']."'
              AND usuario = '".$datos['colaboradores_id']."'
              AND estado = 2
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function valid_config_apertura_modelo($accion){
        $query = "
            SELECT activar AS validar
            FROM config
            WHERE accion = '$accion'
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function obtener_total_retiros_caja_modelo($apertura_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $query = "
            SELECT COALESCE(SUM(monto), 0) AS total_retiros
            FROM caja_retiros
            WHERE apertura_id = '$apertura_id'
              AND empresa_id = '$empresa_id'
              AND estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (float)$row['total_retiros'];
        }

        return 0;
    }

    protected function obtener_cuenta_efectivo_caja_modelo(){
        $query = "
            SELECT cuentas_id
            FROM tipo_pago
            WHERE tipo_pago_id = 1
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (int)$row['cuentas_id'];
        }

        return 0;
    }

    protected function obtener_total_ventas_caja_modelo($apertura_id){
        $query = "
            SELECT COALESCE(SUM(pd.efectivo), 0) AS total_vendido
            FROM pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd ON pd.pagos_id = p.pagos_id
            INNER JOIN tipo_pago tp ON tp.tipo_pago_id = pd.tipo_pago_id
            INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre IN ('Factura Electronica', 'Factura Proforma')
              AND p.estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (float)$row['total_vendido'];
        }

        return 0;
    }

    protected function obtener_resumen_ventas_cierre_caja_modelo($apertura_id){
        $query = "
            SELECT
                COALESCE(SUM(pd.efectivo), 0) AS total_vendido,

                COALESCE(SUM(
                    CASE
                        WHEN d.nombre = 'Factura Electronica'
                        THEN pd.efectivo
                        ELSE 0
                    END
                ), 0) AS total_factura_normal,

                COALESCE(SUM(
                    CASE
                        WHEN d.nombre = 'Factura Proforma'
                        THEN pd.efectivo
                        ELSE 0
                    END
                ), 0) AS total_proforma,

                COUNT(DISTINCT CASE WHEN d.nombre = 'Factura Electronica' THEN f.facturas_id END) AS cantidad_factura_normal,
                COUNT(DISTINCT CASE WHEN d.nombre = 'Factura Proforma' THEN f.facturas_id END) AS cantidad_proforma,

                COALESCE(SUM(
                    CASE
                        WHEN f.importe > 0
                        THEN ROUND(
                            IFNULL(dt.impuesto, 0) * (pd.efectivo / NULLIF(f.importe, 0)),
                            2
                        )
                        ELSE 0
                    END
                ), 0) AS total_isv
            FROM pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd ON pd.pagos_id = p.pagos_id
            INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d ON sf.documento_id = d.documento_id
            LEFT JOIN (
                SELECT
                    facturas_id,
                    COALESCE(SUM(isv_valor + isv_valor1), 0) AS impuesto
                FROM facturas_detalles
                GROUP BY facturas_id
            ) dt ON dt.facturas_id = f.facturas_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre IN ('Factura Electronica', 'Factura Proforma')
              AND p.estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        $resumen = [
            "total_vendido" => 0,
            "total_factura_normal" => 0,
            "total_proforma" => 0,
            "cantidad_factura_normal" => 0,
            "cantidad_proforma" => 0,
            "total_isv" => 0
        ];

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();

            $resumen["total_vendido"] = (float)($row["total_vendido"] ?? 0);
            $resumen["total_factura_normal"] = (float)($row["total_factura_normal"] ?? 0);
            $resumen["total_proforma"] = (float)($row["total_proforma"] ?? 0);
            $resumen["cantidad_factura_normal"] = (int)($row["cantidad_factura_normal"] ?? 0);
            $resumen["cantidad_proforma"] = (int)($row["cantidad_proforma"] ?? 0);
            $resumen["total_isv"] = (float)($row["total_isv"] ?? 0);
        }

        return $resumen;
    }

    protected function getMontosNoContabilizadosPorCuenta($apertura_id){
        $query = "
            SELECT
                tp.cuentas_id,
                SUM(pd.efectivo) AS monto,

                SUM(CASE WHEN d.nombre = 'Factura Electronica' THEN pd.efectivo ELSE 0 END) AS monto_factura_normal,
                SUM(CASE WHEN d.nombre = 'Factura Proforma' THEN pd.efectivo ELSE 0 END) AS monto_proforma,

                COUNT(DISTINCT CASE WHEN d.nombre = 'Factura Electronica' THEN f.facturas_id END) AS cantidad_factura_normal,
                COUNT(DISTINCT CASE WHEN d.nombre = 'Factura Proforma' THEN f.facturas_id END) AS cantidad_proforma,

                COALESCE(SUM(
                    ROUND(
                        dt.subtotal * (pd.efectivo / NULLIF(f.importe, 0)),
                        2
                    )
                ), 0) AS subtotal,

                COALESCE(SUM(
                    ROUND(
                        dt.descuento * (pd.efectivo / NULLIF(f.importe, 0)),
                        2
                    )
                ), 0) AS descuento,

                COALESCE(SUM(
                    ROUND(
                        dt.impuesto * (pd.efectivo / NULLIF(f.importe, 0)),
                        2
                    )
                ), 0) AS impuesto
            FROM pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd ON pd.pagos_id = p.pagos_id
            INNER JOIN tipo_pago tp ON tp.tipo_pago_id = pd.tipo_pago_id
            INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d ON sf.documento_id = d.documento_id
            LEFT JOIN (
                SELECT
                    facturas_id,
                    COALESCE(SUM(cantidad * precio), 0) AS subtotal,
                    COALESCE(SUM(descuento), 0) AS descuento,
                    COALESCE(SUM(isv_valor + isv_valor1), 0) AS impuesto
                FROM facturas_detalles
                GROUP BY facturas_id
            ) dt ON dt.facturas_id = f.facturas_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre IN ('Factura Electronica', 'Factura Proforma')
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
            GROUP BY tp.cuentas_id
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function obtener_retiros_pendientes_cierre_modelo($apertura_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $query = "
            SELECT
                cr.caja_retiros_id,
                cr.apertura_id,
                cr.egresos_id,
                cr.cuentas_id,
                cr.empresa_id,
                cr.monto,
                cr.motivo,
                cr.observacion,
                cr.estado,
                cr.colaboradores_id,
                cr.fecha,
                cr.fecha_registro,
                COALESCE(cg.categoria_gastos_id, 0) AS categoria_gastos_id,
                COALESCE(cg.es_inversion, 0) AS categoria_es_inversion,
                COALESCE(cg.nombre, '') AS categoria_nombre
            FROM caja_retiros cr
            LEFT JOIN categoria_gastos cg
                ON cg.estado = 1
               AND (
                    UPPER(TRIM(cg.nombre)) = UPPER(TRIM(cr.motivo))
                    OR UPPER(TRIM(cr.motivo)) LIKE CONCAT('%', UPPER(TRIM(cg.nombre)), '%')
                    OR UPPER(TRIM(cg.nombre)) LIKE CONCAT('%', UPPER(TRIM(cr.motivo)), '%')
               )
            WHERE cr.apertura_id = '$apertura_id'
              AND cr.empresa_id = '$empresa_id'
              AND cr.estado = 1
              AND IFNULL(cr.egresos_id,0) = 0
              AND cr.monto > 0
            GROUP BY cr.caja_retiros_id
            ORDER BY cr.caja_retiros_id ASC
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql;
    }

    protected function actualizar_retiro_caja_egreso_modelo($caja_retiros_id, $egresos_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $update = "
            UPDATE caja_retiros
            SET egresos_id = '$egresos_id'
            WHERE caja_retiros_id = '$caja_retiros_id'
              AND empresa_id = '$empresa_id'
            LIMIT 1
        ";

        $ok = mainModel::connection()->query($update);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ok;
    }

    protected function marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingresos_id){
        $update = "
            UPDATE pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd ON pd.pagos_id = p.pagos_id
            INNER JOIN tipo_pago tp ON tp.tipo_pago_id = pd.tipo_pago_id
            INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d ON sf.documento_id = d.documento_id
            SET
                p.contabilizado = 1,
                p.referencia_ingreso_id = '$ingresos_id'
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre IN ('Factura Electronica', 'Factura Proforma')
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
              AND tp.cuentas_id = '$cuentas_id'
        ";

        $ok = mainModel::connection()->query($update);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ok;
    }

    protected function marcar_pagos_contabilizados($apertura_id, $cuentas_id, $ingresos_id){
        $update = "
            UPDATE pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            SET
                p.contabilizado = 1,
                p.referencia_ingreso_id = '$ingresos_id'
            WHERE f.apertura_id = '$apertura_id'
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
        ";

        $ok = mainModel::connection()->query($update);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $ok;
    }

    protected function registrar_ingresos_cierre_caja_modelo($apertura_id, $fecha, $fecha_registro, $empresa_id, $colaboradores_id){
        $tipo_ingreso = 1;

        $montos = $this->getMontosNoContabilizadosPorCuenta($apertura_id);

        if($montos){
            while($monto = $montos->fetch_assoc()){
                $cuentas_id = (int)$monto['cuentas_id'];
                $total_contabilizar = round((float)$monto['monto'] + 1e-9, 2);

                /*
                    En el cierre se registra el ingreso completo vendido.
                    Incluye:
                    - Factura Electronica pagada al contado y no contabilizada.
                    - Factura Proforma pagada al contado y no contabilizada.
                    Los abonos CxC que ya fueron contabilizados no entran aquí.
                */
                if($total_contabilizar <= 0){
                    $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, 0);
                    continue;
                }

                $subtotal = round((float)($monto['subtotal'] ?? 0) + 1e-9, 2);
                $descuento = round((float)($monto['descuento'] ?? 0) + 1e-9, 2);
                $isv_neto = round((float)($monto['impuesto'] ?? 0) + 1e-9, 2);

                /*
                    Ajuste de seguridad:
                    Si por alguna factura no hay detalle, evitamos dejar subtotal en cero.
                */
                if($subtotal <= 0 && $isv_neto <= 0){
                    $subtotal = $total_contabilizar;
                    $descuento = 0;
                    $isv_neto = 0;
                }

                $montoFacturaNormal = round((float)($monto['monto_factura_normal'] ?? 0) + 1e-9, 2);
                $montoProforma = round((float)($monto['monto_proforma'] ?? 0) + 1e-9, 2);
                $cantidadFacturaNormal = (int)($monto['cantidad_factura_normal'] ?? 0);
                $cantidadProforma = (int)($monto['cantidad_proforma'] ?? 0);

                $observacion = "Ingresos por venta Cierre de Caja AP-".$apertura_id.
                    ". Total: L. ".number_format($total_contabilizar, 2).
                    " | Factura normal: L. ".number_format($montoFacturaNormal, 2)." (".$cantidadFacturaNormal.")".
                    " | Proforma: L. ".number_format($montoProforma, 2)." (".$cantidadProforma.")".
                    " | ISV: L. ".number_format($isv_neto, 2);

                $datos_ingreso = [
                    "clientes_id" => 2,
                    "cuentas_id" => $cuentas_id,
                    "empresa_id" => $empresa_id,
                    "fecha" => $fecha,
                    "factura" => "AP-".$apertura_id,
                    "subtotal" => $subtotal,
                    "isv" => $isv_neto,
                    "descuento" => $descuento,
                    "nc" => 0,
                    "total" => $total_contabilizar,
                    "observacion" => $observacion,
                    "estado" => 1,
                    "fecha_registro" => $fecha_registro,
                    "colaboradores_id" => $colaboradores_id,
                    "tipo_ingreso" => $tipo_ingreso
                ];

                $ingreso_id = $this->agregar_ingresos_contabilidad_modelo($datos_ingreso);

                $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingreso_id);

                $saldo_actual = 0;
                $rsSaldo = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);

                if($rsSaldo && $rsSaldo->num_rows > 0){
                    $filaS = $rsSaldo->fetch_assoc();
                    $saldo_actual = isset($filaS['saldo']) ? (float)$filaS['saldo'] : 0;
                }

                $nuevo_saldo = $saldo_actual + $total_contabilizar;

                $datos_movimiento = [
                    "cuentas_id" => $cuentas_id,
                    "empresa_id" => $empresa_id,
                    "fecha" => $fecha,
                    "ingreso" => $total_contabilizar,
                    "egreso" => 0,
                    "saldo" => $nuevo_saldo,
                    "colaboradores_id" => $colaboradores_id,
                    "fecha_registro" => $fecha_registro
                ];

                $this->agregar_movimientos_contabilidad_modelo($datos_movimiento);
            }
        }

        return true;
    }

    protected function texto_es_inversion_cierre_modelo($texto){
        $texto = trim((string)$texto);

        if($texto === ''){
            return false;
        }

        $texto = mb_strtoupper($texto, 'UTF-8');

        return (
            strpos($texto, 'INVERSION') !== false ||
            strpos($texto, 'INVERSIÓN') !== false ||
            strpos($texto, 'REPOSICION') !== false ||
            strpos($texto, 'REPOSICIÓN') !== false
        );
    }

    protected function registrar_entrada_inversion_por_retiro_cierre_modelo($apertura_id, $caja_retiros_id, $cuenta_origen_id, $monto, $fecha, $fecha_registro, $empresa_id, $colaboradores_id, $motivo){
        $cuenta_inversion_id = $this->obtener_cuenta_inversion_cierre_modelo();

        if($cuenta_inversion_id <= 0 || $monto <= 0){
            return true;
        }

        if((int)$cuenta_inversion_id === (int)$cuenta_origen_id){
            return true;
        }

        $factura_inversion = 'INV-RC-'.$apertura_id.'-'.$caja_retiros_id;

        $datos_ingreso = [
            "clientes_id" => 2,
            "cuentas_id" => $cuenta_inversion_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "factura" => $factura_inversion,
            "subtotal" => $monto,
            "isv" => 0,
            "descuento" => 0,
            "nc" => 0,
            "total" => $monto,
            "observacion" => mb_substr("Entrada a cuenta de inversión por retiro de caja AP-".$apertura_id." - ".$motivo, 0, 150, 'UTF-8'),
            "estado" => 1,
            "fecha_registro" => $fecha_registro,
            "colaboradores_id" => $colaboradores_id,
            "tipo_ingreso" => 2
        ];

        $this->agregar_ingresos_contabilidad_modelo($datos_ingreso);

        $saldo_inversion = $this->obtener_saldo_actual_cuenta_cierre_modelo($cuenta_inversion_id);
        $nuevo_saldo_inversion = $saldo_inversion + $monto;

        $this->agregar_movimientos_contabilidad_modelo([
            "cuentas_id" => $cuenta_inversion_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "ingreso" => $monto,
            "egreso" => 0,
            "saldo" => $nuevo_saldo_inversion,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ]);

        return true;
    }

    protected function registrar_egresos_retiros_cierre_caja_modelo($apertura_id, $fecha, $fecha_registro, $empresa_id, $colaboradores_id){
        $retiros = $this->obtener_retiros_pendientes_cierre_modelo($apertura_id);

        if(!$retiros || $retiros->num_rows <= 0){
            return true;
        }

        while($retiro = $retiros->fetch_assoc()){
            $caja_retiros_id = (int)$retiro['caja_retiros_id'];
            $cuentas_id = (int)$retiro['cuentas_id'];
            $monto = (float)$retiro['monto'];
            $motivo = trim($retiro['motivo']);
            $observacion_retiro = trim($retiro['observacion']);
            $categoria_gastos_id = (int)$retiro['categoria_gastos_id'];
            $categoria_es_inversion = isset($retiro['categoria_es_inversion']) ? (int)$retiro['categoria_es_inversion'] : 0;
            $categoria_nombre = isset($retiro['categoria_nombre']) ? trim($retiro['categoria_nombre']) : '';

            $es_retiro_inversion = (
                $categoria_es_inversion === 1 ||
                $this->texto_es_inversion_cierre_modelo($motivo) ||
                $this->texto_es_inversion_cierre_modelo($observacion_retiro) ||
                $this->texto_es_inversion_cierre_modelo($categoria_nombre)
            );

            if($es_retiro_inversion && $categoria_gastos_id <= 0){
                $categoria_gastos_id = $this->obtener_categoria_inversion_cierre_modelo();
            }

            if($monto <= 0 || $cuentas_id <= 0){
                continue;
            }

            $observacion = $es_retiro_inversion
                ? "Retiro de caja a inversión cierre AP-".$apertura_id." - ".$motivo
                : "Retiro de caja cierre AP-".$apertura_id." - ".$motivo;

            if($observacion_retiro !== ""){
                $observacion .= " - ".$observacion_retiro;
            }

            $observacion = mb_substr($observacion, 0, 150, 'UTF-8');

            $datos_egreso = [
                "cuentas_id" => $cuentas_id,
                "proveedores_id" => 1,
                "empresa_id" => $empresa_id,
                "tipo_egreso" => 2,
                "fecha" => $fecha,
                "factura" => "RC-".$apertura_id."-".$caja_retiros_id,
                "subtotal" => $monto,
                "descuento" => 0,
                "nc" => 0,
                "impuesto" => 0,
                "total" => $monto,
                "observacion" => $observacion,
                "estado" => 1,
                "colaboradores_id" => $colaboradores_id,
                "fecha_registro" => $fecha_registro,
                "categoria_gastos_id" => $categoria_gastos_id
            ];

            $egresos_id = $this->agregar_egresos_contabilidad_modelo($datos_egreso);

            $saldo_actual = 0;
            $rsSaldo = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);

            if($rsSaldo && $rsSaldo->num_rows > 0){
                $filaS = $rsSaldo->fetch_assoc();
                $saldo_actual = isset($filaS['saldo']) ? (float)$filaS['saldo'] : 0;
            }

            $nuevo_saldo = $saldo_actual - $monto;

            $datos_movimiento = [
                "cuentas_id" => $cuentas_id,
                "empresa_id" => $empresa_id,
                "fecha" => $fecha,
                "ingreso" => 0,
                "egreso" => $monto,
                "saldo" => $nuevo_saldo,
                "colaboradores_id" => $colaboradores_id,
                "fecha_registro" => $fecha_registro
            ];

            $this->agregar_movimientos_contabilidad_modelo($datos_movimiento);

            if($es_retiro_inversion){
                $this->registrar_entrada_inversion_por_retiro_cierre_modelo(
                    $apertura_id,
                    $caja_retiros_id,
                    $cuentas_id,
                    $monto,
                    $fecha,
                    $fecha_registro,
                    $empresa_id,
                    $colaboradores_id,
                    $motivo
                );
            }

            $this->actualizar_retiro_caja_egreso_modelo($caja_retiros_id, $egresos_id);
        }

        return true;
    }

    protected function obtener_categoria_inversion_cierre_modelo(){
        $query = "
            SELECT categoria_gastos_id
            FROM categoria_gastos
            WHERE estado = 1
              AND IFNULL(es_inversion, 0) = 1
            ORDER BY categoria_gastos_id ASC
            LIMIT 1
        ";
    
        $sql = mainModel::connection()->query($query);
    
        if(!$sql){
            die(mainModel::connection()->error);
        }
    
        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (int)$row['categoria_gastos_id'];
        }
    
        return 0;
    }
    
    protected function obtener_cuenta_inversion_cierre_modelo(){
        $query = "
            SELECT cuentas_id
            FROM cuentas
            WHERE estado = 1
              AND IFNULL(es_inversion, 0) = 1
            ORDER BY cuentas_id ASC
            LIMIT 1
        ";
    
        $sql = mainModel::connection()->query($query);
    
        if(!$sql){
            die(mainModel::connection()->error);
        }
    
        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (int)$row['cuentas_id'];
        }
    
        return 0;
    }
    
    protected function obtener_costo_productos_vendidos_caja_modelo($apertura_id){
        /*
            Inversión / reposición automática:
            Se calcula sobre el costo de productos vendidos en documentos cobrados
            dentro de la apertura de caja. Incluye factura normal y proforma,
            porque ambas pueden cobrar y rebajar inventario según configuración.

            Si una factura tiene pago parcial, se aparta el costo proporcional
            al monto realmente cobrado en la caja.
        */
        $query = "
            SELECT COALESCE(SUM(
                CASE
                    WHEN f.importe > 0 THEN ROUND(dt.costo_productos * (pg.total_pagado / NULLIF(f.importe, 0)), 2)
                    ELSE dt.costo_productos
                END
            ), 0) AS costo_productos_vendidos
            FROM facturas f
            INNER JOIN secuencia_facturacion sf
                ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d
                ON sf.documento_id = d.documento_id
            INNER JOIN (
                SELECT
                    facturas_id,
                    COALESCE(SUM(cantidad * costo_unitario), 0) AS costo_productos
                FROM facturas_detalles
                GROUP BY facturas_id
            ) dt ON dt.facturas_id = f.facturas_id
            INNER JOIN (
                SELECT
                    p.facturas_id,
                    COALESCE(SUM(pd.efectivo), 0) AS total_pagado
                FROM pagos p
                INNER JOIN pagos_detalles pd ON pd.pagos_id = p.pagos_id
                WHERE p.estado = 1
                GROUP BY p.facturas_id
            ) pg ON pg.facturas_id = f.facturas_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre IN ('Factura Electronica', 'Factura Proforma')
              AND pg.total_pagado > 0
        ";
    
        $sql = mainModel::connection()->query($query);
    
        if(!$sql){
            die(mainModel::connection()->error);
        }
    
        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (float)$row['costo_productos_vendidos'];
        }
    
        return 0;
    }
    
    protected function validar_inversion_cierre_registrada_modelo($apertura_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
    
        $query = "
            SELECT egresos_id
            FROM egresos
            WHERE factura = 'INV-AP-$apertura_id'
              AND empresa_id = '$empresa_id'
              AND estado = 1
            LIMIT 1
        ";
    
        $sql = mainModel::connection()->query($query);
    
        if(!$sql){
            die(mainModel::connection()->error);
        }
    
        return $sql->num_rows > 0;
    }
    
    protected function obtener_monto_inversion_automatico_cierre_modelo($apertura_id){
        $categoria_inversion_id = $this->obtener_categoria_inversion_cierre_modelo();
        $cuenta_inversion_id = $this->obtener_cuenta_inversion_cierre_modelo();
        $cuenta_caja_id = $this->obtener_cuenta_efectivo_caja_modelo();
    
        if($categoria_inversion_id <= 0 || $cuenta_inversion_id <= 0 || $cuenta_caja_id <= 0){
            return 0;
        }
    
        if($cuenta_inversion_id == $cuenta_caja_id){
            return 0;
        }
    
        $monto = $this->obtener_costo_productos_vendidos_caja_modelo($apertura_id);
    
        if($monto <= 0){
            return 0;
        }
    
        return $monto;
    }
    
    protected function obtener_saldo_actual_cuenta_cierre_modelo($cuentas_id){
        $saldo_actual = 0;
    
        $rsSaldo = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);
    
        if($rsSaldo && $rsSaldo->num_rows > 0){
            $filaS = $rsSaldo->fetch_assoc();
            $saldo_actual = isset($filaS['saldo']) ? (float)$filaS['saldo'] : 0;
        }
    
        return $saldo_actual;
    }
    
    protected function registrar_inversion_automatica_cierre_caja_modelo($apertura_id, $fecha, $fecha_registro, $empresa_id, $colaboradores_id){
        if($this->validar_inversion_cierre_registrada_modelo($apertura_id)){
            return true;
        }
    
        $categoria_inversion_id = $this->obtener_categoria_inversion_cierre_modelo();
        $cuenta_inversion_id = $this->obtener_cuenta_inversion_cierre_modelo();
        $cuenta_caja_id = $this->obtener_cuenta_efectivo_caja_modelo();
        $monto_inversion = $this->obtener_monto_inversion_automatico_cierre_modelo($apertura_id);
    
        if($categoria_inversion_id <= 0 || $cuenta_inversion_id <= 0 || $cuenta_caja_id <= 0 || $monto_inversion <= 0){
            return true;
        }
    
        $observacion = "Inversión / reposición automática por cierre de caja AP-$apertura_id";
    
        $datos_egreso = [
            "cuentas_id" => $cuenta_caja_id,
            "proveedores_id" => 1,
            "empresa_id" => $empresa_id,
            "tipo_egreso" => 2,
            "fecha" => $fecha,
            "factura" => "INV-AP-$apertura_id",
            "subtotal" => $monto_inversion,
            "descuento" => 0,
            "nc" => 0,
            "impuesto" => 0,
            "total" => $monto_inversion,
            "observacion" => $observacion,
            "estado" => 1,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro,
            "categoria_gastos_id" => $categoria_inversion_id
        ];
    
        $this->agregar_egresos_contabilidad_modelo($datos_egreso);
    
        $saldo_caja = $this->obtener_saldo_actual_cuenta_cierre_modelo($cuenta_caja_id);
        $nuevo_saldo_caja = $saldo_caja - $monto_inversion;
    
        $this->agregar_movimientos_contabilidad_modelo([
            "cuentas_id" => $cuenta_caja_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "ingreso" => 0,
            "egreso" => $monto_inversion,
            "saldo" => $nuevo_saldo_caja,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ]);
    
        $datos_ingreso = [
            "clientes_id" => 2,
            "cuentas_id" => $cuenta_inversion_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "factura" => "INV-AP-$apertura_id",
            "subtotal" => $monto_inversion,
            "isv" => 0,
            "descuento" => 0,
            "nc" => 0,
            "total" => $monto_inversion,
            "observacion" => "Entrada automática a cuenta de inversión / reposición por cierre de caja AP-$apertura_id",
            "estado" => 1,
            "fecha_registro" => $fecha_registro,
            "colaboradores_id" => $colaboradores_id,
            "tipo_ingreso" => 2
        ];
    
        $this->agregar_ingresos_contabilidad_modelo($datos_ingreso);
    
        $saldo_inversion = $this->obtener_saldo_actual_cuenta_cierre_modelo($cuenta_inversion_id);
        $nuevo_saldo_inversion = $saldo_inversion + $monto_inversion;
    
        $this->agregar_movimientos_contabilidad_modelo([
            "cuentas_id" => $cuenta_inversion_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "ingreso" => $monto_inversion,
            "egreso" => 0,
            "saldo" => $nuevo_saldo_inversion,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ]);
    
        return true;
    }

    protected function registrar_movimientos_contables_cierre_modelo($apertura_id){
        $cn = mainModel::connection();
        $cn->begin_transaction();

        try{
            $fecha = date('Y-m-d');
            $fecha_registro = date('Y-m-d H:i:s');
            $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
            $colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

            if($empresa_id <= 0 || $colaboradores_id <= 0){
                throw new Exception("No se pudo identificar la empresa o el usuario de la sesión.");
            }

            /*
                ORDEN CORRECTO DEL CIERRE:
                1. Registrar ingresos completos de ventas.
                2. Registrar egresos de retiros pendientes de caja.
                3. Registrar inversión automática si existe:
                - categoria_gastos.es_inversion = 1
                - cuentas.es_inversion = 1
                - costo de productos vendidos > 0
                4. Registrar salida de caja.
                5. Registrar entrada a cuenta de inversión.
            */

            $this->registrar_ingresos_cierre_caja_modelo(
                $apertura_id,
                $fecha,
                $fecha_registro,
                $empresa_id,
                $colaboradores_id
            );

            $this->registrar_egresos_retiros_cierre_caja_modelo(
                $apertura_id,
                $fecha,
                $fecha_registro,
                $empresa_id,
                $colaboradores_id
            );

            $this->registrar_inversion_automatica_cierre_caja_modelo(
                $apertura_id,
                $fecha,
                $fecha_registro,
                $empresa_id,
                $colaboradores_id
            );

            $cn->commit();
            return true;

        }catch(Throwable $e){
            $cn->rollback();
            throw $e;
        }
    }
}