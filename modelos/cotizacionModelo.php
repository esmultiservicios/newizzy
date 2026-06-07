<?php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class cotizacionModelo extends mainModel
{
    protected function agregar_cotizacion_modelo($datos)
    {
        $conexion = $this->connection();

        try {
            $conexion->autocommit(false);

            /*
                IMPORTANTE:
                Según la estructura real de la tabla cotizacion, los campos son:
                - number
                - vigencia_cotizacion_id

                No existen:
                - numero
                - vigencia
            */
            $stmt = $conexion->prepare("
                INSERT INTO cotizacion
                (
                    cotizacion_id,
                    clientes_id,
                    number,
                    tipo_factura,
                    colaboradores_id,
                    importe,
                    notas,
                    fecha,
                    estado,
                    vigencia_cotizacion_id,
                    usuario,
                    empresa_id,
                    fecha_registro,
                    fecha_dolar
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $cotizacion_id = (int)$datos['cotizacion_id'];
            $clientes_id = (int)$datos['clientes_id'];
            $numero = (int)$datos['numero'];
            $tipo_factura = (int)$datos['tipo_factura'];
            $colaboradores_id = (int)$datos['colaboradores_id'];
            $importe = (float)$datos['importe'];
            $notas = (string)$datos['notas'];
            $fecha = (string)$datos['fecha'];
            $estado = (int)$datos['estado'];
            $vigencia_quote = (int)$datos['vigencia_quote'];
            $usuario = (int)$datos['usuario'];
            $empresa = (int)$datos['empresa'];
            $fecha_registro = (string)$datos['fecha_registro'];
            $fecha_dolar = (string)$datos['fecha_dolar'];

            $stmt->bind_param(
                "iiiiidssiiiiss",
                $cotizacion_id,
                $clientes_id,
                $numero,
                $tipo_factura,
                $colaboradores_id,
                $importe,
                $notas,
                $fecha,
                $estado,
                $vigencia_quote,
                $usuario,
                $empresa,
                $fecha_registro,
                $fecha_dolar
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conexion->commit();

            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en agregar_cotizacion_modelo: " . $e->getMessage());
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function agregar_detalle_cotizacion($datos)
    {
        $conexion = $this->connection();

        try {
            $conexion->autocommit(false);

            $cotizacion_detalle_id = mainModel::correlativo("cotizacion_detalle_id", "cotizacion_detalles");

            /*
                IMPORTANTE:
                Ahora se guarda separado:
                - isv_valor  = ISV id=1
                - isv_valor1 = ISV id=2
            */
            $stmt = $conexion->prepare("
                INSERT INTO cotizacion_detalles
                (
                    cotizacion_detalle_id,
                    cotizacion_id,
                    productos_id,
                    cantidad,
                    precio,
                    isv_valor,
                    isv_valor1,
                    descuento
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $cotizacion_detalle_id = (int)$cotizacion_detalle_id;
            $cotizacion_id = (int)$datos['cotizacion_id'];
            $productos_id = (int)$datos['productos_id'];
            $cantidad = (float)$datos['cantidad'];
            $precio = (float)$datos['precio'];
            $isv_valor = (float)$datos['isv_valor'];
            $isv_valor1 = (float)$datos['isv_valor1'];
            $descuento = (float)$datos['descuento'];

            $stmt->bind_param(
                "iiiddddd",
                $cotizacion_detalle_id,
                $cotizacion_id,
                $productos_id,
                $cantidad,
                $precio,
                $isv_valor,
                $isv_valor1,
                $descuento
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conexion->commit();

            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en agregar_detalle_cotizacion: " . $e->getMessage());
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function actualizar_detalle_cotizacion($datos)
    {
        $conexion = $this->connection();

        try {
            $conexion->autocommit(false);

            $stmt = $conexion->prepare("
                UPDATE cotizacion_detalles
                SET
                    cantidad = ?,
                    precio = ?,
                    isv_valor = ?,
                    isv_valor1 = ?,
                    descuento = ?
                WHERE cotizacion_id = ?
                AND productos_id = ?
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $cantidad = (float)$datos['cantidad'];
            $precio = (float)$datos['precio'];
            $isv_valor = (float)$datos['isv_valor'];
            $isv_valor1 = (float)$datos['isv_valor1'];
            $descuento = (float)$datos['descuento'];
            $cotizacion_id = (int)$datos['cotizacion_id'];
            $productos_id = (int)$datos['productos_id'];

            $stmt->bind_param(
                "dddddii",
                $cantidad,
                $precio,
                $isv_valor,
                $isv_valor1,
                $descuento,
                $cotizacion_id,
                $productos_id
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conexion->commit();

            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en actualizar_detalle_cotizacion: " . $e->getMessage());
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function actualizar_cotizacion_importe($datos)
    {
        $conexion = $this->connection();

        try {
            $conexion->autocommit(false);

            $stmt = $conexion->prepare("
                UPDATE cotizacion
                SET importe = ?
                WHERE cotizacion_id = ?
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $importe = (float)$datos['importe'];
            $cotizacion_id = (int)$datos['cotizacion_id'];

            $stmt->bind_param(
                "di",
                $importe,
                $cotizacion_id
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conexion->commit();

            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en actualizar_cotizacion_importe: " . $e->getMessage());
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function cancelar_cotizacion_modelo($cotizacion_id)
    {
        $conexion = $this->connection();

        try {
            $conexion->autocommit(false);

            /*
                Según tu estructura:
                estado = 1 Activa
                estado = 2 Cancelada
            */
            $estado = 2;

            $stmt = $conexion->prepare("
                UPDATE cotizacion
                SET estado = ?
                WHERE cotizacion_id = ?
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $cotizacion_id = (int)$cotizacion_id;

            $stmt->bind_param(
                "ii",
                $estado,
                $cotizacion_id
            );

            $ejecutado = $stmt->execute();

            if (!$ejecutado) {
                throw new Exception($stmt->error);
            }

            $stmt->close();
            $conexion->commit();

            return true;

        } catch (Exception $e) {
            $conexion->rollback();
            error_log("Error en cancelar_cotizacion_modelo: " . $e->getMessage());
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function validDetalleCotizacion($cotizacion_id, $productos_id)
    {
        $conexion = $this->connection();

        try {
            $stmt = $conexion->prepare("
                SELECT cotizacion_detalle_id
                FROM cotizacion_detalles
                WHERE cotizacion_id = ?
                AND productos_id = ?
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $cotizacion_id = (int)$cotizacion_id;
            $productos_id = (int)$productos_id;

            $stmt->bind_param("ii", $cotizacion_id, $productos_id);
            $stmt->execute();

            $result = $stmt->get_result();

            $stmt->close();

            return $result;

        } catch (Exception $e) {
            error_log("Error en validDetalleCotizacion: " . $e->getMessage());
            return false;
        }
    }

    protected function getISV_modelo()
    {
        return mainModel::getISV('Facturas');
    }

    protected function getISVEstadoProducto_modelo($productos_id)
    {
        return mainModel::getISVEstadoProducto($productos_id);
    }

    protected function getTotalCotizacionesRegistradas()
    {
        try {
            $conexion = $this->connection();

            $primerDiaMes = date('Y-m-01');
            $ultimoDiaMes = date('Y-m-t');

            $stmt = $conexion->prepare("
                SELECT COUNT(cotizacion_id) AS total
                FROM cotizacion
                WHERE estado = 1
                AND CAST(fecha_registro AS DATE) BETWEEN ? AND ?
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param("ss", $primerDiaMes, $ultimoDiaMes);
            $stmt->execute();

            $resultado = $stmt->get_result();
            $fila = $resultado->fetch_assoc();

            $stmt->close();

            return isset($fila['total']) ? (int)$fila['total'] : 0;

        } catch (Exception $e) {
            error_log("Error en getTotalCotizacionesRegistradas: " . $e->getMessage());
            return 0;
        }
    }
}