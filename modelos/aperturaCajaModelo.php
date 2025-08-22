<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";    
}

class aperturaCajaModelo extends mainModel{

    /* ==========================
       APERTURA
       ========================== */
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
        if(!$ok){ die(mainModel::connection()->error); }

        // devuelve TRUE como antes
        return $ok;
    }

    /* ==========================
       INGRESOS / MOVIMIENTOS
       ========================== */
    protected function agregar_ingresos_contabilidad_modelo($datos) {
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");
        // Valor por defecto para evitar warning
        $recibide = isset($datos['recibide']) ? $datos['recibide'] : '';

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
                fecha_registro, 
                recibide
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
                '".$datos['fecha_registro']."', 
                '".$recibide."'
            )
        ";

        $ok = mainModel::connection()->query($insert);
        if(!$ok){ die(mainModel::connection()->error); }

        return $ingresos_id;
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
        if(!$ok){ die(mainModel::connection()->error); }

        return $ok;
    }

    protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id){
        $query = "SELECT ingreso, egreso, saldo
            FROM movimientos_cuentas
            WHERE cuentas_id = '$cuentas_id'
            ORDER BY movimientos_cuentas_id DESC LIMIT 1";
        
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;                
    }

    /* ==========================
       VALIDACIONES
       ========================== */
    protected function valid_ingreso_cuentas_modelo($datos){
        $query = "SELECT ingresos_id 
            FROM ingresos 
            WHERE factura = '".$datos['factura']."' AND clientes_id = '".$datos['clientes_id']."'";
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }

    protected function valid_apertura_caja_modelo($datos){
        $query = "SELECT apertura_id 
            FROM apertura 
            WHERE colaboradores_id = '".$datos['colaboradores_id']."' AND estado = 1";

        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }

        return $sql;
    }
    
    protected function valid_open_caja($datos){
        $query = "SELECT apertura 
            FROM cajas 
            WHERE apertura_id = '".$datos['apertura_id']."' AND estado = 1";
        
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }

    /* ==========================
       CIERRE
       ========================== */
    protected function cerrar_caja_modelo($datos){
        $update = "UPDATE apertura
        SET 
            factura_inicial = '".$datos['factura_inicial']."',
            factura_final   = '".$datos['factura_final']."',    
            neto            = '".$datos['neto']."',                    
            estado          = '".$datos['estado']."'
        WHERE fecha = '".$datos['fecha']."' 
          AND colaboradores_id = '".$datos['colaboradores_id']."' 
          AND estado = 1";
        
        $sql = mainModel::connection()->query($update);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;                
    }
    
    protected function consultar_factura_inicial($apertura_id){
        $query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.rango_final As 'rango_final', sf.relleno AS 'relleno'
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id' AND f.estado = 2 AND d.nombre = 'Factura Electronica'
            ORDER BY f.facturas_id ASC LIMIT 1";
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }
    
    protected function consultar_factura_final($apertura_id){
        $query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.rango_final As 'rango_final', sf.fecha_limite AS 'fecha_limite', sf.relleno AS 'relleno'
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id' AND f.estado = 2 AND d.nombre = 'Factura Electronica'
            ORDER BY f.facturas_id DESC LIMIT 1";

        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }    

    protected function consulta_facturas_electronicas_con_pagos($apertura_id){
        $query = "SELECT f.facturas_id
            FROM facturas AS f
            INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento AS d ON sf.documento_id = d.documento_id
            INNER JOIN pagos AS p ON f.facturas_id = p.facturas_id
            WHERE f.apertura_id = '$apertura_id' 
              AND f.estado = 2 
              AND d.nombre = 'Factura Electronica'
              AND p.estado = 1";
    
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }

    protected function consulta_detalles_facturas($facturas_id){
        return mainModel::getDetalleFactura($facturas_id);
    }

    protected function neto_factura($datos){
        $query = "SELECT SUM(importe) AS 'neto'
            FROM facturas
            WHERE fecha = '".$datos['fecha']."' AND usuario = '".$datos['colaboradores_id']."' AND estado = 2";
    
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;            
    }    
    
    protected function valid_config_apertura_modelo($accion){
        $query = "SELECT activar AS validar
            FROM config
            WHERE accion = '$accion'";

        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;
    }            

    /* ==========================
       NUEVOS MÉTODOS (CIERRE)
       ========================== */

    // Montos por cuenta para pagos NO contabilizados (derivando cuenta desde tipo_pago)
    protected function getMontosNoContabilizadosPorCuenta($apertura_id){
        $query = "
            SELECT tp.cuentas_id, SUM(pd.efectivo) AS monto
            FROM pagos p
            INNER JOIN facturas f         ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd  ON pd.pagos_id   = p.pagos_id
            INNER JOIN tipo_pago tp       ON tp.tipo_pago_id = pd.tipo_pago_id
            INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
            INNER JOIN documento d        ON sf.documento_id = d.documento_id
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND d.nombre = 'Factura Electronica'
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
            GROUP BY tp.cuentas_id
        ";
        $sql = mainModel::connection()->query($query);
        if(!$sql){ die(mainModel::connection()->error); }
        return $sql;
    }

    // Marca pagos como contabilizados por cuenta (derivada desde tipo_pago) y guarda el ingreso de referencia
    protected function marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingresos_id){
        $update = "
            UPDATE pagos p
            INNER JOIN facturas f         ON f.facturas_id = p.facturas_id
            INNER JOIN pagos_detalles pd  ON pd.pagos_id   = p.pagos_id
            INNER JOIN tipo_pago tp       ON tp.tipo_pago_id = pd.tipo_pago_id
            SET p.contabilizado = 1, p.referencia_ingreso_id = '$ingresos_id'
            WHERE f.apertura_id = '$apertura_id'
              AND f.estado = 2
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
              AND tp.cuentas_id = '$cuentas_id'
        ";
        $ok = mainModel::connection()->query($update);
        if(!$ok){ die(mainModel::connection()->error); }
        return $ok;
    }

    /* (Compat) marcar por cuenta directa si la necesitas en otro lado */
    protected function marcar_pagos_contabilizados($apertura_id, $cuentas_id, $ingresos_id){
        $update = "
            UPDATE pagos p
            INNER JOIN facturas f ON f.facturas_id = p.facturas_id
            SET p.contabilizado = 1, p.referencia_ingreso_id = '$ingresos_id'
            WHERE f.apertura_id = '$apertura_id' 
              AND p.estado = 1
              AND IFNULL(p.contabilizado,0) = 0
        ";
        $ok = mainModel::connection()->query($update);
        if(!$ok){ die(mainModel::connection()->error); }
        return $ok;
    }
}