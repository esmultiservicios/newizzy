<?php
//facturasModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class facturasModelo extends mainModel{		
    protected function guardar_facturas_modelo($datos) {
        // Verificar si ya existe un registro con el mismo facturas_id
        $check = "SELECT COUNT(*) as count FROM facturas 
                  WHERE facturas_id = '".$datos['facturas_id']."'";
        $result_check = mainModel::connection()->query($check) or die(mainModel::connection()->error);
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
						'".$datos['exoneracion_orden']."',
						'".$datos['exoneracion_constancia']."',
						'".$datos['exoneracion_sag']."',
						'".$datos['exoneracion_orden_interno']."'	                        
                    )";
        }
    
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
    
        // Devolver true si la consulta fue exitosa, false en caso contrario
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
    * INVENTARIO: ARREGLA bind_param (con/sin lote)
    * =========================== */
    protected function registrar_salida_lote_modelo($datos) {
        $mysqli = mainModel::connection();
    
        $producto_id         = (int)$datos['productos_id'];
        $empresa_id          = (int)$datos['empresa_id'];
        $clientes_id         = (int)($datos['clientes_id'] ?? 0);
        $documento           = (string)$datos['documento'];
        $comentario          = (string)$datos['comentario'];
        $cantidad_solicitada = (float)$datos['cantidad'];
        $almacen_solicitado  = isset($datos['almacen_id']) ? (int)$datos['almacen_id'] : 0;
    
        // 1) Traer lotes activos con stock > 0 (FIFO real)
        $sql = "
            SELECT lote_id, cantidad, almacen_id
            FROM lotes
            WHERE productos_id = ?
              AND estado = 'Activo'
              AND cantidad > 0
              ".($almacen_solicitado > 0 ? " AND almacen_id = ? " : "")."
            ORDER BY fecha_ingreso ASC, fecha_vencimiento ASC, lote_id ASC
        ";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            return ["status" => "error", "message" => "Error prepare lotes: ".$mysqli->error];
        }
        if ($almacen_solicitado > 0) {
            $stmt->bind_param("ii", $producto_id, $almacen_solicitado);
        } else {
            $stmt->bind_param("i", $producto_id);
        }
        $stmt->execute();
        $rs = $stmt->get_result();
    
        $restante = $cantidad_solicitada;
        $consumos = [];
    
        // 2) Consumir por lotes
        while ($restante > 0 && ($lote = $rs->fetch_assoc())) {
            $lote_id      = (int)$lote['lote_id'];
            $en_lote      = (float)$lote['cantidad'];
            $almacen_lote = (int)$lote['almacen_id'];
            if ($en_lote <= 0) continue;
    
            $a_usar           = ($en_lote >= $restante) ? $restante : $en_lote;
            $nuevo_saldo_lote = $en_lote - $a_usar;
    
            // INSERT con lote (9 placeholders → 9 tipos/valores)
            $insertMov = "INSERT INTO movimientos
                (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro,
                 almacen_id, lote_id, clientes_id, documento, comentario)
                VALUES (?, 0, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
            $stmtMov = $mysqli->prepare($insertMov);
            if (!$stmtMov) {
                return ["status" => "error", "message" => "Error prepare mov: ".$mysqli->error];
            }
            $stmtMov->bind_param(
                "iddiiiiss",
                $producto_id,        // i
                $a_usar,             // d
                $nuevo_saldo_lote,   // d
                $empresa_id,         // i
                $almacen_lote,       // i
                $lote_id,            // i
                $clientes_id,        // i
                $documento,          // s
                $comentario          // s
            );
            if (!$stmtMov->execute()) {
                return ["status" => "error", "message" => "Error ejecutar mov: ".$stmtMov->error];
            }
    
            // Actualizar lote
            $sqlUp = "UPDATE lotes SET cantidad = ?".($nuevo_saldo_lote <= 0 ? ", estado='Inactivo' " : " ")."WHERE lote_id = ?";
            $up = $mysqli->prepare($sqlUp);
            if (!$up) {
                return ["status" => "error", "message" => "Error prepare update lote: ".$mysqli->error];
            }
            $up->bind_param("di", $nuevo_saldo_lote, $lote_id);
            if (!$up->execute()) {
                return ["status" => "error", "message" => "Error update lote: ".$up->error];
            }
    
            $consumos[] = [
                "lote_id"     => $lote_id,
                "usado"       => $a_usar,
                "almacen_id"  => $almacen_lote,
                "saldo_final" => $nuevo_saldo_lote
            ];
            $restante -= $a_usar;
        }
        $stmt->close();
    
        // 3) Sin lote (saldo global)
        if ($restante > 0) {
            $saldo_global = (float)$this->getSaldoProductosMovimientosModelo($producto_id);
            if ($saldo_global < $restante) {
                return ["status" => "error", "message" => "Saldo insuficiente para la salida (falta $restante)"];
            }
            $nuevo_saldo_global = $saldo_global - $restante;
    
            $almacen_mov = $almacen_solicitado > 0 ? $almacen_solicitado : (int)$this->getAlmacenProducto($producto_id);
    
            $insertMov = "INSERT INTO movimientos
                (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro,
                 almacen_id, lote_id, clientes_id, documento, comentario)
                VALUES (?, 0, ?, ?, ?, NOW(), ?, 0, ?, ?, ?)";
            $stmtMov = $mysqli->prepare($insertMov);
            if (!$stmtMov) {
                return ["status" => "error", "message" => "Error prepare mov (sin lote): ".$mysqli->error];
            }
            // 8 placeholders → 8 tipos/valores
            $stmtMov->bind_param(
                "iddiiiss",
                $producto_id,        // i
                $restante,           // d
                $nuevo_saldo_global, // d
                $empresa_id,         // i
                $almacen_mov,        // i
                $clientes_id,        // i
                $documento,          // s
                $comentario          // s
            );
            if (!$stmtMov->execute()) {
                return ["status" => "error", "message" => "Error ejecutar mov (sin lote): ".$stmtMov->error];
            }
    
            $consumos[] = [
                "lote_id"     => 0,
                "usado"       => $restante,
                "almacen_id"  => $almacen_mov,
                "saldo_final" => $nuevo_saldo_global
            ];
            $restante = 0;
        }
    
        return ["status" => "success", "message" => "Salida registrada (FIFO)", "detalle" => $consumos];
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