<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";    
}

class pagoCompraModelo extends mainModel{
    protected function agregar_pago_compras_base($res) {
        // Validar que todos los campos necesarios estén presentes
        if (empty($res['compras_id']) || empty($res['fecha']) || empty($res['importe'])) {
            return [
                "alert" => "simple",
                "title" => "Error",
                "text" => "Faltan datos requeridos para procesar el pago",
                "type" => "error"
            ];
        }

        if ($res['tipo_pago'] == 2 || $res['multiple_pago'] == 1) {
            return $this->procesarPagoCredito($res);
        } else {
            return $this->procesarPagoContado($res);
        }
    }
    
    protected function procesarPagoCredito($res) {
        $saldoCredito = $this->obtenerSaldoCredito($res['compras_id']);
        
        if ($saldoCredito === false) {
            return [
                "alert" => "simple",
                "title" => "Error",
                "text" => "No se pudo obtener información de la cuenta por pagar",
                "type" => "error"
            ];
        }

        if ($res['abono'] > $saldoCredito) {
            return [
                "alert" => "simple",
                "title" => "Error",
                "text" => "El abono es mayor al importe pendiente",
                "type" => "error"
            ];
        }

        $nuevoSaldo = $this->actualizarSaldoCompra($res, $saldoCredito);
        $query = $this->agregarPagoCompra($res);
        
        if (!$query) {
            return [
                "alert" => "simple",
                "title" => "Error",
                "text" => "No se pudo registrar el pago en la base de datos",
                "type" => "error"
            ];
        }
        
        $pagoscompras_id = $this->consultar_codigo_pago_modelo($res['compras_id'])->fetch_assoc()['pagoscompras_id'];
        $this->agregarDetallePago($pagoscompras_id, $res);
        $this->registrarEgresoContable($res);
        
        $saldoNuevo = $this->obtenerSaldoActualizado($res['compras_id']);
        
        // Determinar si el saldo llegó a cero para imprimir
        $funcionExtra = "";
        if ($nuevoSaldo == 0) {
            $funcionExtra = "printPurchase(".$res['compras_id'].");";
        }
        
        if ($res['multiple_pago'] == 1 && $saldoNuevo > 0) {
            return [
                "alert" => "save_simple",
                "title" => "Abono registrado",
                "text" => "El abono se ha registrado correctamente",
                "type" => "success",
                "form" => "formEfectivoPurchase",
                "funcion" => "getBancoPurchase();listar_cuentas_por_pagar_proveedores();saldoCompras(".$res['compras_id'].");getProveedores();getColaboradores();getColaboradorCompras();",
                "closeAllModals" => true
            ];
        } else {
            return [
                "alert" => "save_simple",
                "title" => "Pago completado",
                "text" => "El pago se ha registrado correctamente",
                "type" => "success",
                "form" => "formEfectivoPurchase",
                "funcion" => "getBancoPurchase();listar_cuentas_por_pagar_proveedores();".$funcionExtra."getProveedores();getColaboradores();getColaboradorCompras();",
                "closeAllModals" => true
            ];
        }
    }
    
    protected function procesarPagoContado($res) {
        $query = $this->agregarPagoCompra($res);
        
        if (!$query) {
            return [
                "alert" => "simple",
                "title" => "Error",
                "text" => "No se pudo registrar el pago en la base de datos",
                "type" => "error"
            ];
        }
        
        $pagoscompras_id = $this->consultar_codigo_pago_modelo($res['compras_id'])->fetch_assoc()['pagoscompras_id'];
        $this->agregarDetallePago($pagoscompras_id, $res);
        
        $this->update_status_compras($res['compras_id']);
        $this->update_cuenta_compras($res['compras_id'], $res['cuentas_id']);
        $this->registrarEgresoContable($res);
        
        return [
            "alert" => "save_simple",
            "title" => "Pago registrado",
            "text" => "El pago se ha registrado correctamente",
            "type" => "success",
            "form" => "formEfectivoPurchase",
            "funcion" => "getBancoPurchase();listar_cuentas_por_pagar_proveedores();printPurchase(".$res['compras_id'].");getProveedores();getColaboradores();getColaboradorCompras();",
            "closeAllModals" => true
        ];
    }
    
    protected function obtenerSaldoCredito($compras_id) {
        $get_cxc_proveedor = $this->consultar_compra_cuentas_por_pagar($compras_id);
                            
        if ($get_cxc_proveedor->num_rows > 0) {
            $rec = $get_cxc_proveedor->fetch_assoc();
            return $rec['saldo'];
        }
        
        return false;
    }
    
    protected function obtenerSaldoActualizado($compras_id) {
        $get_cxc_proveedor = $this->consultar_compra_cuentas_por_pagar($compras_id);
                            
        if ($get_cxc_proveedor->num_rows > 0) {
            $rec = $get_cxc_proveedor->fetch_assoc();
            return $rec['saldo'];
        }
        
        return 0;
    }
    
    protected function actualizarSaldoCompra($res, $saldoCredito) {
        $nuevoSaldo = $saldoCredito - $res['abono'];
        $estado = ($nuevoSaldo == 0) ? 2 : 1;
        $this->update_status_compras_cuentas_por_pagar($res['compras_id'], $estado, $nuevoSaldo);
        
        if ($nuevoSaldo == 0) {
            $this->update_status_compras($res['compras_id']);
        }
        
        return $nuevoSaldo;
    }
    
    protected function agregarPagoCompra($res) {
        $importe = $res['importe'];

        if($res['abono'] > 0){
            $importe = $res['abono'] === "" ? 0 : $res['abono'];
        }

        $pagoscompras_id = mainModel::correlativo("pagoscompras_id", "pagoscompras");
        $insert = "INSERT INTO pagoscompras 
            VALUES('$pagoscompras_id','".$res['compras_id']."','".$res['tipo_pago']."','".$res['fecha']."',
            '".$importe."','".$res['efectivo']."','".$res['cambio']."','".$res['tarjeta']."',
            '".$res['usuario']."','".$res['estado']."','".$res['empresa']."','".$res['fecha_registro']."')";
        
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
    
        return $result;
    }
    
    protected function agregarDetallePago($pagoscompras_id, $res) {
        $datos_pago_detalle = [
            "pagoscompras_id" => $pagoscompras_id,
            "tipo_pago_id" => $res['metodo_pago'],
            "banco_id" => $res['banco_id'],
            "efectivo" => isset($res['abono']) && $res['abono'] > 0 ? $res['abono'] : $res['importe'],
            "descripcion1" => $res['referencia_pago1'],
            "descripcion2" => $res['referencia_pago2'],
            "descripcion3" => $res['referencia_pago3'],
        ];
        
        if ($this->valid_pagos_detalles_compras($pagoscompras_id, $res['metodo_pago'])->num_rows == 0) {
            return $this->agregar_pago_detalles_compras_modelo($datos_pago_detalle);
        }
        
        return true;
    }
    
    protected function registrarEgresoContable($res) {
        // Consultar información necesaria
        $infoProveedor = $this->consultar_proveedor_id_compra($res['compras_id'])->fetch_assoc();
        $detallesCompra = $this->obtenerDetallesCompra($res['compras_id']);
        
        // Preparar datos para el egreso
        $tipo_egreso = 1; // COMPRA
        $observacion = "Egresos por compras";
        $egresos_id = mainModel::correlativo("egresos_id", "egresos");
        $categoria_gastos_id = 0;
        
        $colaboradores_id = isset($res["colaboradores_id"]) ? $res["colaboradores_id"] : '';
        
        // Determinar si es abono o pago completo para ajustar los montos
        if ($res['tipo_pago'] == 2 || $res['multiple_pago'] == 1) {
            $datosEgresos = [
                "proveedores_id" => $infoProveedor['proveedores_id'],
                "cuentas_id" => $res['cuentas_id'],
                "empresa_id" => $res['empresa'],
                "tipo_egreso" => $tipo_egreso,
                "fecha" => $res['fecha'],
                "factura" => $infoProveedor['factura'],
                "subtotal" => $res['abono'],
                "isv" => 0,
                "descuento" => 0,
                "nc" => 0,
                "total" => $res['abono'],
                "observacion" => $observacion,
                "estado" => $res['estado'],
                "fecha_registro" => $res['fecha_registro'],
                "colaboradores_id" => $colaboradores_id,
                "egresos_id" => $egresos_id,
                "categoria_gastos_id" => $categoria_gastos_id
            ];
        } else {
            $datosEgresos = [
                "proveedores_id" => $infoProveedor['proveedores_id'],
                "cuentas_id" => $res['cuentas_id'],
                "empresa_id" => $res['empresa'],
                "tipo_egreso" => $tipo_egreso,
                "fecha" => $res['fecha'],
                "factura" => $infoProveedor['factura'],
                "subtotal" => $detallesCompra['total_antes_isv'],
                "isv" => $detallesCompra['isv_neto'],
                "descuento" => $detallesCompra['descuentos'],
                "nc" => $detallesCompra['nc'],
                "total" => $detallesCompra['total_despues_isv'],
                "observacion" => $observacion,
                "estado" => $res['estado'],
                "fecha_registro" => $res['fecha_registro'],
                "colaboradores_id" => $colaboradores_id,
                "egresos_id" => $egresos_id,
                "categoria_gastos_id" => $categoria_gastos_id
            ];
        }
        
        // Validar si ya existe el egreso para evitar duplicados
        $result_valid_egresos = $this->valid_egresos_cuentas_modelo($datosEgresos);
        
        if ($result_valid_egresos->num_rows == 0) {
            $this->agregar_egresos_contabilidad_modelo($datosEgresos);
            $this->registrarMovimientoCuenta($res, $datosEgresos);
        }
        
        return true;
    }
    
    protected function registrarMovimientoCuenta($res, $datosEgresos) {
        // Consultar saldo actual
        $consulta_ingresos_contabilidad = $this->consultar_saldo_movimientos_cuentas_contabilidad($res['cuentas_id'])->fetch_assoc();
        $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) ? $consulta_ingresos_contabilidad['saldo'] : 0;
        
        // Calcular nuevo saldo
        $ingreso = 0;
        $egreso = $datosEgresos['total'];
        $saldo = $saldo_consulta - $egreso;
        
        // Registrar movimiento
        $datos_movimientos = [
            "cuentas_id" => $res['cuentas_id'],
            "empresa_id" => $res['empresa'],
            "fecha" => $res['fecha'],
            "ingreso" => $ingreso,
            "egreso" => $egreso,
            "saldo" => $saldo,
            "colaboradores_id" => isset($res["colaboradores_id"]) ? $res["colaboradores_id"] : '',
            "fecha_registro" => $res['fecha_registro'],
        ];
        
        return $this->agregar_movimientos_contabilidad_modelo($datos_movimientos);
    }
    
    protected function obtenerDetallesCompra($compras_id) {
        $resultDetallesCompras = $this->consulta_detalle_compras($compras_id);
        $datos = [
            'total_despues_isv' => 0,
            'isv_neto' => 0,
            'descuentos' => 0,
            'total_antes_isv' => 0,
            'nc' => 0
        ];
        
        while ($dataDetallesCompra = $resultDetallesCompras->fetch_assoc()) {
            $datos['total_despues_isv'] = $dataDetallesCompra['monto'];
            $datos['isv_neto'] = $dataDetallesCompra['isv_valor'];
            $datos['descuentos'] = $dataDetallesCompra['descuento'];
            $datos['total_antes_isv'] = ($datos['total_despues_isv'] - $datos['isv_neto']) - $datos['descuentos'];
        }
        
        return $datos;
    }
    
    protected function agregar_pago_detalles_compras_modelo($datos){            
        $pagoscompras_detalles_id = mainModel::correlativo("pagoscompras_detalles_id", "pagoscompras_detalles");
        $insert = "INSERT INTO pagoscompras_detalles 
            VALUES('$pagoscompras_detalles_id','".$datos['pagoscompras_id']."','".$datos['tipo_pago_id']."','".$datos['banco_id']."','".$datos['efectivo']."','".$datos['descripcion1']."','".$datos['descripcion2']."','".$datos['descripcion3']."')";
            
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $result;            
    }
    
    protected function agregar_movimientos_contabilidad_modelo($datos){
        $movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
        $insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','".$datos['cuentas_id']."','".$datos['empresa_id']."','".$datos['fecha']."','".$datos['ingreso']."','".$datos['egreso']."','".$datos['saldo']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."')";
                            
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;            
    }

    protected function agregar_egresos_contabilidad_modelo($datos){
        $insert = "
        INSERT INTO egresos 
        VALUES('".$datos['egresos_id']."','".$datos['cuentas_id']."','".$datos['proveedores_id']."',
        '".$datos['empresa_id']."','".$datos['tipo_egreso']."','".$datos['fecha']."','".$datos['factura']."',
        '".$datos['subtotal']."','".$datos['descuento']."','".$datos['nc']."','".$datos['isv']."','".$datos['total']."',
        '".$datos['observacion']."','".$datos['estado']."','".$datos['colaboradores_id']."','".$datos['fecha_registro']."','".$datos['categoria_gastos_id']."')";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;            
    }

    protected function cancelar_pago_modelo($pagoscompras_id){
        $estado = 2;//FACTURA CANCELADA
        $update = "UPDATE pagoscompras
            SET
                estado = '$estado'
            WHERE pagoscompras_id = '$pagoscompras_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;                
    }
    
    protected function consultar_codigo_pago_modelo($compras_id){
        $query = "SELECT pagoscompras_id
            FROM pagoscompras
            WHERE compras_id = '$compras_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }
    
    protected function update_status_compras($compras_id){
        $estado = 2;//FACTURA PAGADA
        $update = "UPDATE compras
            SET
                estado = '$estado'
            WHERE compras_id = '$compras_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;                    
    }
    
    protected function update_cuenta_compras($compras_id, $cuentas_id){
        $update = "UPDATE compras
            SET
                cuentas_id = '$cuentas_id'
            WHERE 
                compras_id = '$compras_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;                    
    }
    
    protected function update_status_compras_cuentas_por_pagar($compras_id, $estado = 2, $importe = ''){
        if($importe !== '' || $importe === 0){
            $importe = ', saldo = '.$importe;
        }

        $update = "UPDATE pagar_proveedores
            SET
                estado = '$estado'
                $importe
            WHERE compras_id = '$compras_id'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;                    
    }
    
    protected function consultar_compra_cuentas_por_pagar($compras_id){
        $query = "SELECT *
            FROM pagar_proveedores
            WHERE compras_id = '$compras_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }    

    protected function consultar_compra_fecha($compras_id){
        $query = "SELECT fecha
            FROM compras
            WHERE compras_id = '$compras_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }

    protected function valid_pagos_compras($compras_id){
        $query = "SELECT pagoscompras_id
            FROM pagoscompras
            WHERE compras_id = '$compras_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }
    
    protected function valid_pagos_detalles_compras($pagos_id, $tipo_pago){
        $query = "SELECT pagoscompras_detalles_id
            FROM pagoscompras_detalles
            WHERE pagoscompras_id = '$pagos_id' AND tipo_pago_id = '$tipo_pago'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }    
    
    protected function valid_egresos_cuentas_modelo($datos){
        $query = "SELECT egresos_id FROM egresos WHERE factura = '".$datos['factura']."' AND proveedores_id = '".$datos['proveedores_id']."'";

        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;            
    }

    protected function consultar_cuenta_contabilidad_tipo_pago($tipo_pago_id){
        $query = "SELECT nombre, cuentas_id
            FROM tipo_pago
            WHERE tipo_pago_id = '$tipo_pago_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }    

    protected function consultar_proveedor_id_compra($compras_id){
        $query = "SELECT proveedores_id, number AS 'factura'
            FROM compras
            WHERE compras_id = '$compras_id'";

        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }            

    protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id){
        $query = "SELECT ingreso, egreso, saldo
            FROM movimientos_cuentas
            WHERE cuentas_id = '$cuentas_id'
            ORDER BY movimientos_cuentas_id DESC LIMIT 1";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;                
    }    
    
    protected function consulta_detalle_compras($compras_id){
        $result = mainModel::getMontoTipoPagoCompras($compras_id);
        
        return $result;            
    }
}