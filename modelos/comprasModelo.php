<?php
//comprasModelo.php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class comprasModelo extends mainModel{		
    protected function agregar_compras_modelo($datos){
        $compras_id = mainModel::correlativo("compras_id", "compras");
        $insert = "INSERT INTO compras 
            VALUES('$compras_id','".$datos['proveedores_id']."','".$datos['number']."','".$datos['tipoPurchase']."','".$datos['colaboradores_id']."','".$datos['importe']."','".$datos['notas']."','".$datos['fecha']."','".$datos['estado']."','".$datos['usuario']."','".$datos['empresa']."','".$datos['fecha_registro']."','".$datos['cuentas_id']."','".$datos['recordatorio']."')";
        
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $result;			
    }
    
    protected function agregar_detalle_compras($datos){
        $compras_detalles_id = mainModel::correlativo("compras_detalles_id", "compras_detalles");
        $insert = "INSERT INTO compras_detalles 
            VALUES('$compras_detalles_id','".$datos['compras_id']."','".$datos['productos_id']."','".$datos['cantidad']."',
            '".$datos['precio']."','".$datos['isv_valor']."','".$datos['descuento']."','".$datos['medida']."')";

        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
        
        return $result;			
    }
    
    protected function agregar_movimientos_productos_modelo($datos){
        $movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
        $insert = "INSERT INTO movimientos
            VALUES('$movimientos_id','".$datos['productos_id']."','".$datos['documento']."','".$datos['cantidad_entrada']."',
            '".$datos['cantidad_salida']."','".$datos['saldo']."','".$datos['empresa']."','".$datos['fecha_registro']."',
            '".$datos['clientes_id']."','".$datos['comentario']."','".$datos['almacen_id']."'
            )";

        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
    
        return $result;				
    }
    
    protected function agregar_cuenta_por_pagar_proveedores($datos){
        $pagar_proveedores_id = mainModel::correlativo("pagar_proveedores_id", "pagar_proveedores");
        $insert = "INSERT INTO pagar_proveedores
            VALUES('$pagar_proveedores_id','".$datos['proveedores_id']."','".$datos['compras_id']."','".$datos['fecha']."','".$datos['saldo']."','".$datos['estado']."','".$datos['usuario']."','".$datos['empresa']."','".$datos['fecha_registro']."')";
        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
    
        return $result;				
    }
    
    protected function insert_price_list($datos){
        $price_list_id = mainModel::correlativo("price_list_id", "price_list");
        $insert = "INSERT INTO price_list
            VALUES('$price_list_id','".$datos['compras_id']."','".$datos['productos_id']."','".$datos['prices']."','".$datos['fecha']."','".$datos['usuario']."','".$datos['fecha_registro']."')";

        $result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
    
        return $result;	
    }
    
    protected function actualizar_detalle_compras($datos){
        $update = "UPDATE compras_detalles
                    SET 
                        cantidad = '".$datos['cantidad']."',
                        precio = '".$datos['precio']."',
                        isv_valor = '".$datos['isv_valor']."',
                        descuento = '".$datos['descuento']."'
                    WHERE compras_id = '".$datos['compras_id']."'";
        
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;					
    }	

    protected function actualizar_compra_importe($datos){
        $update = "UPDATE compras
            SET
                importe = '".$datos['importe']."'
            WHERE compras_id = '".$datos['compras_id']."'";
            
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;				
    }		
    
    protected function actualizar_productos_modelo($productos_id, $cantidad, $precio_compra){
        $update = "UPDATE productos
            SET
                cantidad = '$cantidad',
                precio_compra = '$precio_compra'
            WHERE productos_id = '$productos_id'";

        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
    
        return $result;				
    }
    
    protected function actualizar_secuencia_compras_modelo($secuencia_facturacion_id, $numero){
        $update = "UPDATE secuencia_facturacion
            SET
                siguiente = '$numero'
            WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
    
        return $result;				
    }
    
    protected function cancelar_compra_modelo($compras_id){
        $estado = 4;//COMPRA CANCELADA
        $update = "UPDATE compras
            SET
                estado = '$estado'
            WHERE compras_id = '$compras_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
    
        return $result;			
    }
    
    protected function secuencia_compras($empresa_id){
        $query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS 'numero', rango_final, fecha_limite, incremento, relleno
           FROM secuencia_facturacion
           WHERE activo = '1' AND empresa_id = '$empresa_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;
    }
    
    protected function obtener_compraID_modelo($proveedores_id, $fecha, $number, $colaboradores_id){
        $query = "SELECT compras_id 
            FROM compras 
            WHERE proveedores_id = '$proveedores_id' AND fecha = '$fecha' AND number = '$number' AND colaboradores_id = '$colaboradores_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;				
    }
    
    protected function validNumberCompras($proveedores_id, $fecha, $number, $colaboradores_id){
        $query = "SELECT compras_id
            FROM compras 
            WHERE proveedores_id = '$proveedores_id' AND fecha = '$fecha' AND number = '$number' AND colaboradores_id = '$colaboradores_id'";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;					
    }
    
    protected function validDetalleCompras($compras_id, $productos_id){
        $query = "SELECT compras_id
            FROM compras_detalles
            WHERE compras_id = '$compras_id' AND productos_id  = '$productos_id'";
        
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        return $result;			
    }
    
    protected function saldo_productos_movimientos_modelo($productos_id){
        $result = mainModel::getSaldoProductosMovimientos($productos_id);
        
        return $result;			
    }
    
    protected function getISV_modelo(){
        $result = mainModel::getISV('Compras');
        
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
    
    protected function cantidad_producto_modelo($productos_id){
        $result = mainModel::getCantidadProductos($productos_id);
        
        return $result;			
    }	
    
    protected function total_hijos_segun_padre_modelo($productos_id){
        $result = mainModel::getTotalHijosporPadre($productos_id);
        
        return $result;			
    }

    protected function registrar_lote_modelo($datos) {
        $mysqli = mainModel::connection();
        
        do {
            $fechaHora = date("YmdHis");
            $contador = rand(100, 999);
            $numero_lote = "LOT{$datos['productos_id']}{$fechaHora}{$contador}";
    
            // Verificamos si el número de lote ya existe
            $checkQuery = $mysqli->prepare("SELECT numero_lote FROM lotes WHERE numero_lote = ?");
            $checkQuery->bind_param("s", $numero_lote);
            $checkQuery->execute();
            $result = $checkQuery->get_result();
        } while ($result->num_rows > 0); 
    
        // Insertamos el lote
        $insertQuery = "INSERT INTO lotes (numero_lote, productos_id, cantidad, fecha_vencimiento, fecha_ingreso, almacen_id, empresa_id, estado) 
                        VALUES (?, ?, ?, ?, NOW(), ?, ?, 'Activo')";
    
        $stmt = $mysqli->prepare($insertQuery);
        $stmt->bind_param("siisii", 
            $numero_lote, 
            $datos['productos_id'], 
            $datos['cantidad'], 
            $datos['fecha_vencimiento'], 
            $datos['almacen_id'], 
            $datos['empresa_id']
        );
    
        if ($stmt->execute()) {
            return $mysqli->insert_id; // Retornamos el ID del lote insertado
        } else {
            return false; // Retornamos false si hubo un error
        }
    }
            
    protected function registrar_entrada_por_lote($producto_id, $cantidad_entrada, $fecha_vencimiento, $almacen_id, $empresa_id, $documento) {
        // Seleccionar los lotes disponibles para el producto con estado 'Activo'
        $query = mainModel::connection()->query("SELECT lote_id, cantidad, fecha_vencimiento 
                                                 FROM lotes 
                                                 WHERE productos_id = '$producto_id' AND estado = 'Activo' 
                                                 ORDER BY fecha_vencimiento ASC"); // FIFO
        
        $cantidad_restante = $cantidad_entrada;
        $lote_id = 0;
        $numero_lote = ''; // Variable para el número de lote
    
        // Si se ha proporcionado una fecha de vencimiento, buscamos el lote correspondiente
        if ($fecha_vencimiento != null) {
            while ($row = $query->fetch_assoc()) {
                // Compara la fecha de vencimiento y el producto
                if ($row['fecha_vencimiento'] == $fecha_vencimiento) {
                    // Actualizamos la cantidad del lote con el mismo vencimiento
                    $lote_id = $row['lote_id'];
                    $cantidad_disponible = $row['cantidad'];
    
                    // Consultar el saldo actual del lote en la tabla movimientos
                    $saldo_query = mainModel::connection()->query("SELECT saldo FROM movimientos 
                                                                   WHERE lote_id = '{$row['lote_id']}' 
                                                                   ORDER BY fecha_registro DESC LIMIT 1");
                    $saldo = 0; // Si no hay movimientos previos, el saldo es 0
                    if ($saldo_query->num_rows > 0) {
                        $saldo_row = $saldo_query->fetch_assoc();
                        $saldo = $saldo_row['saldo'];
                    } else {
                        // Si no hay saldo previo, consideramos la cantidad del lote como el saldo inicial
                        $saldo = $cantidad_disponible;
                    }
    
                    // Actualizamos el saldo del lote (sumamos la cantidad de entrada)
                    $nuevo_saldo = $saldo + $cantidad_restante;
    
                    // Actualizamos el lote con la nueva cantidad
                    $nueva_cantidad = $cantidad_disponible + $cantidad_restante;
                    mainModel::connection()->query("UPDATE lotes SET cantidad = $nueva_cantidad WHERE lote_id = '$lote_id'");
    
                    // Registrar el movimiento de entrada en la tabla movimientos
                    $insert_movimiento = "INSERT INTO movimientos (productos_id, documento, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, clientes_id, comentario, almacen_id, lote_id) 
                                          VALUES ('$producto_id', '$documento', '$cantidad_restante', 0, '$nuevo_saldo', '$empresa_id', NOW(), 0, 'Entrada de inventario por compra', '$almacen_id', '$lote_id')";
                    mainModel::connection()->query($insert_movimiento);
    
                    $cantidad_restante = 0;
                    break;
                }
            }
        }
    
        // Si no encontramos un lote con la misma fecha de vencimiento o si no se ha enviado fecha
        if ($cantidad_restante > 0) {
            // Si no se ha enviado fecha de vencimiento, no generamos número de lote
            if ($fecha_vencimiento != null) {
                do {
                    $fechaHora = date("YmdHis");
                    $contador = rand(100, 999);
                    $numero_lote = "LOT{$producto_id}{$fechaHora}{$contador}";
    
                    // Verificamos si el número de lote ya existe
                    $checkQuery = mainModel::connection()->prepare("SELECT numero_lote FROM lotes WHERE numero_lote = ?");
                    $checkQuery->bind_param("s", $numero_lote);
                    $checkQuery->execute();
                    $result = $checkQuery->get_result();
                } while ($result->num_rows > 0);  // Si ya existe, se vuelve a generar el número de lote
            }
    
            // Registrar el nuevo lote con el número generado
            $datos_lote = [
                "productos_id" => $producto_id,
                "cantidad" => $cantidad_restante,
                "fecha_vencimiento" => $fecha_vencimiento,
                "numero_lote" => $numero_lote, // Usamos el número de lote generado
                "almacen_id" => $almacen_id,
                "empresa_id" => $empresa_id
            ];
            $lote_id = $this->registrar_lote_modelo($datos_lote); // Usamos la función modular para registrar el nuevo lote
    
            // Registrar el movimiento de entrada para el nuevo lote
            $insert_movimiento = "INSERT INTO movimientos (productos_id, documento, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, clientes_id, comentario, almacen_id, lote_id) 
                                  VALUES ('$producto_id', '$documento', '$cantidad_restante', 0, '$cantidad_restante', '$empresa_id', NOW(), 0, 'Entrada de inventario por compra', '$almacen_id', '$lote_id')";
            mainModel::connection()->query($insert_movimiento);
        }
    
        return true; // Retorna true si la entrada fue registrada correctamente
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
    
    protected function registrar_entrada_lote_modelo($datos) {
        $mysqli = mainModel::connection();
    
        // Verificar si la fecha de vencimiento está presente
        if (isset($datos['fecha_vencimiento']) && $datos['fecha_vencimiento'] !== null) {
            // Verificar si existe un lote con la fecha de vencimiento para el producto
            $checkLoteQuery = $mysqli->prepare("SELECT lote_id, cantidad FROM lotes 
                                                WHERE productos_id = ? AND fecha_vencimiento = ? AND estado = 'Activo'");
            $checkLoteQuery->bind_param("is", $datos['productos_id'], $datos['fecha_vencimiento']);
            $checkLoteQuery->execute();
            $resultLote = $checkLoteQuery->get_result();

            $nuevoSaldo = 0;
    
            if ($resultLote->num_rows > 0) {
                $lote = $resultLote->fetch_assoc();
                $lote_id = $lote['lote_id'];
                $saldo = $lote['cantidad'];  // Saldo actual del lote
    
                // Actualizar el saldo sumando la cantidad de entrada
                $nuevoSaldo = $saldo + $datos['cantidad'];
    
                // Actualizar el lote con el nuevo saldo
                $updateLoteQuery = $mysqli->prepare("UPDATE lotes SET cantidad = ? WHERE lote_id = ?");
                $updateLoteQuery->bind_param("ii", $nuevoSaldo, $lote_id);
                $updateLoteQuery->execute();
            } else {
                // Generar número de lote único
                do {
                    $fechaHora = date("YmdHis");
                    $contador = rand(100, 999);
                    $numero_lote = "LOT{$datos['productos_id']}{$fechaHora}{$contador}";
    
                    $checkQuery = $mysqli->prepare("SELECT numero_lote FROM lotes WHERE numero_lote = ?");
                    $checkQuery->bind_param("s", $numero_lote);
                    $checkQuery->execute();
                    $result = $checkQuery->get_result();
                } while ($result->num_rows > 0);
    
                // Insertar el nuevo lote
                $insertQuery = "INSERT INTO lotes (numero_lote, productos_id, cantidad, fecha_vencimiento, fecha_ingreso, almacen_id, empresa_id, estado) 
                                VALUES (?, ?, ?, ?, NOW(), ?, ?, 'Activo')";
    
                $stmt = $mysqli->prepare($insertQuery);
                $stmt->bind_param("siisii", 
                    $numero_lote, 
                    $datos['productos_id'], 
                    $datos['cantidad'], 
                    $datos['fecha_vencimiento'], 
                    $datos['almacen_id'], 
                    $datos['empresa_id']
                );
    
                if ($stmt->execute()) {
                    $lote_id = $mysqli->insert_id;
                    $nuevoSaldo = $datos['cantidad'];  // El saldo inicial es igual a la cantidad
                } else {
                    return false;
                }
            }
        } else {
            // Si no hay fecha de vencimiento, el lote no se maneja, obtener saldo desde movimientos
            $resultSaldo = $this->getSaldoProductosMovimientos($datos['productos_id']);

            if ($resultSaldo->num_rows > 0) {
                $consulta = $resultSaldo->fetch_assoc();  // Accede a los resultados correctamente
                $saldo = $consulta['saldo'];  // Obtén el saldo desde la consulta
            } else {
                $saldo = 0;  // Si no hay resultados, asigna 0 al saldo
            }

            $nuevoSaldo = $saldo + $datos['cantidad'];
            $lote_id = 0;  // No hay lote asociado
        }
    
        // Asegúrate de que siempre haya un saldo válido
        $cantidadSalida = 0;
        $documento = "";
    
        // Insertar movimiento
        $insertMovimiento = "INSERT INTO movimientos (productos_id, cantidad_entrada, cantidad_salida, saldo, empresa_id, fecha_registro, almacen_id, lote_id, clientes_id, documento, comentario) 
                            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)";
    
        $stmtMovimiento = $mysqli->prepare($insertMovimiento);
        $stmtMovimiento->bind_param("iiiiiiiiss", 
            $datos['productos_id'],  // productos_id
            $datos['cantidad'],
            $cantidadSalida,
            $nuevoSaldo,
            $datos['empresa_id'],
            $datos['almacen_id'],
            $lote_id,
            $datos['clientes_id'],
            $documento,
            $datos['comentario']
        );
    
        if ($stmtMovimiento->execute()) {
            $movimientos_id = $mysqli->insert_id; // Obtener ID del movimiento insertado
    
            // Actualizar el campo documento con "Movimiento" + id del movimiento
            $nuevo_documento = "Movimiento " . $movimientos_id;
            $updateDocumento = $mysqli->prepare("UPDATE movimientos SET documento = ? WHERE movimientos_id = ?");
            $updateDocumento->bind_param("si", $nuevo_documento, $movimientos_id);
            $updateDocumento->execute();
            
            return ["success" => true, "message" => "Entrada registrada correctamente.", "movimientos_id" => $movimientos_id];
        } else {
            return ["success" => false, "message" => "Error al registrar el movimiento de entrada."];
        }
    }	
    
    protected function getTotalComprasRegistradas() {
        try {
            $conexion = $this->connection();
            $primerDiaMes = date('Y-m-01');
            $ultimoDiaMes = date('Y-m-t');
    
            $query = "SELECT COUNT(compras_id) AS total 
                      FROM compras 
                      WHERE estado IN(2,3)
                      AND CAST(fecha_registro AS DATE) BETWEEN '$primerDiaMes' AND '$ultimoDiaMes'";
    
            $resultado = $conexion->query($query);
            $fila = $resultado->fetch_assoc();
            return (int)$fila['total'];
        } catch (Exception $e) {
            error_log("Error en getTotalComprasRegistradas: " . $e->getMessage());
            return 0;
        }
    }

    // Nuevo método para verificar si el saldo es cero
    protected function verificar_saldo_cero($compras_id) {
        $query = "SELECT saldo FROM pagar_proveedores WHERE compras_id = '$compras_id'";
        $result = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['saldo'] == 0;
        }
        
        return false;
    }

    // Nuevo método para actualizar el estado de pago completo
    protected function actualizar_estado_pago_completo($compras_id) {
        $update = "UPDATE pagar_proveedores 
                  SET estado = 2 
                  WHERE compras_id = '$compras_id'";
        $result = mainModel::connection()->query($update) or die(mainModel::connection()->error);
        
        return $result;
    }
}