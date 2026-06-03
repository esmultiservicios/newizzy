<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class egresosContabilidadModelo extends mainModel{
    protected function agregar_egresos_contabilidad_modelo($datos){
        $insert = "INSERT INTO egresos VALUES(
            '".$datos['egresos_id']."',
            '".$datos['cuentas_id']."',
            '".$datos['proveedores_id']."',
            '".$datos['empresa_id']."',
            '".$datos['tipo_egreso']."',
            '".$datos['fecha']."',
            '".$datos['factura']."',
            '".$datos['factura_pdf']."',
            '".$datos['subtotal']."',
            '".$datos['descuento']."',
            '".$datos['nc']."',
            '".$datos['isv']."',
            '".$datos['total']."',
            '".$datos['observacion']."',
            '".$datos['estado']."',
            '".$datos['colaboradores_id']."',
            '".$datos['fecha_registro']."',
            '".$datos['categoria_gastos']."'
        )";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;			
    }

    protected function agregar_categoria_egresos_modelo($datos){
        $insert = "INSERT INTO `categoria_gastos`(`categoria_gastos_id`, `nombre`, `estado`, `usuario`, `date_write`) VALUES ('" . $datos['categoria_gastos_id'] . "','" . $datos['nombre'] . "','" . $datos['estado'] . "','" . $datos['usuario'] . "','" . $datos['date_write'] . "')";
        
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $sql;			
    }		
    
    // 3) Ya tienes este: crea el movimiento y calcula el correlativo internamente
    protected function agregar_movimientos_contabilidad_modelo($datos){
        $movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");
        $insert = "INSERT INTO movimientos_cuentas VALUES(
            '$movimientos_cuentas_id',
            '".$datos['cuentas_id']."',
            '".$datos['empresa_id']."',
            '".$datos['fecha']."',
            '".$datos['ingreso']."',
            '".$datos['egreso']."',
            '".$datos['saldo']."',
            '".$datos['colaboradores_id']."',
            '".$datos['fecha_registro']."'
        )";
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        return $sql;
    }
    
    protected function edit_egresos_contabilidad_modelo($datos){
        $update = "UPDATE egresos
        SET
            factura = '".$datos['factura']."',
            fecha = '".$datos['fecha']."',
            observacion = '".$datos['observacion']."',
            factura_pdf = '".$datos['factura_pdf']."'                
        WHERE egresos_id = '".$datos['egresos_id']."'";
        $sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $sql;
    }

    protected function edit_categoria_egresos_contabilidad_modelo($datos){
        $update = "UPDATE categoria_gastos
        SET
            nombre = '".$datos['nombre']."'                
        WHERE categoria_gastos_id = '".$datos['categoria_gastos_id']."'";

        $sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $sql;
    }

    // 1) Anular egreso: estado y observación
    protected function cancel_egresos_contabilidad_modelo($datos){
        $update = "
            UPDATE egresos
            SET estado = '".$datos['estado']."',
                observacion = '".$datos['observacion']."'
            WHERE egresos_id = '".$datos['egresos_id']."'
        ";
        $sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        return $sql;
    }

    // 2) Insertar el INGRESO de reintegro
    protected function agregar_ingreso_por_anulacion_modelo($datos){
        // MISMO orden de columnas que tu modelo de ingresos
        $insert = "INSERT INTO ingresos VALUES(
            '".$datos['ingresos_id']."',
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
            '".$datos['recibide']."'
        )";
        $sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        return $sql;
    }
 
    // 4) Y este: trae el último saldo
    protected function consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id){
        $query = "SELECT ingreso, egreso, saldo
                FROM movimientos_cuentas
                WHERE cuentas_id = '$cuentas_id'
                ORDER BY movimientos_cuentas_id DESC
                LIMIT 1";
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        return $sql;
    }
    
    protected function delete_egresos_contabilidad_modelo($cuentas_id){
        $delete = "DELETE FROM egresos WHERE cuentas_id = '$cuentas_id'";
        
        $sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
        
        return $sql;			
    }
    
    protected function valid_egresos_cuentas_modelo($datos){
        if(is_array($datos)) {
            $query = "SELECT egresos_id FROM egresos WHERE factura = '".$datos['factura']."' AND proveedores_id = '".$datos['proveedores_id']."'";
        } else {
            // Si solo se pasa el ID
            $query = "SELECT egresos_id FROM egresos WHERE egresos_id = '$datos'";
        }
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }

    protected function valid_categoria_egresos_modelo($datos){
        $query = "SELECT categoria_gastos_id FROM categoria_gastos WHERE nombre = '".$datos['nombre']."'";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $sql;			
    }		

    protected function getTotalEgresosRegistrados() {
        try {
            $conexion = $this->connection();
            
            // Obtener el primer y último día del mes actual
            $primerDiaMes = date('Y-m-01');
            $ultimoDiaMes = date('Y-m-t');
            
            $query = "SELECT COUNT(egresos_id) AS total 
                      FROM egresos 
                      WHERE estado = 1
                      AND CAST(fecha_registro AS DATE) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'";
            
            $resultado = $conexion->query($query);
            
            if (!$resultado) {
                throw new Exception("Error al contar egresos: " . $conexion->error);
            }
            
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
            
        } catch (Exception $e) {
            error_log("Error en getTotalEgresosRegistrados: " . $e->getMessage());
            return 0;
        }
    }

    protected function get_egreso_by_id($egreso_id) {
        $conexion = mainModel::connection();
    
        $stmt = $conexion->prepare("
            SELECT e.egresos_id, e.numero_factura, e.monto, e.fecha, e.estado,
                   e.archivo_pdf, p.nombre AS proveedor
            FROM egresos_contabilidad e
            INNER JOIN proveedores p ON e.proveedor_id = p.proveedores_id
            WHERE e.egresos_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $egreso_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        return $resultado->fetch_assoc();
    }   
    
    // 5) Ya lo tienes: nombre del proveedor (usado para “recibide”)
    protected function getProveedorNombreById($proveedores_id){
        $proveedores_id = mainModel::cleanString($proveedores_id);
        $q  = "SELECT nombre FROM proveedores WHERE proveedores_id = '$proveedores_id' LIMIT 1";
        $rs = mainModel::connection()->query($q);
        if ($rs && $rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            return (string)$row['nombre'];
        }
        return '';
    }

    protected function obtener_egreso_reversion_modelo($egresos_id, $empresa_id){
        $conexion = mainModel::connection();

        $egresos_id = (int)$egresos_id;
        $empresa_id = (int)$empresa_id;

        $query = "
            SELECT
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
            FROM egresos
            WHERE egresos_id = '$egresos_id'
              AND empresa_id = '$empresa_id'
            LIMIT 1
        ";

        $sql = $conexion->query($query) or die($conexion->error);

        return $sql;
    }

    protected function validar_reversion_egreso_existente_modelo($egresos_id, $empresa_id){
        $conexion = mainModel::connection();

        $egresos_id = (int)$egresos_id;
        $empresa_id = (int)$empresa_id;

        $facturaReversion = "REV-EGR-".$egresos_id;
        $facturaReversion = $conexion->real_escape_string($facturaReversion);

        $query = "
            SELECT ingresos_id
            FROM ingresos
            WHERE empresa_id = '$empresa_id'
              AND factura = '$facturaReversion'
              AND estado = 1
            LIMIT 1
        ";

        $sql = $conexion->query($query) or die($conexion->error);

        return $sql;
    }

    protected function reversar_egreso_con_ingreso_modelo($datosIngreso, $datosMov){
        $conexion = mainModel::connection();

        $ingresos_id = (int)$datosIngreso['ingresos_id'];
        $cuentas_id = (int)$datosIngreso['cuentas_id'];
        $clientes_id = (int)$datosIngreso['clientes_id'];
        $empresa_id = (int)$datosIngreso['empresa_id'];
        $tipo_ingreso = (int)$datosIngreso['tipo_ingreso'];
        $fecha = $conexion->real_escape_string($datosIngreso['fecha']);
        $factura = $conexion->real_escape_string($datosIngreso['factura']);
        $subtotal = (float)$datosIngreso['subtotal'];
        $descuento = (float)$datosIngreso['descuento'];
        $nc = (float)$datosIngreso['nc'];
        $isv = (float)$datosIngreso['isv'];
        $total = (float)$datosIngreso['total'];
        $observacion = $conexion->real_escape_string($datosIngreso['observacion']);
        $estado = (int)$datosIngreso['estado'];
        $colaboradores_id = (int)$datosIngreso['colaboradores_id'];
        $fecha_registro = $conexion->real_escape_string($datosIngreso['fecha_registro']);

        $queryInsertIngreso = "
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
            ) VALUES (
                '$ingresos_id',
                '$cuentas_id',
                '$clientes_id',
                '$empresa_id',
                '$tipo_ingreso',
                '$fecha',
                '$factura',
                '$subtotal',
                '$descuento',
                '$nc',
                '$isv',
                '$total',
                '$observacion',
                '$estado',
                '$colaboradores_id',
                '$fecha_registro'
            )
        ";

        $resultInsertIngreso = $conexion->query($queryInsertIngreso);

        if(!$resultInsertIngreso){
            return [
                "success" => false,
                "message" => "No se pudo registrar el ingreso de reversión: ".$conexion->error
            ];
        }

        $movimientos_cuentas_id = mainModel::correlativo("movimientos_cuentas_id", "movimientos_cuentas");

        $mov_cuentas_id = (int)$datosMov['cuentas_id'];
        $mov_empresa_id = (int)$datosMov['empresa_id'];
        $mov_fecha = $conexion->real_escape_string($datosMov['fecha']);
        $mov_ingreso = (float)$datosMov['ingreso'];
        $mov_egreso = (float)$datosMov['egreso'];
        $mov_saldo = (float)$datosMov['saldo'];
        $mov_colaboradores_id = (int)$datosMov['colaboradores_id'];
        $mov_fecha_registro = $conexion->real_escape_string($datosMov['fecha_registro']);

        $queryInsertMovimiento = "
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
            ) VALUES (
                '$movimientos_cuentas_id',
                '$mov_cuentas_id',
                '$mov_empresa_id',
                '$mov_fecha',
                '$mov_ingreso',
                '$mov_egreso',
                '$mov_saldo',
                '$mov_colaboradores_id',
                '$mov_fecha_registro'
            )
        ";

        $resultInsertMovimiento = $conexion->query($queryInsertMovimiento);

        if(!$resultInsertMovimiento){
            return [
                "success" => false,
                "message" => "El ingreso fue registrado, pero no se pudo registrar el movimiento de cuenta: ".$conexion->error
            ];
        }

        return [
            "success" => true,
            "ingresos_id" => $ingresos_id,
            "movimientos_cuentas_id" => $movimientos_cuentas_id
        ];
    }

}