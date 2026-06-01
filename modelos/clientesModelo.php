<?php
if ($peticionAjax) {
    require_once "../core/mainModel.php";
} else {
    require_once "./core/mainModel.php";
}

class clientesModelo extends mainModel
{
    protected function agregar_clientes_modelo($datos)
    {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            if (trim($datos['nombre']) === '') {
                throw new Exception("Nombre vacío");
            }

            $conexion->autocommit(false);

            $cliente_id = mainModel::correlativo("clientes_id", "clientes");

            $sql = "
                INSERT INTO clientes
                (
                    clientes_id,
                    nombre,
                    rtn,
                    fecha,
                    departamentos_id,
                    municipios_id,
                    localidad,
                    telefono,
                    correo,
                    estado,
                    colaboradores_id,
                    fecha_registro,
                    empresa,
                    eslogan,
                    otra_informacion,
                    whatsapp
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '')
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param(
                "isssiisssiis",
                $cliente_id,
                $datos['nombre'],
                $datos['rtn'],
                $datos['fecha'],
                $datos['departamento_id'],
                $datos['municipio_id'],
                $datos['localidad'],
                $datos['telefono'],
                $datos['correo'],
                $datos['estado_clientes'],
                $datos['colaborador_id'],
                $datos['fecha_registro']
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $conexion->commit();

            return $cliente_id;

        } catch (Exception $e) {
            if ($conexion) {
                $conexion->rollback();
            }

            error_log("Error al insertar cliente: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }

            if ($conexion) {
                $conexion->autocommit(true);
            }
        }
    }

    protected function agregar_colaboradores_modelo($datos)
    {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $conexion->autocommit(false);

            $colaborador_id = mainModel::correlativo("colaboradores_id", "colaboradores");

            $sql = "
                INSERT INTO colaboradores
                (
                    colaboradores_id,
                    puestos_id,
                    nombre,
                    identidad,
                    estado,
                    telefono,
                    empresa_id,
                    fecha_registro,
                    fecha_ingreso,
                    fecha_egreso
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $fecha_egreso = isset($datos['fecha_egreso']) ? $datos['fecha_egreso'] : '';

            $stmt->bind_param(
                "iissiissss",
                $colaborador_id,
                $datos['puestos_id'],
                $datos['nombre'],
                $datos['identidad'],
                $datos['estado'],
                $datos['telefono'],
                $datos['empresa_id'],
                $datos['fecha_registro'],
                $datos['fecha_ingreso'],
                $fecha_egreso
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $conexion->commit();

            return $colaborador_id;

        } catch (Exception $e) {
            if ($conexion) {
                $conexion->rollback();
            }

            error_log("Error al insertar colaborador: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }

            if ($conexion) {
                $conexion->autocommit(true);
            }
        }
    }

    protected function valid_clientes_modelo($rtn)
    {
        $conexion = mainModel::connection();

        try {
            $stmt = $conexion->prepare("
                SELECT clientes_id
                FROM clientes
                WHERE rtn = ?
                LIMIT 1
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param("s", $rtn);
            $stmt->execute();

            return $stmt->get_result();

        } catch (Exception $e) {
            error_log("Error al validar cliente por RTN: " . $e->getMessage());
            return false;
        }
    }

    protected function edit_clientes_modelo($datos)
    {
        /*
            IMPORTANTE:
            No se actualiza RTN aquí.
            El RTN se debe modificar solo desde el método/botón específico que ya tenés para editar identidad/RTN.
        */

        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $sql = "
                UPDATE clientes SET
                    nombre = ?,
                    departamentos_id = ?,
                    municipios_id = ?,
                    localidad = ?,
                    telefono = ?,
                    correo = ?,
                    estado = ?
                WHERE clientes_id = ?
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $stmt->bind_param(
                "siisssii",
                $datos['nombre'],
                $datos['departamento_id'],
                $datos['municipio_id'],
                $datos['localidad'],
                $datos['telefono'],
                $datos['correo'],
                $datos['estado'],
                $datos['clientes_id']
            );

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error al actualizar cliente: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }

    protected function delete_clientes_modelo($clientes_id)
    {
        $conexion = mainModel::connection();
        $stmt = null;

        try {
            $stmt = $conexion->prepare("
                DELETE FROM clientes
                WHERE clientes_id = ?
                  AND clientes_id NOT IN (1)
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $clientes_id = (int)$clientes_id;
            $stmt->bind_param("i", $clientes_id);

            return $stmt->execute();

        } catch (Exception $e) {
            error_log("Error al eliminar cliente: " . $e->getMessage());
            return false;

        } finally {
            if ($stmt) {
                $stmt->close();
            }
        }
    }

    protected function valid_clientes_facturas_modelo($clientes_id)
    {
        $conexion = mainModel::connection();

        try {
            $stmt = $conexion->prepare("
                SELECT facturas_id
                FROM facturas
                WHERE clientes_id = ?
                LIMIT 1
            ");

            if (!$stmt) {
                throw new Exception($conexion->error);
            }

            $clientes_id = (int)$clientes_id;
            $stmt->bind_param("i", $clientes_id);
            $stmt->execute();

            return $stmt->get_result();

        } catch (Exception $e) {
            error_log("Error al validar facturas del cliente: " . $e->getMessage());
            return false;
        }
    }

    protected function getTotalClientesRegistrados()
    {
        $conexion = mainModel::connection();

        try {
            $query = "
                SELECT COUNT(clientes_id) AS total
                FROM clientes
                WHERE estado = 1
            ";

            $resultado = $conexion->query($query);

            if (!$resultado) {
                throw new Exception("Error al contar clientes: " . $conexion->error);
            }

            $fila = $resultado->fetch_assoc();

            return (int)$fila['total'];

        } catch (Exception $e) {
            error_log("Error en getTotalClientesRegistrados: " . $e->getMessage());
            return 0;
        }
    }
}