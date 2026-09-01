<?php
//facturasModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class facturasModelo extends mainModel{		
    protected function guardar_facturas_modelo($datos) {
        $conexion = mainModel::connection();

        $facturas_id = (int)$datos['facturas_id'];
        $empresa_id = (int)$datos['empresa'];
        $secuencia_facturacion_id = (int)$datos['secuencia_facturacion_id'];
        $numero = (int)$datos['numero'];

        // Seguridad: no permitir duplicar number dentro de la misma empresa/secuencia.
        $stmtDuplicado = $conexion->prepare(
            "SELECT facturas_id
             FROM facturas
             WHERE empresa_id = ?
               AND secuencia_facturacion_id = ?
               AND number = ?
               AND facturas_id <> ?
             LIMIT 1"
        );

        if($stmtDuplicado){
            $stmtDuplicado->bind_param("iiii", $empresa_id, $secuencia_facturacion_id, $numero, $facturas_id);
            $stmtDuplicado->execute();
            $resDuplicado = $stmtDuplicado->get_result();

            if($resDuplicado && $resDuplicado->num_rows > 0){
                $stmtDuplicado->close();
                error_log("Duplicado bloqueado en facturas: empresa={$empresa_id}, secuencia={$secuencia_facturacion_id}, numero={$numero}, facturas_id={$facturas_id}");
                return false;
            }

            $stmtDuplicado->close();
        }

        // Verificar si ya existe un registro con el mismo facturas_id
        $check = "SELECT COUNT(*) as count FROM facturas 
                  WHERE facturas_id = '".$facturas_id."'";
        $result_check = $conexion->query($check) or die($conexion->error);
        $row = $result_check->fetch_assoc();

        if ($row['count'] > 0) {
            // Si existe, realizar un UPDATE
            $query = "UPDATE facturas SET
                        `clientes_id` = '".$datos['clientes_id']."',
                        `secuencia_facturacion_id` = '".$datos['secuencia_facturacion_id']."',
                        `apertura_id` = '".$datos['apertura_id']."',
                        `number` = '".$datos['numero']."',
                        `tipo_factura` = '".$datos['tipo_factura']."',
                        `colaboradores_id` = '".$datos['colaboradores_id']."',
                        `importe` = '".$datos['importe']."',
                        `notas` = '".$datos['notas']."',
                        `fecha` = '".$datos['fecha']."',
                        `estado` = '".$datos['estado']."',
                        `usuario` = '".$datos['usuario']."',
                        `empresa_id` = '".$datos['empresa']."',
                        `fecha_registro` = '".$datos['fecha_registro']."',
                        `fecha_dolar` = '".$datos['fecha_dolar']."'
                    WHERE `facturas_id` = '".$datos['facturas_id']."'";
        } else {
            // Si no existe, realizar un INSERT
            $query = "INSERT INTO facturas (
                        `facturas_id`, 
                        `clientes_id`, 
                        `secuencia_facturacion_id`, 
                        `apertura_id`, 
                        `number`, 
                        `tipo_factura`, 
                        `colaboradores_id`, 
                        `importe`, 
                        `notas`, 
                        `fecha`, 
                        `estado`, 
                        `usuario`, 
                        `empresa_id`, 
                        `fecha_registro`, 
                        `fecha_dolar`,
                        `no_orden`,
                        `constancia`,
                        `identificativo_sag`,
                        `numero_interno`                        
                    )
                    VALUES (
                        '".$datos['facturas_id']."',
                        '".$datos['clientes_id']."',
                        '".$datos['secuencia_facturacion_id']."',
                        '".$datos['apertura_id']."',
                        '".$datos['numero']."',
                        '".$datos['tipo_factura']."',
                        '".$datos['colaboradores_id']."',
                        '".$datos['importe']."',
                        '".$datos['notas']."',
                        '".$datos['fecha']."',
                        '".$datos['estado']."',
                        '".$datos['usuario']."',
                        '".$datos['empresa']."',
                        '".$datos['fecha_registro']."',
                        '".$datos['fecha_dolar']."',
                        '".($datos['exoneracion_orden'] ?? '')."',
                        '".($datos['exoneracion_constancia'] ?? '')."',
                        '".($datos['exoneracion_sag'] ?? '')."',
                        '".($datos['exoneracion_orden_interno'] ?? '')."'	                        
                    )";
        }

        $result = $conexion->query($query) or die($conexion->error);

        return $result ? true : false;
    }
    
    /* ===========================
    * DETALLE: INSERT/UPDATE con isv_valor1
    * =========================== */
    protected function agregar_detalle_facturas_modelo($datos) {
        $check = "SELECT COUNT(*) as count FROM facturas_detalles 
                WHERE facturas_id = '".$datos['facturas_id']."' 
                AND productos_id = '".$datos['productos_id']."'";
        $result_check = mainModel::connection()->query($check) or die(mainModel::connection()->error);
        $row = $result_check->fetch_assoc();

        if ($row['count'] > 0) {
            $update = "UPDATE facturas_detalles SET
                        `cantidad`   = '".$datos['cantidad']."',
                        `precio`     = '".$datos['precio']."',
                        `isv_valor`  = '".$datos['isv_valor']."',
                        `isv_valor1` = '".$datos['isv_valor1']."',
                        `descuento`  = '".$datos['descuento']."',
                        `medida`     = '".$datos['medida']."'
                    WHERE `facturas_id` = '".$datos['facturas_id']."' 
                    AND `productos_id` = '".$datos['productos_id']."'";
            $result = mainModel::connection()->query($update);
        } else {
            $facturas_detalle_id = mainModel::correlativo("facturas_detalle_id", "facturas_detalles");
            $insert = "INSERT INTO facturas_detalles (
                            `facturas_detalle_id`, 
                            `facturas_id`, 
                            `productos_id`, 
                            `cantidad`, 
                            `precio`, 
                            `costo_unitario`,
                            `isv_valor`,
                            `isv_valor1`,
                            `descuento`, 
                            `medida`
                        )
                        VALUES (
                            '$facturas_detalle_id',
                            '".$datos['facturas_id']."',
                            '".$datos['productos_id']."',
                            '".$datos['cantidad']."',
                            '".$datos['precio']."',
                            '".$datos['costo_unitario']."',
                            '".$datos['isv_valor']."',
                            '".$datos['isv_valor1']."',
                            '".$datos['descuento']."',
                            '".$datos['medida']."'
                        )";
            $result = mainModel::connection()->query($insert);
        }
        return $result ? true : false;
    } 
    
    protected function agregar_cambio_dolar_modelo($datos){
        $insert = "INSERT INTO cambio_dolar 
            VALUES('".$datos['cambio_dolar_id']."','".$datos['compra']."','".$datos['venta']."','".$datos['tipo']."','".$datos['fecha_registro']."')";

        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);        

        return $result;            
    }

    protected function agregar_movimientos_productos_modelo($datos){
        $movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
        $insert = "INSERT INTO movimientos (
                        `movimientos_id`, 
                        `productos_id`, 
                        `documento`, 
                        `cantidad_entrada`, 
                        `cantidad_salida`, 
                        `saldo`, 
                        `empresa_id`, 
                        `fecha_registro`, 
                        `clientes_id`, 
                        `comentario`, 
                        `almacen_id`,
                        `lote_id`
                    )
                    VALUES (
                        '$movimientos_id',
                        '".$datos['productos_id']."',
                        '".$datos['documento']."',
                        '".$datos['cantidad_entrada']."',
                        '".$datos['cantidad_salida']."',
                        '".$datos['saldo']."',
                        '".$datos['empresa']."',
                        '".$datos['fecha_registro']."',
                        '".$datos['clientes_id']."',
                        '',
                        '".$datos['almacen_id']."',
                        '".$datos['lote_id']."'  
                    )";
    
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);    
        return $result;                
    }        
    
    protected function agregar_cuenta_por_cobrar_clientes($datos){
        $cobrar_clientes_id = mainModel::correlativo("cobrar_clientes_id", "cobrar_clientes");
        $insert = "INSERT INTO cobrar_clientes (
                        `cobrar_clientes_id`, 
                        `clientes_id`, 
                        `facturas_id`, 
                        `fecha`, 
                        `saldo`, 
                        `estado`, 
                        `tipo_factura`,
                        `usuario`, 
                        `empresa_id`, 
                        `fecha_registro`
                    )
                    VALUES (
                        '$cobrar_clientes_id',
                        '".$datos['clientes_id']."',
                        '".$datos['facturas_id']."',
                        '".$datos['fecha']."',
                        '".$datos['saldo']."',
                        '".$datos['estado']."',
                        '".$datos['tipo_factura']."',
                        '".$datos['usuario']."',
                        '".$datos['empresa']."',
                        '".$datos['fecha_registro']."'
                    )";
    
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        return $result;                
    }

    protected function agregar_precio_factura_clientes($datos){
        $precio_factura_id = mainModel::correlativo("precio_factura_id", "precio_factura");
        $insert = "INSERT INTO precio_factura (
                        `precio_factura_id`, 
                        `facturas_id`, 
                        `productos_id`, 
                        `clientes_id`, 
                        `fecha`, 
                        `referencia`, 
                        `precio_anterior`, 
                        `precio_nuevo`, 
                        `fecha_registro`
                    )
                    VALUES (
                        '$precio_factura_id',
                        '".$datos['facturas_id']."',
                        '".$datos['productos_id']."',
                        '".$datos['clientes_id']."',
                        '".$datos['fecha']."',
                        '".$datos['referencia']."',
                        '".$datos['precio_anterior']."',
                        '".$datos['precio_nuevo']."',
                        '".$datos['fecha_registro']."'
                    )";
        
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        return $result;                
    } 
    
    protected function agregar_facturas_proforma_modelo($datos){
        $conexion = mainModel::connection();

        $facturas_id = (int)$datos['facturas_id'];
        $clientes_id = (int)$datos['clientes_id'];
        $secuencia_facturacion_id = (int)$datos['secuencia_facturacion_id'];
        $numero = (int)$datos['numero'];
        $importe = (float)$datos['importe'];
        $usuario = (int)$datos['usuario'];
        $empresa_id = (int)$datos['empresa_id'];
        $estado = (int)$datos['estado'];
        $fecha_creacion = (string)$datos['fecha_creacion'];

        // Evita duplicar el registro si una proforma viene desde borrador
        // o si se vuelve a guardar el mismo encabezado.
        $check = $conexion->prepare("SELECT facturas_proforma_id FROM facturas_proforma WHERE facturas_id = ? LIMIT 1");

        if (!$check) {
            error_log("Error al preparar validación facturas_proforma: " . $conexion->error);
            return false;
        }

        $check->bind_param("i", $facturas_id);
        $check->execute();
        $resultCheck = $check->get_result();
        $existe = ($resultCheck && $resultCheck->num_rows > 0);
        $check->close();

        if ($existe) {
            $update = "UPDATE facturas_proforma
                       SET clientes_id = ?,
                           secuencia_facturacion_id = ?,
                           numero = ?,
                           importe = ?,
                           usuario = ?,
                           empresa_id = ?,
                           estado = ?
                       WHERE facturas_id = ?";

            $stmt = $conexion->prepare($update);

            if (!$stmt) {
                error_log("Error al preparar UPDATE facturas_proforma: " . $conexion->error);
                return false;
            }

            $stmt->bind_param(
                "iiidiiii",
                $clientes_id,
                $secuencia_facturacion_id,
                $numero,
                $importe,
                $usuario,
                $empresa_id,
                $estado,
                $facturas_id
            );
        } else {
            $facturas_proforma_id = mainModel::correlativo("facturas_proforma_id", "facturas_proforma");

            $insert = "INSERT INTO facturas_proforma (
                            facturas_proforma_id,
                            facturas_id,
                            clientes_id,
                            secuencia_facturacion_id,
                            numero,
                            importe,
                            usuario,
                            empresa_id,
                            estado,
                            fecha_creacion
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conexion->prepare($insert);

            if (!$stmt) {
                error_log("Error al preparar INSERT facturas_proforma: " . $conexion->error);
                return false;
            }

            $stmt->bind_param(
                "iiiiidiiis",
                $facturas_proforma_id,
                $facturas_id,
                $clientes_id,
                $secuencia_facturacion_id,
                $numero,
                $importe,
                $usuario,
                $empresa_id,
                $estado,
                $fecha_creacion
            );
        }

        $result = $stmt->execute();

        if (!$result) {
            error_log("Error al guardar facturas_proforma: " . $stmt->error);
        }

        $stmt->close();

        return $result ? true : false;
    }

    protected function actualizar_detalle_facturas($datos){
        $update = "UPDATE facturas_detalles
                    SET 
                        cantidad   = '".$datos['cantidad']."',
                        precio     = '".$datos['precio']."',
                        costo_unitario = '".$datos['costo_unitario']."',
                        isv_valor  = '".$datos['isv_valor']."',
                        isv_valor1 = '".$datos['isv_valor1']."',
                        descuento  = '".$datos['descuento']."',
                        medida     = '".$datos['medida']."'
                    WHERE facturas_id = '".$datos['facturas_id']."' 
                      AND productos_id = '".$datos['productos_id']."'";        
    
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);        
        return $result;                    
    }
    
    protected function actualizar_factura_importe($datos){
        $update = "UPDATE facturas
                    SET
                        importe = '".$datos['importe']."'
                    WHERE facturas_id = '".$datos['facturas_id']."'";
    
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        return $result;                
    }

    protected function actualizar_estado_factura_modelo($facturas_id){
        $update = "UPDATE facturas
            SET
                estado = '2'
            WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);    

        return $result;                
    }            
                    
    public static function bloquear_y_obtener_secuencia_modelo($empresa_id, $documento_id, $conexion = null) {
        $conexionLocal = false;
        
        try {
            // Si no se proporciona conexión, crear una nueva
            if($conexion === null) {
                $conexion = mainModel::staticConnection();
                $conexionLocal = true;
            }
            
            // Establecer tiempo de espera para el bloqueo (5 segundos)
            $conexion->query("SET innodb_lock_wait_timeout = 5");
            
            // Bloquear la fila para lectura (FOR UPDATE)
            $sql = "SELECT * FROM secuencia_facturacion 
                    WHERE empresa_id = ? 
                    AND documento_id = ? 
                    AND activo = 1
                    LIMIT 1
                    FOR UPDATE";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $empresa_id, $documento_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows == 0) {
                $stmt->close();
                return false;
            }
            
            $secuencia = $result->fetch_assoc();
            $stmt->close();
            
            return $secuencia;
        } catch (Exception $e) {
            error_log("Error en secuencia facturación: " . $e->getMessage());
            return false;
        }
    }
    
    protected function cancelar_facturas_modelo($facturas_id){
        $estado = 4; //FACTURA CANCELADA
        $update = "UPDATE facturas
                    SET
                        estado = '$estado'
                    WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
    
        return $result;            
    }

    protected function secuencia_facturacion_modelo($empresa_id, $documento_id) {
        // Consulta SQL para obtener la secuencia de facturación
        $query = "
            SELECT 
                secuencia_facturacion_id, 
                prefijo, 
                siguiente AS 'numero', 
                rango_final, 
                fecha_limite, 
                incremento, 
                relleno
            FROM 
                secuencia_facturacion
            WHERE 
                activo = '1' 
                AND empresa_id = '$empresa_id' 
                AND documento_id = '$documento_id'
        ";

        // Ejecuta la consulta y maneja errores
        $result = mainModel::connection()->query($query) 
            or die(mainModel::connection()->error);

        return $result;
    }
    
    protected function validDetalleFactura($facturas_id, $productos_id){
        $query = "SELECT facturas_id
                FROM facturas_detalles
                WHERE facturas_id = '$facturas_id' AND productos_id  = '$productos_id'";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }

    protected function validar_cobrarClientes_modelo($facturas_id){
        $query = "SELECT cobrar_clientes_id
                FROM cobrar_clientes
                WHERE facturas_id = '$facturas_id'";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }        
    
    protected function valid_cambio_dolar_modelo($fecha){
        $query = "SELECT cambio_dolar_id
                FROM cambio_dolar
                WHERE CAST(fecha_registro AS DATE) = '$fecha'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }  

    protected function valid_cambio_dolar_tipo2_modelo($fecha){
        $query = "SELECT cambio_dolar_id
                    FROM cambio_dolar
                    WHERE CAST(fecha_registro AS DATE) = '$fecha' AND tipo = 2";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);        
        
        return $result;                
    }                

    protected function valid_precio_factura_modelo($datos){
        $query = "SELECT precio_factura_id
                    FROM precio_factura
                    WHERE facturas_id = '".$datos['facturas_id']."'";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }    

    protected function saldo_productos_movimientos_modelo($productos_id){
        $result = mainModel::getSaldoProductosMovimientos($productos_id);
        
        return $result;            
    }
    
    protected function getISV_modelo(){
        $result = mainModel::getISV('Facturas');
        
        return $result;
    }
    
    protected function getISVEstadoProducto_modelo($productos_id){
        $result = mainModel::getISVEstadoProducto($productos_id);        
    
        return $result;            
    }
    
    protected function tipo_producto_modelo($productos_id){
        $result = mainModel::getTipoProducto($productos_id);        
    
        return $result;            
    }      

    protected function getMedidaProducto($productos_id){
        $query = "SELECT
                    productos.productos_id,
                    medida.nombre AS medida,
                    medida.medida_id,
                    medida.estado
                FROM
                    medida
                    INNER JOIN productos ON medida.medida_id = productos.medida_id    
                WHERE productos.productos_id = '".$productos_id."'
                AND medida.estado = 1";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;                
    }

    protected function cantidad_producto_modelo($productos_id){
        $result = mainModel::getCantidadProductos($productos_id);
        
        return $result;            
    }    

    protected function getAperturaIDModelo($datos){
        $query = "SELECT apertura_id
                    FROM apertura
                    WHERE colaboradores_id = '".$datos['colaboradores_id']."' AND fecha = '".$datos['fecha']."' AND estado = '".$datos['estado']."'";            
                
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;            
    }

    protected function total_hijos_segun_padre_modelo($productos_id){
        $result = mainModel::getTotalHijosporPadre($productos_id);
        
        return $result;            
    }
    
    protected function obtener_lote_para_salida($producto_id, $cantidad_salida) {
        // Seleccionar los lotes disponibles para el producto (por ejemplo, con estado 'Activo')
        $query = mainModel::connection()->query("SELECT lote_id, cantidad, fecha_vencimiento 
                                                 FROM lotes 
                                                 WHERE productos_id = '$producto_id' AND cantidad > 0 AND estado = 'Activo' 
                                                 ORDER BY fecha_vencimiento ASC"); // FIFO
    
        $lote_id = 0;
        $cantidad_restante = $cantidad_salida;
        
        while ($row = $query->fetch_assoc()) {
            if ($row['cantidad'] >= $cantidad_restante) {
                // Si el lote tiene suficiente cantidad, asignamos el lote
                $lote_id = $row['lote_id'];
                // Actualizamos la cantidad del lote
                $nueva_cantidad = $row['cantidad'] - $cantidad_restante;
                mainModel::connection()->query("UPDATE lotes SET cantidad = $nueva_cantidad WHERE lote_id = '$lote_id'");
                break;
            } else {
                // Si el lote no tiene suficiente cantidad, reducimos la cantidad restante
                $cantidad_restante -= $row['cantidad'];
                $lote_id = $row['lote_id'];
                // Ponemos la cantidad del lote a 0 ya que se consumió todo
                mainModel::connection()->query("UPDATE lotes SET cantidad = 0 WHERE lote_id = '$lote_id'");
            }
        }
        
        return $lote_id; // Retornamos el ID del lote seleccionado
    }    
    
    public function saldo_productos_por_lote_modelo($producto_id, $lote_id) {
        // Obtenemos la conexión a la base de datos
        $conexion = mainModel::connection();
    
        // Consulta SQL para obtener el saldo del producto en el lote específico
        $query = "SELECT saldo 
                  FROM movimientos 
                  WHERE productos_id = ? AND lote_id = ? 
                  ORDER BY fecha_registro DESC LIMIT 1"; // FIFO (First In, First Out)
    
        // Preparamos la consulta
        $stmt = $conexion->prepare($query);
        
        // Vinculamos los parámetros (producto_id y lote_id)
        $stmt->bind_param("ii", $producto_id, $lote_id);
        
        // Ejecutamos la consulta
        $stmt->execute();
        
        // Obtenemos el resultado
        $result = $stmt->get_result();
        
        // Verificamos si existe un saldo para este producto en el lote especificado
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); // Devolvemos el saldo si existe
        } else {
            return null; // Si no se encuentra el saldo, devolvemos null
        }
    }

    /* ===========================
    * COMBOS: CONSULTAS DE INVENTARIO
    * =========================== */
    protected function tabla_combo_facturacion_existe_modelo($tabla) {
        static $cache = [];

        $tabla = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$tabla);

        if ($tabla === '') {
            return false;
        }

        if (array_key_exists($tabla, $cache)) {
            return $cache[$tabla];
        }

        $cn = mainModel::connection();

        if (!$cn) {
            $cache[$tabla] = false;
            return false;
        }

        $sql = "SELECT 1
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME = ?
                LIMIT 1";

        $stmt = $cn->prepare($sql);

        if (!$stmt) {
            $cache[$tabla] = false;
            return false;
        }

        $stmt->bind_param("s", $tabla);
        $stmt->execute();
        $rs = $stmt->get_result();

        $cache[$tabla] = ($rs && $rs->num_rows > 0);
        $stmt->close();

        return $cache[$tabla];
    }

    protected function obtener_combo_activo_producto_modelo($productos_id, $empresa_id) {
        if (
            !$this->tabla_combo_facturacion_existe_modelo('combos') ||
            !$this->tabla_combo_facturacion_existe_modelo('combo_detalle')
        ) {
            return null;
        }

        $productos_id = (int)$productos_id;
        $empresa_id = (int)$empresa_id;

        if ($productos_id <= 0 || $empresa_id <= 0) {
            return null;
        }

        $cn = mainModel::connection();

        $sql = "SELECT c.combo_id,
                       c.productos_id,
                       c.version_actual
                FROM combos c
                INNER JOIN productos p
                    ON p.productos_id = c.productos_id
                WHERE c.productos_id = ?
                  AND c.activo = 1
                  AND p.empresa_id = ?
                LIMIT 1";

        $stmt = $cn->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("ii", $productos_id, $empresa_id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $row = ($rs && $rs->num_rows > 0) ? $rs->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    protected function obtener_componentes_combo_inventario_modelo($combo_id, $version, $empresa_id) {
        if (
            !$this->tabla_combo_facturacion_existe_modelo('combos') ||
            !$this->tabla_combo_facturacion_existe_modelo('combo_detalle')
        ) {
            return [];
        }

        $combo_id = (int)$combo_id;
        $version = (int)$version;
        $empresa_id = (int)$empresa_id;

        if ($combo_id <= 0 || $empresa_id <= 0) {
            return [];
        }

        $cn = mainModel::connection();

        $sql = "SELECT d.combo_detalle_id,
                       d.productos_id,
                       d.cantidad_por_porcion,
                       d.unidad,
                       d.merma_pct,
                       d.obligatorio,
                       d.version,
                       d.orden,
                       p.nombre,
                       p.almacen_id,
                       p.empresa_id
                FROM combo_detalle d
                INNER JOIN productos p
                    ON p.productos_id = d.productos_id
                WHERE d.combo_id = ?
                  AND d.obligatorio = 1
                  AND p.empresa_id = ?";

        $types = "ii";
        $params = [$combo_id, $empresa_id];

        if ($version > 0) {
            $sql .= " AND d.version = ?";
            $types .= "i";
            $params[] = $version;
        }

        $sql .= " ORDER BY d.orden ASC, d.combo_detalle_id ASC";

        $stmt = $cn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rs = $stmt->get_result();

        $items = [];

        if ($rs) {
            while ($row = $rs->fetch_assoc()) {
                $items[] = $row;
            }
        }

        $stmt->close();

        return $items;
    }

    protected function obtener_politica_almacen_modelo($almacen_id, $empresa_id) {
        $almacen_id = (int)$almacen_id;
        $empresa_id = (int)$empresa_id;

        if ($almacen_id <= 0 || $empresa_id <= 0) {
            return ['existe'=>false, 'facturar_cero'=>0, 'nombre'=>''];
        }

        $cn = mainModel::connection();
        $stmt = $cn->prepare(
            "SELECT nombre, facturar_cero
             FROM almacen
             WHERE almacen_id = ? AND empresa_id = ? AND estado = 1
             LIMIT 1"
        );

        if (!$stmt) {
            return ['existe'=>false, 'facturar_cero'=>0, 'nombre'=>''];
        }

        $stmt->bind_param("ii", $almacen_id, $empresa_id);
        $stmt->execute();
        $rs = $stmt->get_result();
        $row = ($rs && $rs->num_rows > 0) ? $rs->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return ['existe'=>false, 'facturar_cero'=>0, 'nombre'=>''];
        }

        return [
            'existe'=>true,
            'facturar_cero'=>((int)($row['facturar_cero'] ?? 0) === 1 ? 1 : 0),
            'nombre'=>(string)($row['nombre'] ?? '')
        ];
    }

    protected function obtener_saldo_vendible_producto_modelo($productos_id, $empresa_id, $almacen_id) {
        $productos_id=(int)$productos_id; $empresa_id=(int)$empresa_id; $almacen_id=(int)$almacen_id;
        if($productos_id<=0 || $empresa_id<=0 || $almacen_id<=0) return 0.0;
        $cn=mainModel::connection();
        $stmt=$cn->prepare("SELECT COALESCE(SUM(cantidad_entrada),0)-COALESCE(SUM(cantidad_salida),0) saldo FROM movimientos WHERE productos_id=? AND empresa_id=? AND almacen_id=? AND lote_id=0");
        if(!$stmt) return 0.0;
        $stmt->bind_param('iii',$productos_id,$empresa_id,$almacen_id); $stmt->execute(); $rs=$stmt->get_result();
        $sinLote=($rs&&$rs->num_rows)?(float)($rs->fetch_assoc()['saldo']??0):0.0; $stmt->close();
        $stmt=$cn->prepare("SELECT COALESCE(SUM(cantidad),0) saldo FROM lotes WHERE productos_id=? AND empresa_id=? AND almacen_id=? AND estado='Activo' AND cantidad>0 AND (fecha_vencimiento IS NULL OR fecha_vencimiento>=CURDATE())");
        if(!$stmt) return $sinLote;
        $stmt->bind_param('iii',$productos_id,$empresa_id,$almacen_id); $stmt->execute(); $rs=$stmt->get_result();
        $lotes=($rs&&$rs->num_rows)?(float)($rs->fetch_assoc()['saldo']??0):0.0; $stmt->close();
        return $sinLote+$lotes;
    }

    protected function obtener_saldo_inventario_producto_modelo($productos_id, $empresa_id, $almacen_id = 0) {
        $productos_id = (int)$productos_id;
        $empresa_id = (int)$empresa_id;
        $almacen_id = (int)$almacen_id;

        if ($productos_id <= 0 || $empresa_id <= 0) {
            return 0.0;
        }

        $cn = mainModel::connection();

        $sql = "SELECT COALESCE(SUM(cantidad_entrada), 0)
                     - COALESCE(SUM(cantidad_salida), 0) AS saldo
                FROM movimientos
                WHERE productos_id = ?
                  AND empresa_id = ?";

        if ($almacen_id > 0) {
            $sql .= " AND almacen_id = ?";
            $stmt = $cn->prepare($sql);

            if (!$stmt) {
                return 0.0;
            }

            $stmt->bind_param("iii", $productos_id, $empresa_id, $almacen_id);
        } else {
            $stmt = $cn->prepare($sql);

            if (!$stmt) {
                return 0.0;
            }

            $stmt->bind_param("ii", $productos_id, $empresa_id);
        }

        $stmt->execute();
        $rs = $stmt->get_result();
        $saldo = 0.0;

        if ($rs && $rs->num_rows > 0) {
            $row = $rs->fetch_assoc();
            $saldo = (float)($row['saldo'] ?? 0);
        }

        $stmt->close();

        return $saldo;
    }

    /* ===========================
    * INVENTARIO: ARREGLA bind_param (con/sin lote)
    * =========================== */
    protected function registrar_salida_lote_modelo($datos) {
        $mysqli = mainModel::connection();

        $producto_id = (int)($datos['productos_id'] ?? 0);
        $empresa_id = (int)($datos['empresa_id'] ?? $datos['empresa'] ?? 0);
        $clientes_id = (int)($datos['clientes_id'] ?? 0);
        $documento = (string)($datos['documento'] ?? '');
        $comentario = (string)($datos['comentario'] ?? 'Salida de inventario por venta');
        $cantidad_solicitada = (float)($datos['cantidad'] ?? 0);
        $almacen_solicitado = (int)($datos['almacen_id'] ?? 0);

        if($producto_id <= 0 || $empresa_id <= 0 || $cantidad_solicitada <= 0){
            return ['status'=>'error','message'=>'Datos inválidos para rebajar inventario'];
        }

        if($almacen_solicitado <= 0){
            $almacen_solicitado = (int)$this->getAlmacenProducto($producto_id);
        }

        $politica = $this->obtener_politica_almacen_modelo($almacen_solicitado, $empresa_id);
        if(empty($politica['existe'])){
            return ['status'=>'error','message'=>'El almacén seleccionado no existe, está inactivo o no pertenece a la empresa.'];
        }
        $permiteNegativo = !empty($politica['facturar_cero']);

        $nombre_producto = 'Producto ID '.$producto_id;
        $stmtNombre = $mysqli->prepare('SELECT nombre FROM productos WHERE productos_id=? AND empresa_id=? LIMIT 1');
        if($stmtNombre){
            $stmtNombre->bind_param('ii',$producto_id,$empresa_id);
            $stmtNombre->execute();
            $resNombre=$stmtNombre->get_result();
            if($resNombre && $resNombre->num_rows){
                $nombre_producto=(string)$resNombre->fetch_assoc()['nombre'];
            }
            $stmtNombre->close();
        }

        $sqlSaldo = "SELECT COALESCE(SUM(cantidad_entrada),0)-COALESCE(SUM(cantidad_salida),0) AS saldo
                     FROM movimientos
                     WHERE productos_id=? AND empresa_id=? AND almacen_id=?";
        $stmtSaldo=$mysqli->prepare($sqlSaldo);
        if(!$stmtSaldo){
            return ['status'=>'error','message'=>'No se pudo consultar el inventario del producto.'];
        }
        $stmtSaldo->bind_param('iii',$producto_id,$empresa_id,$almacen_solicitado);
        $stmtSaldo->execute();
        $rsSaldo=$stmtSaldo->get_result();
        $saldoInicial=0.0;
        if($rsSaldo && $rsSaldo->num_rows){
            $saldoInicial=(float)($rsSaldo->fetch_assoc()['saldo'] ?? 0);
        }
        $stmtSaldo->close();

        $saldoVendible=$this->obtener_saldo_vendible_producto_modelo($producto_id,$empresa_id,$almacen_solicitado);
        if(!$permiteNegativo && ($saldoVendible + 0.000001) < $cantidad_solicitada){
            return [
                'status'=>'error',
                'message'=>'Saldo vendible insuficiente para '.$nombre_producto.' en '.$politica['nombre'].'. Disponible: '.number_format($saldoVendible,4,'.','').', solicitado: '.number_format($cantidad_solicitada,4,'.','')
            ];
        }

        $restante=$cantidad_solicitada;
        $consumos=[];
        $saldoOperacion=$saldoInicial;

        $sqlLotes="SELECT lote_id, numero_lote, cantidad, almacen_id, fecha_vencimiento, fecha_ingreso
                   FROM lotes
                   WHERE productos_id=? AND empresa_id=? AND almacen_id=?
                     AND estado='Activo' AND cantidad>0
                     AND (fecha_vencimiento IS NULL OR fecha_vencimiento>=CURDATE())
                   ORDER BY
                     CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END ASC,
                     fecha_vencimiento ASC,
                     fecha_ingreso ASC,
                     lote_id ASC";
        $stmt=$mysqli->prepare($sqlLotes);
        if($stmt){
            $stmt->bind_param('iii',$producto_id,$empresa_id,$almacen_solicitado);
            if($stmt->execute()){
                $rs=$stmt->get_result();
                while($restante>0 && $rs && ($lote=$rs->fetch_assoc())){
                    $enLote=(float)$lote['cantidad'];
                    if($enLote<=0) continue;
                    $usar=min($enLote,$restante);
                    $nuevoLote=$enLote-$usar;
                    $saldoOperacion-=$usar;

                    $stmtMov=$mysqli->prepare("INSERT INTO movimientos
                        (productos_id,cantidad_entrada,cantidad_salida,saldo,empresa_id,fecha_registro,almacen_id,lote_id,clientes_id,documento,comentario)
                        VALUES (?,0,?,?,?,NOW(),?,?,?,?,?)");
                    if(!$stmtMov){ $stmt->close(); return ['status'=>'error','message'=>'No se pudo preparar el movimiento de inventario.']; }
                    $loteId=(int)$lote['lote_id'];
                    $stmtMov->bind_param('iddiiiiss',$producto_id,$usar,$saldoOperacion,$empresa_id,$almacen_solicitado,$loteId,$clientes_id,$documento,$comentario);
                    if(!$stmtMov->execute()){
                        $msg=$stmtMov->error; $stmtMov->close(); $stmt->close();
                        return ['status'=>'error','message'=>'No se pudo registrar la salida de inventario: '.$msg];
                    }
                    $stmtMov->close();

                    $estadoLote=$nuevoLote<=0?'Inactivo':'Activo';
                    $up=$mysqli->prepare('UPDATE lotes SET cantidad=?, estado=? WHERE lote_id=?');
                    $up->bind_param('dsi',$nuevoLote,$estadoLote,$loteId);
                    $up->execute(); $up->close();

                    $consumos[]=['lote_id'=>$loteId,'numero_lote'=>$lote['numero_lote'],'usado'=>$usar,'almacen_id'=>$almacen_solicitado,'saldo_final'=>$saldoOperacion];
                    $restante-=$usar;
                }
            }
            $stmt->close();
        }

        if($restante>0){
            if(!$permiteNegativo){
                $stmtNoLote=$mysqli->prepare("SELECT COALESCE(SUM(cantidad_entrada),0)-COALESCE(SUM(cantidad_salida),0) saldo FROM movimientos WHERE productos_id=? AND empresa_id=? AND almacen_id=? AND lote_id=0");
                $saldoSinLote=0.0;
                if($stmtNoLote){
                    $stmtNoLote->bind_param('iii',$producto_id,$empresa_id,$almacen_solicitado); $stmtNoLote->execute(); $rsNoLote=$stmtNoLote->get_result();
                    if($rsNoLote&&$rsNoLote->num_rows)$saldoSinLote=(float)($rsNoLote->fetch_assoc()['saldo']??0); $stmtNoLote->close();
                }
                if(($saldoSinLote+0.000001)<$restante){
                    return ['status'=>'error','message'=>'Inventario insuficiente después de consumir los lotes vigentes de '.$nombre_producto.'.'];
                }
            }

            $saldoOperacion-=$restante;
            $stmtMov=$mysqli->prepare("INSERT INTO movimientos
                (productos_id,cantidad_entrada,cantidad_salida,saldo,empresa_id,fecha_registro,almacen_id,lote_id,clientes_id,documento,comentario)
                VALUES (?,0,?,?,?,NOW(),?,0,?,?,?)");
            if(!$stmtMov){ return ['status'=>'error','message'=>'No se pudo preparar el movimiento sin lote.']; }
            $stmtMov->bind_param('iddiiiss',$producto_id,$restante,$saldoOperacion,$empresa_id,$almacen_solicitado,$clientes_id,$documento,$comentario);
            if(!$stmtMov->execute()){
                $msg=$stmtMov->error; $stmtMov->close();
                return ['status'=>'error','message'=>'No se pudo registrar la salida sin lote: '.$msg];
            }
            $stmtMov->close();
            $consumos[]=['lote_id'=>0,'numero_lote'=>'','usado'=>$restante,'almacen_id'=>$almacen_solicitado,'saldo_final'=>$saldoOperacion];
            $restante=0;
        }

        return [
            'status'=>'success',
            'message'=>'Salida registrada correctamente',
            'detalle'=>$consumos,
            'saldo_final'=>$saldoOperacion,
            'permite_negativo'=>$permiteNegativo?1:0
        ];
    }

    // --- HELPER: almacén por defecto del producto (por si no hay lotes) ---
    protected function getAlmacenProducto($producto_id) {
        $cn = mainModel::connection();
        $stmt = $cn->prepare("SELECT almacen_id FROM productos WHERE productos_id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) return (int)$row['almacen_id'];
        return 0;
    }
    
    protected function getSaldoProductosMovimientosModelo($productos_id)
    {
        $mysqli = self::connection();
    
        // Consulta preparada para evitar inyecciones SQL
        $query = "SELECT COALESCE(SUM(m.cantidad_entrada), 0) - COALESCE(SUM(m.cantidad_salida), 0) AS saldo 
                  FROM movimientos AS m
                  INNER JOIN productos AS p ON m.productos_id = p.productos_id 
                  WHERE p.estado = 1 AND p.productos_id = ?";
    
        // Preparar y ejecutar la consulta
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $productos_id);  // Bind para el parámetro del producto
        $stmt->execute();
    
        // Obtener el resultado y devolver el saldo
        $result = $stmt->get_result();
        return ($result && $row = $result->fetch_assoc()) ? $row['saldo'] : 0;
    }    

    protected function getTotalFacturasRegistradas() {
        try {
            $conexion = $this->connection();
            $primerDiaMes = date('Y-m-01');
            $ultimoDiaMes = date('Y-m-t');
    
            $query = "SELECT COUNT(facturas_id) AS total 
                      FROM facturas 
                      WHERE estado IN(2,3)
                      AND CAST(fecha_registro AS DATE) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'";
    
            $resultado = $conexion->query($query);
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
        } catch (Exception $e) {
            error_log("Error en getTotalFacturasRegistradas: " . $e->getMessage());
            return 0;
        }
    }

    // Nuevo método para verificar si el saldo es cero
    protected function verificar_saldo_cero($facturas_id) {
        $query = "SELECT saldo FROM cobrar_clientes WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['saldo'] == 0;
        }
        
        return false;
    }

    // Nuevo método para actualizar el estado de pago completo
    protected function actualizar_estado_pago_completo($facturas_id) {
        $update = "UPDATE cobrar_clientes 
                  SET estado = 2 
                  WHERE facturas_id = '$facturas_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;
    }
    
    // Método mejorado para actualizar secuencia
    public static function actualizar_secuencia_modelo($secuencia_id, $nuevo_numero, $conexion = null) {
        $conexionLocal = false;
        
        try {
            // Si no se proporciona una conexión, crear una local
            if($conexion === null) {
                $conexion = self::staticConnection();
                $conexionLocal = true;
                $conexion->begin_transaction();
            }
            
            // Verificar que el nuevo número no exceda el rango final
            $check_sql = "SELECT rango_final FROM secuencia_facturacion 
                          WHERE secuencia_facturacion_id = ? FOR UPDATE";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bind_param("i", $secuencia_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows == 0) {
                $check_stmt->close();
                if($conexionLocal) $conexion->rollback();
                return false;
            }
            
            $row = $check_result->fetch_assoc();
            $rango_final = (int)$row['rango_final'];
            $check_stmt->close();
            
            if($nuevo_numero > $rango_final) {
                if($conexionLocal) $conexion->rollback();
                return false;
            }
            
            $sql = "UPDATE secuencia_facturacion 
                    SET siguiente = ? 
                    WHERE secuencia_facturacion_id = ?";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $nuevo_numero, $secuencia_id);
            $result = $stmt->execute();
            $stmt->close();
            
            if($conexionLocal) {
                if($result) {
                    $conexion->commit();
                } else {
                    $conexion->rollback();
                }
            }
            
            return $result;
        } catch (Exception $e) {
            if($conexionLocal && isset($conexion)) $conexion->rollback();
            error_log("Error al actualizar secuencia: " . $e->getMessage());
            return false;
        }
    }
}