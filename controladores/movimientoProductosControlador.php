<?php
if ($peticionAjax) {
    require_once "../modelos/movimientoProductosModelo.php";
} else {
    require_once "./modelos/movimientoProductosModelo.php";
}

class movimientoProductosControlador extends movimientoProductosModelo
{
    public function agregar_movimiento_productos_controlador()
    {
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $movimiento_producto = $_POST['movimiento_producto'];
        $movimiento_operacion = $_POST['movimiento_operacion'];
        $movimiento_cantidad = $_POST['movimiento_cantidad'];
        $movimiento_comentario = $_POST['movimiento_comentario'];
        $cliente_movimientos = $_POST['cliente_movimientos'] ?? 0;
        $almacen = $_POST['almacen_modal'];
        $empresa_id = $_SESSION['empresa_id_sd'];
        $movimiento_lote = $_POST['movimiento_lote'] ?? null; // Captura el lote seleccionado si existe
        $fecha_vencimiento = !empty($_POST['movimiento_fecha_vencimiento']) ? $_POST['movimiento_fecha_vencimiento'] : null;

        // Validar empresa
        if (!$this->verificar_empresa($empresa_id)) {
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "El ID de empresa no es válido.",
                "type" => "error",
            ]);
        }

        // Si tiene un lote, se ignora la fecha de vencimiento
        if (!empty($movimiento_lote)) {
            $fecha_vencimiento = null;
        }

        // Calcular nuevo saldo según operación
        if ($movimiento_operacion == "entrada") { // ENTRADA
            $datos = [
                "productos_id" => $movimiento_producto,
                "clientes_id" => $cliente_movimientos ?: 0,
                "comentario" => $movimiento_comentario,
                "almacen_id" => $almacen ?: 0,
                "fecha_vencimiento" => $fecha_vencimiento,
                "cantidad" => $movimiento_cantidad,
                "empresa_id" => $empresa_id,
                "movimiento_lote" => $movimiento_lote
            ];

            $resultado = movimientoProductosModelo::registrar_entrada_lote_modelo($datos);

            if ($resultado['success']) {
                return mainModel::showNotification([
                    "title" => "Registro almacenado",
                    "text" => $resultado['message'],
                    "type" => "success",
                    "form" => "formMovimientos",
                    "funcion" => "listar_movimientos();funciones();"
                ]);
            } else {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => $resultado['message'],
                    "type" => "error"
                ]);
            }
        }

        if ($movimiento_operacion == "salida") { // SALIDA
            $datos = [
                "productos_id" => $movimiento_producto,
                "clientes_id" => $cliente_movimientos ?: 0,
                "comentario" => $movimiento_comentario,
                "almacen_id" => $almacen ?: 0,
                "cantidad" => $movimiento_cantidad,
                "empresa_id" => $empresa_id,
                "movimiento_lote" => $movimiento_lote
            ];

            $resultado = movimientoProductosModelo::registrar_salida_lote_modelo($datos);

            if ($resultado['status'] == "success") {
                return mainModel::showNotification([
                    "title" => "Registro almacenado",
                    "text" => $resultado['message'],
                    "type" => "success",
                    "form" => "formMovimientos",
                    "funcion" => "listar_movimientos();"
                ]);
            } else {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => $resultado['message'],
                    "type" => "error"
                ]);
            }
        }
    }

    public function modificar_fecha_vencimiento_movimiento_productos_controlador() {
        $fecha_vencimiento = $_POST['fecha_caducidad'];
        $productos_id = $_POST['productos_id'];
        $almacen_id = $_POST['id_bodega_actual'];
        $lote_id = $_POST['lote_id_productos'];
        $cantidad = $_POST['cantidad_productos'];
        $empresa_id = $_POST['empresa_id_productos'];
        $fecha_ingreso = date("Y-m-d H:i:s"); // Fecha y hora actual

        $mysqli = mainModel::connection();
    
        // Verificar si el lote_id es 0 en el producto (sin lote asignado)
        if ($lote_id == 0) {
            // Primero, creamos un nuevo lote
            $fechaHora = date("YmdHis");
            $contador = rand(100, 999);
            $numero_lote = "LOT{$productos_id}{$fechaHora}{$contador}";
    
            // Inserta el nuevo lote
            $insertLoteQuery = $mysqli->prepare("INSERT INTO lotes (productos_id, almacen_id, numero_lote, cantidad, fecha_vencimiento, estado, empresa_id, fecha_ingreso) VALUES (?, ?, ?, ?, ?, 'Activo', ?, ?)");

            // Aquí, la cadena 'iiisii' debe tener 7 tipos de datos que corresponden a 6 parámetros.
            $insertLoteQuery->bind_param("iisisis", $productos_id, $almacen_id, $numero_lote, $cantidad, $fecha_vencimiento, $empresa_id, $fechaHora);
    
            if ($insertLoteQuery->execute()) {
                $nuevo_lote_id = $mysqli->insert_id; // Obtener el id del nuevo lote
    
                // Ahora asociamos el nuevo lote solo si el producto no tiene un lote asignado
                $updateProductoQuery = $mysqli->prepare("UPDATE movimientos SET lote_id = ? WHERE productos_id = ? AND almacen_id = ? AND lote_id = ?");
                $lote_id_cero = 0;
                $updateProductoQuery->bind_param("iiii", $nuevo_lote_id, $productos_id, $almacen_id, $lote_id_cero);                
    
                if ($updateProductoQuery->execute()) {
                    if ($updateProductoQuery->affected_rows > 0) {
                        // Obtener el nombre del almacén
                        $queryAlmacen = $mysqli->prepare("SELECT nombre FROM almacen WHERE almacen_id = ?");
                        $queryAlmacen->bind_param("i", $almacen_id);
                        $queryAlmacen->execute();
                        $queryAlmacen->bind_result($nombre_almacen);
                        $queryAlmacen->fetch();
                        $queryAlmacen->close();

                        // Mensaje con el nombre del almacén
                        $text = "Nuevo lote creado y asignado al producto en el almacén: " . $nombre_almacen;

                        return mainModel::showNotification([
                            "title" => "Registro almacenado",
                            "text" => $text,
                            "type" => "success",
                            "funcion" => "inventario_transferencia()",  
                            "closeAllModals" => true                          
                        ]);                        
                    } else {
                        return mainModel::showNotification([
                            "title" => "Error",
                            "text" => "El producto ya tiene un lote asignado en este almacén. No se realizó ninguna actualización",
                            "type" => "error"
                        ]);
                    }
                } else {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "Error al actualizar el producto con el nuevo lote.",
                        "type" => "error"
                    ]);
                }
            } else {
                  return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "Error al crear el nuevo lote.",
                    "type" => "error"
                ]);                
            }
        }
    
        // Si el lote_id no es 0, no hace falta hacer nada más ya que se asume que ya tiene un lote asignado.
        return mainModel::showNotification([
            "title" => "Error",
            "text" => "El producto ya tiene un lote, no se requiere la creación de un nuevo lote.",
            "type" => "error"
        ]); 
    }    
}