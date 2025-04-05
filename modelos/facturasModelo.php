<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";    
}

class facturasModelo extends mainModel {
    protected function ejecutarConsulta($query, $params = []) {
        $stmt = mainModel::connection()->prepare($query);
        
        if(!empty($params)) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        return $stmt;
    }
    
    protected function guardar_facturas_modelo($datos) {
        $check = "SELECT COUNT(*) as count FROM facturas WHERE facturas_id = ?";
        $result_check = $this->ejecutarConsulta($check, [$datos['facturas_id']]);
        $row = $result_check->get_result()->fetch_assoc();
    
        if ($row['count'] > 0) {
            $query = "UPDATE facturas SET
                        `clientes_id` = ?,
                        `secuencia_facturacion_id` = ?,
                        `apertura_id` = ?,
                        `number` = ?,
                        `tipo_factura` = ?,
                        `colaboradores_id` = ?,
                        `importe` = ?,
                        `notas` = ?,
                        `fecha` = ?,
                        `estado` = ?,
                        `usuario` = ?,
                        `empresa_id` = ?,
                        `fecha_registro` = ?,
                        `fecha_dolar` = ?
                    WHERE `facturas_id` = ?";
            
            $params = [
                $datos['clientes_id'], $datos['secuencia_facturacion_id'], $datos['apertura_id'],
                $datos['numero'], $datos['tipo_factura'], $datos['colaboradores_id'],
                $datos['importe'], $datos['notas'], $datos['fecha'], $datos['estado'],
                $datos['usuario'], $datos['empresa'], $datos['fecha_registro'],
                $datos['fecha_dolar'], $datos['facturas_id']
            ];
        } else {
            $query = "INSERT INTO facturas (
                        `facturas_id`, `clientes_id`, `secuencia_facturacion_id`, `apertura_id`, 
                        `number`, `tipo_factura`, `colaboradores_id`, `importe`, `notas`, 
                        `fecha`, `estado`, `usuario`, `empresa_id`, `fecha_registro`, `fecha_dolar`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $datos['facturas_id'], $datos['clientes_id'], $datos['secuencia_facturacion_id'], $datos['apertura_id'],
                $datos['numero'], $datos['tipo_factura'], $datos['colaboradores_id'], $datos['importe'], $datos['notas'],
                $datos['fecha'], $datos['estado'], $datos['usuario'], $datos['empresa'], $datos['fecha_registro'], $datos['fecha_dolar']
            ];
        }
    
        $result = $this->ejecutarConsulta($query, $params);
        return $result ? true : false;
    }
    
    protected function agregar_detalle_facturas_modelo($datos) {
        $check = "SELECT COUNT(*) as count FROM facturas_detalles 
                  WHERE facturas_id = ? AND productos_id = ?";
        $result_check = $this->ejecutarConsulta($check, [$datos['facturas_id'], $datos['productos_id']]);
        $row = $result_check->get_result()->fetch_assoc();
    
        if ($row['count'] > 0) {
            $query = "UPDATE facturas_detalles SET
                        `cantidad` = ?,
                        `precio` = ?,
                        `isv_valor` = ?,
                        `descuento` = ?,
                        `medida` = ?
                    WHERE `facturas_id` = ? AND `productos_id` = ?";
            
            $params = [
                $datos['cantidad'], $datos['precio'], $datos['isv_valor'],
                $datos['descuento'], $datos['medida'], $datos['facturas_id'], $datos['productos_id']
            ];
            
            $result = $this->ejecutarConsulta($query, $params);
        } else {
            $facturas_detalle_id = mainModel::correlativo("facturas_detalle_id", "facturas_detalles");
            
            $query = "INSERT INTO facturas_detalles (
                        `facturas_detalle_id`, `facturas_id`, `productos_id`, `cantidad`, 
                        `precio`, `isv_valor`, `descuento`, `medida`
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $facturas_detalle_id, $datos['facturas_id'], $datos['productos_id'], $datos['cantidad'],
                $datos['precio'], $datos['isv_valor'], $datos['descuento'], $datos['medida']
            ];
            
            $result = $this->ejecutarConsulta($query, $params);
        }
    
        return $result ? true : false;
    }
    
    protected function agregar_cambio_dolar_modelo($datos) {
        $query = "INSERT INTO cambio_dolar VALUES(?, ?, ?, ?, ?)";
        $params = [
            $datos['cambio_dolar_id'], $datos['compra'], $datos['venta'], 
            $datos['tipo'], $datos['fecha_registro']
        ];
        
        $result = $this->ejecutarConsulta($query, $params);
        return $result;            
    }

    protected function agregar_movimientos_productos_modelo($datos) {
        $movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
        
        $query = "INSERT INTO movimientos (
                    `movimientos_id`, `productos_id`, `documento`, `cantidad_entrada`, 
                    `cantidad_salida`, `saldo`, `empresa_id`, `fecha_registro`, 
                    `clientes_id`, `comentario`, `almacen_id`, `lote_id`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $movimientos_id, $datos['productos_id'], $datos['documento'], $datos['cantidad_entrada'],
            $datos['cantidad_salida'], $datos['saldo'], $datos['empresa'], $datos['fecha_registro'],
            $datos['clientes_id'], '', $datos['almacen_id'], $datos['lote_id']
        ];
    
        $result = $this->ejecutarConsulta($query, $params);
        return $result;                
    }
    
    protected function agregar_cuenta_por_cobrar_clientes($datos) {
        $cobrar_clientes_id = mainModel::correlativo("cobrar_clientes_id", "cobrar_clientes");
        
        $query = "INSERT INTO cobrar_clientes (
                    `cobrar_clientes_id`, `clientes_id`, `facturas_id`, `fecha`, 
                    `saldo`, `estado`, `usuario`, `empresa_id`, `fecha_registro`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $cobrar_clientes_id, $datos['clientes_id'], $datos['facturas_id'], $datos['fecha'],
            $datos['saldo'], $datos['estado'], $datos['usuario'], $datos['empresa'], $datos['fecha_registro']
        ];
    
        $result = $this->ejecutarConsulta($query, $params);
        return $result;                
    }
    
    protected function agregar_precio_factura_clientes($datos) {
        $precio_factura_id = mainModel::correlativo("precio_factura_id", "precio_factura");
        
        $query = "INSERT INTO precio_factura (
                    `precio_factura_id`, `facturas_id`, `productos_id`, `clientes_id`, 
                    `fecha`, `referencia`, `precio_anterior`, `precio_nuevo`, `fecha_registro`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $precio_factura_id, $datos['facturas_id'], $datos['productos_id'], $datos['clientes_id'],
            $datos['fecha'], $datos['referencia'], $datos['precio_anterior'], $datos['precio_nuevo'], $datos['fecha_registro']
        ];
        
        $result = $this->ejecutarConsulta($query, $params);
        return $result;                
    }
    
    protected function agregar_facturas_proforma_modelo($datos) {
        $facturas_proforma_id = mainModel::correlativo("facturas_proforma_id", "facturas_proforma");
        
        $query = "INSERT INTO facturas_proforma (
                    `facturas_proforma_id`, `facturas_id`, `clientes_id`, `secuencia_facturacion_id`, 
                    `numero`, `importe`, `usuario`, `empresa_id`, `estado`, `fecha_creacion`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $facturas_proforma_id, $datos['facturas_id'], $datos['clientes_id'], $datos['secuencia_facturacion_id'],
            $datos['numero'], $datos['importe'], $datos['usuario'], $datos['empresa_id'], $datos['estado'], $datos['fecha_creacion']
        ];
    
        $result = $this->ejecutarConsulta($query, $params);
        return $result;            
    }

    protected function actualizar_detalle_facturas($datos) {
        $query = "UPDATE facturas_detalles SET
                    `cantidad` = ?,
                    `precio` = ?,
                    `isv_valor` = ?,
                    `descuento` = ?
                WHERE `facturas_id` = ? AND `productos_id` = ?";
        
        $params = [
            $datos['cantidad'], $datos['precio'], $datos['isv_valor'], $datos['descuento'],
            $datos['facturas_id'], $datos['productos_id']
        ];
    
        $result = $this->ejecutarConsulta($query, $params);
        return $result;                    
    }
    
    protected function actualizar_factura_importe($datos) {
        $query = "UPDATE facturas SET `importe` = ? WHERE `facturas_id` = ?";
        $result = $this->ejecutarConsulta($query, [$datos['importe'], $datos['facturas_id']]);
        return $result;                
    }

    protected function actualizar_estado_factura_modelo($facturas_id) {
        $query = "UPDATE facturas SET `estado` = '2' WHERE `facturas_id` = ?";
        $result = $this->ejecutarConsulta($query, [$facturas_id]);
        return $result;                
    }
    
    protected function actualizar_secuencia_facturacion_modelo($secuencia_facturacion_id, $numero) {
        $query = "UPDATE secuencia_facturacion SET `siguiente` = ? WHERE `secuencia_facturacion_id` = ?";
        $result = $this->ejecutarConsulta($query, [$numero, $secuencia_facturacion_id]);
        return $result;                
    }
    
    protected function cancelar_facturas_modelo($facturas_id) {
        $query = "UPDATE facturas SET `estado` = '4' WHERE `facturas_id` = ?";
        $result = $this->ejecutarConsulta($query, [$facturas_id]);
        return $result;            
    }

    protected function secuencia_facturacion_modelo($empresa_id, $documento_id) {
        $query = "SELECT 
                    `secuencia_facturacion_id`, `prefijo`, `siguiente` AS 'numero', 
                    `rango_final`, `fecha_limite`, `incremento`, `relleno`
                  FROM `secuencia_facturacion`
                  WHERE `activo` = '1' AND `empresa_id` = ? AND `documento_id` = ?";
        
        $result = $this->ejecutarConsulta($query, [$empresa_id, $documento_id]);
        return $result->get_result();
    }
    
    protected function validDetalleFactura($facturas_id, $productos_id) {
        $query = "SELECT `facturas_id` FROM `facturas_detalles` WHERE `facturas_id` = ? AND `productos_id` = ?";
        $result = $this->ejecutarConsulta($query, [$facturas_id, $productos_id]);
        return $result->get_result();            
    }

    protected function validar_cobrarClientes_modelo($facturas_id) {
        $query = "SELECT `cobrar_clientes_id` FROM `cobrar_clientes` WHERE `facturas_id` = ?";
        $result = $this->ejecutarConsulta($query, [$facturas_id]);
        return $result->get_result();            
    }
    
    protected function valid_cambio_dolar_modelo($fecha) {
        $query = "SELECT `cambio_dolar_id` FROM `cambio_dolar` WHERE CAST(`fecha_registro` AS DATE) = ?";
        $result = $this->ejecutarConsulta($query, [$fecha]);
        return $result->get_result();                
    }

    protected function valid_cambio_dolar_tipo2_modelo($fecha) {
        $query = "SELECT `cambio_dolar_id` FROM `cambio_dolar` WHERE CAST(`fecha_registro` AS DATE) = ? AND `tipo` = 2";
        $result = $this->ejecutarConsulta($query, [$fecha]);
        return $result->get_result();                
    }
    
    protected function valid_precio_factura_modelo($datos) {
        $query = "SELECT `precio_factura_id` FROM `precio_factura` WHERE `facturas_id` = ?";
        $result = $this->ejecutarConsulta($query, [$datos['facturas_id']]);
        return $result->get_result();                
    }

    protected function saldo_productos_movimientos_modelo($productos_id) {
        return mainModel::getSaldoProductosMovimientos($productos_id);            
    }
    
    protected function getISV_modelo() {
        return mainModel::getISV('Facturas');
    }
    
    protected function getISVEstadoProducto_modelo($productos_id) {
        return mainModel::getISVEstadoProducto($productos_id);            
    }
    
    protected function tipo_producto_modelo($productos_id) {
        return mainModel::getTipoProducto($productos_id);            
    }

    protected function getMedidaProducto($productos_id) {
        $query = "SELECT
                    `productos`.`productos_id`,
                    `medida`.`nombre` AS `medida`,
                    `medida`.`medida_id`,
                    `medida`.`estado`
                FROM `medida`
                INNER JOIN `productos` ON `medida`.`medida_id` = `productos`.`medida_id`    
                WHERE `productos`.`productos_id` = ? AND `medida`.`estado` = 1";
        
        $result = $this->ejecutarConsulta($query, [$productos_id]);
        return $result->get_result();                
    }

    protected function cantidad_producto_modelo($productos_id) {
        return mainModel::getCantidadProductos($productos_id);            
    }

    protected function getAperturaIDModelo($datos) {
        $query = "SELECT `apertura_id` FROM `apertura` 
                 WHERE `colaboradores_id` = ? AND `fecha` = ? AND `estado` = ?";
        
        $params = [$datos['colaboradores_id'], $datos['fecha'], $datos['estado']];
        $result = $this->ejecutarConsulta($query, $params);
        return $result->get_result();            
    }

    protected function total_hijos_segun_padre_modelo($productos_id) {
        return mainModel::getTotalHijosporPadre($productos_id);            
    }
    
    protected function obtener_lote_para_salida($producto_id, $cantidad_salida) {
        $query = "SELECT `lote_id`, `cantidad`, `fecha_vencimiento` 
                  FROM `lotes` 
                  WHERE `productos_id` = ? AND `cantidad` > 0 AND `estado` = 'Activo' 
                  ORDER BY `fecha_vencimiento` ASC";
        
        $stmt = $this->ejecutarConsulta($query, [$producto_id]);
        $result = $stmt->get_result();
        
        $lote_id = 0;
        $cantidad_restante = $cantidad_salida;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['cantidad'] >= $cantidad_restante) {
                $lote_id = $row['lote_id'];
                $nueva_cantidad = $row['cantidad'] - $cantidad_restante;
                $this->ejecutarConsulta("UPDATE `lotes` SET `cantidad` = ? WHERE `lote_id` = ?", [$nueva_cantidad, $lote_id]);
                break;
            } else {
                $cantidad_restante -= $row['cantidad'];
                $lote_id = $row['lote_id'];
                $this->ejecutarConsulta("UPDATE `lotes` SET `cantidad` = 0 WHERE `lote_id` = ?", [$lote_id]);
            }
        }
        
        return $lote_id;
    }
    
    protected function saldo_productos_por_lote_modelo($producto_id, $lote_id) {
        $query = "SELECT `saldo` FROM `movimientos` 
                  WHERE `productos_id` = ? AND `lote_id` = ? 
                  ORDER BY `fecha_registro` DESC LIMIT 1";
        
        $stmt = $this->ejecutarConsulta($query, [$producto_id, $lote_id]);
        $result = $stmt->get_result();
        
        return $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    protected function registrar_salida_lote_modelo($datos) {
        $checkLoteQuery = "SELECT `lote_id`, `cantidad` FROM `lotes` 
                          WHERE `productos_id` = ? AND `estado` = 'Activo' 
                          ORDER BY `fecha_vencimiento` ASC LIMIT 1";
        
        $stmtLote = $this->ejecutarConsulta($checkLoteQuery, [$datos['productos_id']]);
        $resultLote = $stmtLote->get_result();
    
        if ($resultLote->num_rows > 0) {
            $lote = $resultLote->fetch_assoc();
            $lote_id = $lote['lote_id'];
            $saldo = $lote['cantidad'];
        } else {
            $resultSaldo = $this->saldo_productos_movimientos_modelo($datos['productos_id']);
            $consulta = $resultSaldo->fetch_assoc();
            $saldo = $consulta['saldo'] ?? 0;
            $lote_id = 0;
        }
    
        if ($saldo >= $datos['cantidad']) {
            $cantidad_salida = $datos['cantidad'];
            $nuevo_saldo = $saldo - $datos['cantidad'];
    
            $query = "INSERT INTO `movimientos` (
                        `productos_id`, `cantidad_entrada`, `cantidad_salida`, `saldo`, 
                        `empresa_id`, `fecha_registro`, `almacen_id`, `lote_id`, 
                        `clientes_id`, `documento`, `comentario`
                    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
            
            $cantidadEntrada = 0;
            $params = [
                $datos['productos_id'], $cantidadEntrada, $cantidad_salida, $nuevo_saldo,
                $datos['empresa_id'], $datos['almacen_id'], $lote_id, $datos['clientes_id'],
                $datos['documento'], $datos['comentario']
            ];
    
            $stmtMovimiento = $this->ejecutarConsulta($query, $params);
            
            if ($stmtMovimiento) {
                if ($lote_id > 0) {
                    $updateLote = "UPDATE `lotes` SET `cantidad` = ? WHERE `lote_id` = ?";
                    $this->ejecutarConsulta($updateLote, [$nuevo_saldo, $lote_id]);
    
                    if ($nuevo_saldo == 0) {
                        $updateEstadoLote = "UPDATE `lotes` SET `estado` = 'Inactivo' WHERE `lote_id` = ?";
                        $this->ejecutarConsulta($updateEstadoLote, [$lote_id]);
                    }
                }
    
                return ["status" => "success", "message" => "Movimiento registrado con éxito"];
            } else {
                return ["status" => "error", "message" => "Error al registrar el movimiento"];
            }
        } else {
            return ["status" => "error", "message" => "Saldo insuficiente para la salida"];
        }
    }
    
    protected function getSaldoProductosMovimientosModelo($productos_id) {
        $query = "SELECT COALESCE(SUM(`m`.`cantidad_entrada`), 0) - COALESCE(SUM(`m`.`cantidad_salida`), 0) AS `saldo` 
                  FROM `movimientos` AS `m`
                  INNER JOIN `productos` AS `p` ON `m`.`productos_id` = `p`.`productos_id` 
                  WHERE `p`.`estado` = 1 AND `p`.`productos_id` = ?";
        
        $stmt = $this->ejecutarConsulta($query, [$productos_id]);
        $result = $stmt->get_result();
        return ($result && $row = $result->fetch_assoc()) ? $row['saldo'] : 0;
    }
}