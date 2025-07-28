<?php
require_once __DIR__ . '/../mainModel.php';

class facturasRestauranteModelo extends mainModel {
    
    // Obtener mesas disponibles
    public function obtenerMesas() {
        $sql = "SELECT mesa_id as id, numero, capacidad, ubicacion, estado 
                FROM mesas 
                WHERE estado IN ('disponible', 'ocupada')
                ORDER BY numero ASC";
        
        $result = $this->ejecutar_consulta_simple($sql);
        $mesas = [];
        
        while ($row = $result->fetch_assoc()) {
            $mesas[] = [
                'id' => $row['id'],
                'numero' => $row['numero'],
                'capacidad' => $row['capacidad'],
                'ubicacion' => $row['ubicacion'],
                'estado' => $row['estado']
            ];
        }
        
        return $mesas;
    }
    
    // Guardar nueva mesa
    public function guardarMesa($numero, $capacidad, $ubicacion) {
        try {
            $query_check = "SELECT COUNT(*) as total FROM mesas 
                           WHERE numero = ?";
            $result_check = $this->ejecutar_consulta_simple_preparada($query_check, "s", [$numero]);
            
            if ($result_check && $result_check->fetch_assoc()['total'] > 0) {
                return [
                    'status' => false,
                    'message' => 'Ya existe una mesa con este número'
                ];
            }
            
            $query = "INSERT INTO mesas (numero, capacidad, ubicacion, estado, colaborador_id, empresa_id) 
                      VALUES (?, ?, ?, 'disponible', ?, ?)";
            
            $params = [
                $numero, 
                $capacidad, 
                $ubicacion,
                $this->getColaboradorId(),
                $this->getEmpresaId()
            ];
            
            $result = $this->ejecutar_consulta_simple_preparada($query, "sissii", $params);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Mesa guardada correctamente',
                    'id' => $this->connection()->insert_id
                ];
            }
            
            return [
                'status' => false,
                'message' => 'Error al guardar la mesa'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Obtener categorías de productos
    public function obtenerCategoriasProductos() {
        $sql = "SELECT categoria_id as id, nombre 
                FROM categoria
                WHERE estado = 1 
                ORDER BY nombre ASC";
        
        $result = $this->ejecutar_consulta_simple($sql);
        $categorias = [];
        
        while ($row = $result->fetch_assoc()) {
            $categorias[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre']
            ];
        }
        
        return $categorias;
    }
    
    // Obtener productos
    public function obtenerProductos() {
        $sql = "SELECT productos_id, nombre, descripcion, precio_venta, 
                       cantidad_mayoreo, precio_mayoreo, file_name, categoria_id
                FROM productos 
                WHERE estado = 1 
                ORDER BY nombre ASC";
        
        $result = $this->ejecutar_consulta_simple($sql);
        $productos = [];
        
        while ($row = $result->fetch_assoc()) {
            $productos[] = [
                'productos_id' => $row['productos_id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'precio_venta' => floatval($row['precio_venta']),
                'cantidad_mayoreo' => floatval($row['cantidad_mayoreo']),
                'precio_mayoreo' => floatval($row['precio_mayoreo']),
                'file_name' => $row['file_name'],
                'categoria_id' => $row['categoria_id']
            ];
        }
        
        return $productos;
    }
    
    // Obtener clientes
    public function obtenerClientes() {
        $sql = "SELECT clientes_id as id, nombre, rtn as identificacion 
                FROM clientes 
                WHERE estado = 1 
                ORDER BY nombre ASC";
        
        $result = $this->ejecutar_consulta_simple($sql);
        $clientes = [];
        
        while ($row = $result->fetch_assoc()) {
            $clientes[] = [
                'clientes_id' => $row['id'],
                'nombre' => $row['nombre'],
                'identificacion' => $row['identificacion']
            ];
        }
        
        return $clientes;
    }
    
    // Obtener factura activa de una mesa
    public function obtenerFacturaMesa($mesa_id) {
        $sql = "SELECT f.*, m.numero as numero_mesa, m.capacidad as capacidad_mesa, 
                       m.ubicacion as ubicacion_mesa, c.nombre as cliente_nombre,
                       c.rtn as cliente_identificacion, fc.comentarios_cocina
                FROM facturas f
                JOIN factura_comanda fc ON f.facturas_id = fc.factura_id
                JOIN mesas m ON fc.mesa_id = m.mesa_id
                LEFT JOIN clientes c ON f.clientes_id = c.clientes_id
                WHERE fc.mesa_id = ? AND f.estado IN (1, 3)
                LIMIT 1";
        
        $result = $this->ejecutar_consulta_simple_preparada($sql, "i", [$mesa_id]);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }

    
    // Obtener detalles de una factura
    public function obtenerDetallesFactura($factura_id) {
        $sql = "SELECT fd.*, p.nombre as nombre_producto, p.descripcion as descripcion_producto
                FROM facturas_detalles fd
                JOIN productos p ON fd.productos_id = p.productos_id
                WHERE fd.facturas_id = ?";
        
        $result = $this->ejecutar_consulta_simple_preparada($sql, "i", [$factura_id]);
        $detalles = [];
        
        while ($row = $result->fetch_assoc()) {
            $detalles[] = [
                'productos_id' => $row['productos_id'],
                'nombre_producto' => $row['nombre_producto'],
                'descripcion_producto' => $row['descripcion_producto'],
                'cantidad' => $row['cantidad'],
                'precio' => $row['precio'],
                'isv_valor' => $row['isv_valor'],
                'descuento' => $row['descuento']
            ];
        }
        
        return $detalles;
    }
    
    // Guardar nueva factura
    public function guardarFactura($data) {
        try {
            $this->connection()->begin_transaction();
            
            $mesa_id = $data['mesa_id'];
            $cliente_id = isset($data['cliente_id']) ? $data['cliente_id'] : 0;
            $items = $data['items'];
            $metodo_pago = $data['metodo_pago'];
            $observaciones = $this->cleanString($data['observaciones']);
            $comentarios_cocina = isset($data['comentarios_cocina']) ? $this->cleanString($data['comentarios_cocina']) : '';
            
            $numeroFactura = $this->obtenerNumeroFactura($this->getEmpresaId(), 1);
            
            if ($numeroFactura['error']) {
                $this->connection()->rollback();
                return [
                    'status' => false,
                    'message' => $numeroFactura['mensaje']
                ];
            }
            
            $subtotal = 0;
            $impuesto = 0;
            
            foreach ($items as $item) {
                $precio = floatval($item['precio']);
                $cantidad = intval($item['cantidad']);
                $subtotal += $precio * $cantidad;
            }
            
            $impuesto = $subtotal * 0.15;
            $total = $subtotal + $impuesto;
            
            // Insertar en facturas (sin mesa_id ni comentarios_cocina)
            $query = "INSERT INTO facturas (
                clientes_id, secuencia_facturacion_id, number, tipo_factura,
                colaboradores_id, importe, notas, fecha, estado, usuario, empresa_id,
                fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $params = [
                $cliente_id,
                $numeroFactura['data']['secuencia_facturacion_id'],
                $numeroFactura['data']['numero'],
                1,
                $this->getColaboradorId(),
                $total,
                $observaciones,
                date('Y-m-d'),
                1,
                $this->getUsuarioId(),
                $this->getEmpresaId()
            ];
            
            $result = $this->ejecutar_consulta_simple_preparada(
                $query, 
                "iiisidsii", 
                $params
            );
            
            if (!$result) {
                $this->connection()->rollback();
                return [
                    'status' => false,
                    'message' => 'Error al crear la factura'
                ];
            }
            
            $factura_id = $this->connection()->insert_id;
            
            // Insertar en factura_comanda
            $queryComanda = "INSERT INTO factura_comanda (
                factura_id, mesa_id, comentarios_cocina, estado
            ) VALUES (?, ?, ?, 'pendiente')";
            
            $this->ejecutar_consulta_simple_preparada(
                $queryComanda,
                "iis",
                [$factura_id, $mesa_id, $comentarios_cocina]
            );
            
            // Insertar detalles de factura
            foreach ($items as $item) {
                $producto_id = intval($item['producto_id']);
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                $isv_valor = $precio * 0.15;
                
                $query = "INSERT INTO facturas_detalles (
                    facturas_id, productos_id, cantidad, precio, isv_valor, descuento
                ) VALUES (?, ?, ?, ?, ?, 0)";
                
                $params = [
                    $factura_id,
                    $producto_id,
                    $cantidad,
                    $precio,
                    $isv_valor
                ];
                
                $result = $this->ejecutar_consulta_simple_preparada(
                    $query,
                    "iiidd",
                    $params
                );
                
                if (!$result) {
                    $this->connection()->rollback();
                    return [
                        'status' => false,
                        'message' => 'Error al agregar productos a la factura'
                    ];
                }
            }
            
            if ($metodo_pago == 1) {
                $query = "INSERT INTO pagos (
                    facturas_id, tipo_pago, fecha, importe, efectivo, cambio,
                    usuario, estado, empresa_id, fecha_registro
                ) VALUES (?, 1, CURDATE(), ?, ?, 0, ?, 1, ?, NOW())";
                
                $params = [
                    $factura_id,
                    $total,
                    $total,
                    $this->getUsuarioId(),
                    $this->getEmpresaId()
                ];
                
                $result = $this->ejecutar_consulta_simple_preparada(
                    $query,
                    "iddiii",
                    $params
                );
                
                if (!$result) {
                    $this->connection()->rollback();
                    return [
                        'status' => false,
                        'message' => 'Error al registrar el pago'
                    ];
                }
                
                $query = "UPDATE facturas SET estado = 2 WHERE facturas_id = ?";
                $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            }
            
            $query = "UPDATE mesas SET estado = 'ocupada' WHERE mesa_id = ?";
            $this->ejecutar_consulta_simple_preparada($query, "i", [$mesa_id]);
            
            $this->connection()->commit();
            
            return [
                'status' => true,
                'message' => 'Factura creada correctamente',
                'factura' => [
                    'id' => $factura_id,
                    'number' => $numeroFactura['data']['prefijo'] . str_pad($numeroFactura['data']['numero'], $numeroFactura['data']['relleno'], '0', STR_PAD_LEFT),
                    'notas' => $observaciones,
                    'comentarios_cocina' => $comentarios_cocina,
                    'total' => $total,
                    'mesa_id' => $mesa_id
                ]
            ];
            
        } catch (Exception $e) {
            $this->connection()->rollback();
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Actualizar factura existente
    public function actualizarFactura($data) {
        try {
            $this->connection()->begin_transaction();
            
            $factura_id = $data['factura_id'];
            $mesa_id = $data['mesa_id'];
            $cliente_id = isset($data['cliente_id']) ? $data['cliente_id'] : 0;
            $items = $data['items'];
            $metodo_pago = $data['metodo_pago'];
            $observaciones = $this->cleanString($data['observaciones']);
            $comentarios_cocina = isset($data['comentarios_cocina']) ? $this->cleanString($data['comentarios_cocina']) : '';
            
            $query = "SELECT estado FROM facturas WHERE facturas_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            if (!$result || $result->num_rows === 0) {
                $this->connection()->rollback();
                return [
                    'status' => false,
                    'message' => 'Factura no encontrada'
                ];
            }
            
            $factura = $result->fetch_assoc();
            $estado_actual = $factura['estado'];
            
            $subtotal = 0;
            $impuesto = 0;
            
            foreach ($items as $item) {
                $precio = floatval($item['precio']);
                $cantidad = intval($item['cantidad']);
                $subtotal += $precio * $cantidad;
            }
            
            $impuesto = $subtotal * 0.15;
            $total = $subtotal + $impuesto;
            
            // Actualizar factura (sin mesa_id ni comentarios_cocina)
            $query = "UPDATE facturas SET 
                clientes_id = ?,
                importe = ?,
                notas = ?,
                estado = ?
                WHERE facturas_id = ?";
            
            $nuevo_estado = $estado_actual;
            
            if ($metodo_pago == 1 && $estado_actual == 1) {
                $nuevo_estado = 2;
            }
            
            $params = [
                $cliente_id,
                $total,
                $observaciones,
                $nuevo_estado,
                $factura_id
            ];
            
            $result = $this->ejecutar_consulta_simple_preparada(
                $query,
                "idsii",
                $params
            );
            
            if (!$result) {
                $this->connection()->rollback();
                return [
                    'status' => false,
                    'message' => 'Error al actualizar la factura'
                ];
            }
            
            // Actualizar factura_comanda
            $queryComanda = "UPDATE factura_comanda SET 
                mesa_id = ?,
                comentarios_cocina = ?
                WHERE factura_id = ?";
            
            $this->ejecutar_consulta_simple_preparada(
                $queryComanda,
                "isi",
                [$mesa_id, $comentarios_cocina, $factura_id]
            );
            
            // Resto del método (eliminar detalles, insertar nuevos, etc.)
            $query = "DELETE FROM facturas_detalles WHERE facturas_id = ?";
            $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            foreach ($items as $item) {
                $producto_id = intval($item['producto_id']);
                $cantidad = intval($item['cantidad']);
                $precio = floatval($item['precio']);
                $isv_valor = $precio * 0.15;
                
                $query = "INSERT INTO facturas_detalles (
                    facturas_id, productos_id, cantidad, precio, isv_valor, descuento
                ) VALUES (?, ?, ?, ?, ?, 0)";
                
                $params = [
                    $factura_id,
                    $producto_id,
                    $cantidad,
                    $precio,
                    $isv_valor
                ];
                
                $result = $this->ejecutar_consulta_simple_preparada(
                    $query,
                    "iiidd",
                    $params
                );
                
                if (!$result) {
                    $this->connection()->rollback();
                    return [
                        'status' => false,
                        'message' => 'Error al actualizar productos de la factura'
                    ];
                }
            }
            
            $query = "SELECT * FROM pagos WHERE facturas_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            if ($result->num_rows > 0) {
                $query = "UPDATE pagos SET 
                    importe = ?,
                    efectivo = ?,
                    cambio = 0
                    WHERE facturas_id = ?";
                
                $params = [
                    $total,
                    $total,
                    $factura_id
                ];
                
                $this->ejecutar_consulta_simple_preparada(
                    $query,
                    "ddi",
                    $params
                );
            } else if ($metodo_pago == 1) {
                $query = "INSERT INTO pagos (
                    facturas_id, tipo_pago, fecha, importe, efectivo, cambio,
                    usuario, estado, empresa_id, fecha_registro
                ) VALUES (?, 1, CURDATE(), ?, ?, 0, ?, 1, ?, NOW())";
                
                $params = [
                    $factura_id,
                    $total,
                    $total,
                    $this->getUsuarioId(),
                    $this->getEmpresaId()
                ];
                
                $this->ejecutar_consulta_simple_preparada(
                    $query,
                    "iddiii",
                    $params
                );
            }
            
            $this->connection()->commit();
            
            return [
                'status' => true,
                'message' => 'Factura actualizada correctamente',
                'factura' => [
                    'id' => $factura_id,
                    'number' => $factura_id,
                    'notas' => $observaciones,
                    'comentarios_cocina' => $comentarios_cocina,
                    'total' => $total,
                    'mesa_id' => $mesa_id
                ]
            ];
            
        } catch (Exception $e) {
            $this->connection()->rollback();
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Cerrar factura (marcar como pagada o cancelada)
    public function cerrarFactura($factura_id) {
        try {
            $this->connection()->begin_transaction();
            
            $query = "SELECT mesa_id FROM facturas WHERE facturas_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            if (!$result || $result->num_rows === 0) {
                $this->connection()->rollback();
                return [
                    'status' => false,
                    'message' => 'Factura no encontrada'
                ];
            }
            
            $factura = $result->fetch_assoc();
            $mesa_id = $factura['mesa_id'];
            
            $query = "SELECT * FROM pagos WHERE facturas_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            $nuevo_estado = 2;
            
            if ($result->num_rows === 0) {
                $nuevo_estado = 4;
            }
            
            $query = "UPDATE facturas SET estado = ? WHERE facturas_id = ?";
            $this->ejecutar_consulta_simple_preparada($query, "ii", [$nuevo_estado, $factura_id]);
            
            $query = "UPDATE mesas SET estado = 'disponible' WHERE mesa_id = ?";
            $this->ejecutar_consulta_simple_preparada($query, "i", [$mesa_id]);
            
            $this->connection()->commit();
            
            return [
                'status' => true,
                'message' => 'Factura cerrada correctamente'
            ];
            
        } catch (Exception $e) {
            $this->connection()->rollback();
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Método para obtener número de factura
    protected function obtenerNumeroFactura($empresa_id, $documento_id) {
        try {
            $conexion = $this->connection();
            $conexion->begin_transaction();
    
            $sql = "SELECT * FROM secuencia_facturacion 
                    WHERE empresa_id = ? AND documento_id = ? AND activo = 1 
                    FOR UPDATE";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $empresa_id, $documento_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if($result->num_rows === 0){
                $conexion->rollback();
                return ['error' => true, 'mensaje' => 'No se encontró secuencia activa'];
            }
            
            $secuencia = $result->fetch_assoc();
            $stmt->close();
    
            $siguiente_numero = $secuencia['siguiente'];
            if ($siguiente_numero > $secuencia['rango_final']) {
                $conexion->rollback();
                return ['error' => true, 'mensaje' => 'Se ha alcanzado el límite del rango'];
            }
    
            $nuevo_numero = $siguiente_numero + $secuencia['incremento'];
            $update_sql = "UPDATE secuencia_facturacion SET siguiente = ? WHERE secuencia_facturacion_id = ?";
            $update_stmt = $conexion->prepare($update_sql);
            $update_stmt->bind_param("ii", $nuevo_numero, $secuencia['secuencia_facturacion_id']);
            
            if(!$update_stmt->execute()) {
                $conexion->rollback();
                return ['error' => true, 'mensaje' => 'Error al actualizar secuencia'];
            }
            
            $update_stmt->close();
            $conexion->commit();
    
            return [
                'error' => false,
                'data' => [
                    'secuencia_facturacion_id' => $secuencia['secuencia_facturacion_id'],
                    'numero' => $siguiente_numero,
                    'prefijo' => $secuencia['prefijo'],
                    'relleno' => $secuencia['relleno']
                ]
            ];
    
        } catch (Exception $e) {
            error_log("Error obtenerNumeroFactura: " . $e->getMessage());
            return ['error' => true, 'mensaje' => 'Error al generar número de factura'];
        }
    }

    // Obtener comandas pendientes para cocina (basado en facturas)
    public function obtenerComandasCocina() {
        $sql = "SELECT f.facturas_id as id, m.numero as mesa_numero, 
                       f.fecha_registro, f.notas as observaciones,
                       fc.comentarios_cocina, c.nombre as cliente_nombre,
                       fc.estado as estado_comanda
                FROM facturas f
                JOIN factura_comanda fc ON f.facturas_id = fc.factura_id
                JOIN mesas m ON fc.mesa_id = m.mesa_id
                LEFT JOIN clientes c ON f.clientes_id = c.clientes_id
                WHERE f.estado IN (1, 5, 6) 
                AND f.fecha_registro >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
                ORDER BY 
                    CASE WHEN fc.estado = 'urgente' THEN 0
                         WHEN fc.estado = 'pendiente' THEN 1
                         WHEN fc.estado = 'en_preparacion' THEN 2
                         ELSE 3
                    END,
                    f.fecha_registro ASC";
        
        $result = $this->ejecutar_consulta_simple($sql);
        $comandas = [];
        
        while ($row = $result->fetch_assoc()) {
            $items = $this->obtenerDetallesFacturaParaCocina($row['id']);
            
            $comandas[] = [
                'id' => $row['id'],
                'mesa' => $row['mesa_numero'],
                'hora' => date('H:i', strtotime($row['fecha_registro'])),
                'items' => $items,
                'observaciones' => $row['observaciones'],
                'comentarios_cocina' => $row['comentarios_cocina'],
                'cliente_nombre' => $row['cliente_nombre'],
                'urgente' => $row['estado_comanda'] === 'urgente',
                'estado' => $row['estado_comanda']
            ];
        }
        
        return $comandas;
    }

    private function obtenerDetallesFacturaParaCocina($factura_id) {
        $sql = "SELECT fd.productos_id, p.nombre, fd.cantidad, 
                       p.descripcion as observaciones_producto
                FROM facturas_detalles fd
                JOIN productos p ON fd.productos_id = p.productos_id
                WHERE fd.facturas_id = ?";
        
        $result = $this->ejecutar_consulta_simple_preparada($sql, "i", [$factura_id]);
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'productos_id' => $row['productos_id'],
                'nombre' => $row['nombre'],
                'cantidad' => $row['cantidad'],
                'observaciones' => $row['observaciones_producto']
            ];
        }
        
        return $items;
    }

    // Marcar comanda como preparada (actualiza estado de factura)
    public function marcarComandaPreparada($factura_id) {
        try {
            $query = "UPDATE factura_comanda SET estado = 'preparada' WHERE factura_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Comanda marcada como preparada'
                ];
            }
            
            return [
                'status' => false,
                'message' => 'Error al actualizar la comanda'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function marcarComandaUrgente($factura_id, $urgente) {
        try {
            $estado = $urgente ? 'urgente' : 'pendiente';
            $query = "UPDATE factura_comanda SET estado = ? WHERE factura_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "si", [$estado, $factura_id]);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => $urgente ? 'Comanda marcada como urgente' : 'Comanda marcada como normal'
                ];
            }
            
            return [
                'status' => false,
                'message' => 'Error al actualizar la comanda'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    public function marcarComandaEnPreparacion($factura_id) {
        try {
            $query = "UPDATE factura_comanda SET estado = 'en_preparacion' WHERE factura_id = ?";
            $result = $this->ejecutar_consulta_simple_preparada($query, "i", [$factura_id]);
            
            if ($result) {
                return [
                    'status' => true,
                    'message' => 'Comanda marcada como en preparación'
                ];
            }
            
            return [
                'status' => false,
                'message' => 'Error al actualizar la comanda'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

        
    protected function getEmpresaId() {
        return 1;
    }
    
    protected function getUsuarioId() {
        return 1;
    }
    
    protected function getColaboradorId() {
        return 1;
    }
}