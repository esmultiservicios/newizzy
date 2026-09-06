<?php
// core/AsignarPlanes/AsignarPlanesSyncHelper.php

class AsignarPlanesSyncHelper {

    public static function responder($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function conectarPrincipal($mainModel) {
        if (defined('DB_MAIN') && method_exists($mainModel, 'connectToDatabase')) {
            return $mainModel->connectToDatabase([
                "host" => SERVER,
                "user" => USER,
                "pass" => PASS,
                "name" => DB_MAIN
            ]);
        }

        return $mainModel->connection();
    }

    public static function conectarCliente($mainModel, $dbName) {
        $dbName = trim((string)$dbName);

        if ($dbName === "" || !self::validarDbName($dbName)) {
            return null;
        }

        if (!method_exists($mainModel, "connectToDatabase")) {
            return null;
        }

        return $mainModel->connectToDatabase([
            "host" => SERVER,
            "user" => USER,
            "pass" => PASS,
            "name" => $dbName
        ]);
    }

    public static function validarDbName($dbName) {
        return preg_match('/^[a-zA-Z0-9_]+$/', trim((string)$dbName)) === 1;
    }

    public static function tablaExiste($conexion, $tabla) {
        $tabla = trim((string)$tabla);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            return false;
        }

        $tabla = $conexion->real_escape_string($tabla);

        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tabla}'
            LIMIT 1
        ";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            return false;
        }

        $row = $resultado->fetch_assoc();

        return isset($row["total"]) && (int)$row["total"] > 0;
    }

    public static function columnaExiste($conexion, $tabla, $columna) {
        $tabla = trim((string)$tabla);
        $columna = trim((string)$columna);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
            return false;
        }

        $tabla = $conexion->real_escape_string($tabla);
        $columna = $conexion->real_escape_string($columna);

        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tabla}'
              AND COLUMN_NAME = '{$columna}'
            LIMIT 1
        ";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            return false;
        }

        $row = $resultado->fetch_assoc();

        return isset($row["total"]) && (int)$row["total"] > 0;
    }

    public static function validarPlanCliente($conexionCliente) {
        if (!self::tablaExiste($conexionCliente, "plan")) {
            throw new Exception("La tabla plan no existe en la base del cliente.");
        }

        $columnas = ["plan_id", "planes_id", "user_extra", "fecha_registro"];

        foreach ($columnas as $columna) {
            if (!self::columnaExiste($conexionCliente, "plan", $columna)) {
                throw new Exception("La columna {$columna} no existe en la tabla plan del cliente.");
            }
        }

        return true;
    }

    public static function validarTablasPermisosCliente($conexionCliente) {
        $tablas = ["planes", "menu_plan", "submenu_plan", "submenu1_plan"];

        foreach ($tablas as $tabla) {
            if (!self::tablaExiste($conexionCliente, $tabla)) {
                throw new Exception("La tabla {$tabla} no existe en la base del cliente.");
            }
        }

        return true;
    }

    public static function validarPlanActivoPrincipal($conexionPrincipal, $planesId) {
        $sql = "
            SELECT planes_id
            FROM planes
            WHERE planes_id = ?
              AND estado = 1
            LIMIT 1
        ";

        $stmt = $conexionPrincipal->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la validación del plan: " . $conexionPrincipal->error);
        }

        $stmt->bind_param("i", $planesId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function obtenerServerCustomer($conexionPrincipal, $serverCustomersId, $clienteId) {
        $sql = "
            SELECT
                server_customers_id,
                clientes_id,
                codigo_cliente,
                db,
                planes_id,
                sistema_id,
                validar,
                estado,
                db_imported
            FROM server_customers
            WHERE server_customers_id = ?
              AND clientes_id = ?
            LIMIT 1
        ";

        $stmt = $conexionPrincipal->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la consulta de server_customers: " . $conexionPrincipal->error);
        }

        $stmt->bind_param("ii", $serverCustomersId, $clienteId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            throw new Exception("No se encontró el cliente en server_customers.");
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        return $row;
    }

    public static function actualizarServerCustomer($conexion, $serverCustomersId, $clienteId, $planesId, $validar, $estado, $obligatorio = false) {
        if (!self::tablaExiste($conexion, "server_customers")) {
            if ($obligatorio) {
                throw new Exception("La tabla server_customers no existe en la base principal.");
            }
            return true;
        }

        $sql = "
            UPDATE server_customers SET
                planes_id = ?,
                validar = ?,
                estado = ?
            WHERE server_customers_id = ?
              AND clientes_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la actualización de server_customers: " . $conexion->error);
        }

        $stmt->bind_param("iiiii", $planesId, $validar, $estado, $serverCustomersId, $clienteId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo actualizar server_customers: " . $error);
        }

        $stmt->close();

        if ($obligatorio) {
            self::verificarServerCustomerActualizado(
                $conexion,
                $serverCustomersId,
                $clienteId,
                $planesId,
                $validar,
                $estado
            );
        }

        return true;
    }

    public static function verificarServerCustomerActualizado($conexion, $serverCustomersId, $clienteId, $planesId, $validar, $estado) {
        $sql = "
            SELECT planes_id, validar, estado
            FROM server_customers
            WHERE server_customers_id = ?
              AND clientes_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la verificación de server_customers: " . $conexion->error);
        }

        $stmt->bind_param("ii", $serverCustomersId, $clienteId);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            throw new Exception("No se pudo verificar el registro actualizado en server_customers.");
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        if (
            (int)$row["planes_id"] !== (int)$planesId ||
            (int)$row["validar"] !== (int)$validar ||
            (int)$row["estado"] !== (int)$estado
        ) {
            throw new Exception("La base principal no confirmó los nuevos datos del plan.");
        }

        return true;
    }

    public static function verificarPlanTablaClienteActualizado($conexionCliente, $planesId, $userExtra) {
        $sql = "
            SELECT planes_id, IFNULL(user_extra, 0) AS user_extra
            FROM plan
            WHERE plan_id = 1
            LIMIT 1
        ";

        $stmt = $conexionCliente->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la verificación del plan del cliente: " . $conexionCliente->error);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            throw new Exception("No se pudo verificar el plan guardado en la base del cliente.");
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        if (
            (int)$row["planes_id"] !== (int)$planesId ||
            (int)$row["user_extra"] !== (int)$userExtra
        ) {
            throw new Exception("La base del cliente no confirmó el nuevo plan o los usuarios extra.");
        }

        return true;
    }

    public static function actualizarPlanTablaCliente($conexionCliente, $planesId, $userExtra) {
        self::validarPlanCliente($conexionCliente);

        $sqlCheck = "
            SELECT plan_id
            FROM plan
            WHERE plan_id = 1
            LIMIT 1
        ";

        $stmtCheck = $conexionCliente->prepare($sqlCheck);

        if (!$stmtCheck) {
            throw new Exception("No se pudo preparar la validación de plan del cliente: " . $conexionCliente->error);
        }

        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();
        $existe = $resultado && $resultado->num_rows > 0;
        $stmtCheck->close();

        if ($existe) {
            $sql = "
                UPDATE plan SET
                    planes_id = ?,
                    user_extra = ?,
                    fecha_registro = NOW()
                WHERE plan_id = 1
                LIMIT 1
            ";

            $stmt = $conexionCliente->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar la actualización de plan del cliente: " . $conexionCliente->error);
            }

            $stmt->bind_param("ii", $planesId, $userExtra);

        } else {
            $sql = "
                INSERT INTO plan (
                    plan_id,
                    planes_id,
                    user_extra,
                    fecha_registro
                ) VALUES (
                    1,
                    ?,
                    ?,
                    NOW()
                )
            ";

            $stmt = $conexionCliente->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar el registro de plan del cliente: " . $conexionCliente->error);
            }

            $stmt->bind_param("ii", $planesId, $userExtra);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo guardar el plan en la base del cliente: " . $error);
        }

        $stmt->close();

        return true;
    }

    public static function sincronizarPlanCatalogoCliente($conexionPrincipal, $conexionCliente, $planesId) {
        self::validarTablasPermisosCliente($conexionCliente);

        $sql = "
            SELECT
                planes_id,
                nombre,
                configuraciones,
                estado,
                fecha_registro
            FROM planes
            WHERE planes_id = ?
            LIMIT 1
        ";

        $stmt = $conexionPrincipal->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar consulta del plan principal: " . $conexionPrincipal->error);
        }

        $stmt->bind_param("i", $planesId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            throw new Exception("El plan no existe en la base principal.");
        }

        $plan = $resultado->fetch_assoc();
        $stmt->close();

        $sqlCheck = "
            SELECT planes_id
            FROM planes
            WHERE planes_id = ?
            LIMIT 1
        ";

        $stmtCheck = $conexionCliente->prepare($sqlCheck);

        if (!$stmtCheck) {
            throw new Exception("No se pudo validar el plan en cliente: " . $conexionCliente->error);
        }

        $stmtCheck->bind_param("i", $planesId);
        $stmtCheck->execute();

        $resCheck = $stmtCheck->get_result();
        $existe = $resCheck && $resCheck->num_rows > 0;
        $stmtCheck->close();

        $nombre = $plan["nombre"];
        $configuraciones = $plan["configuraciones"];
        $estado = (int)$plan["estado"];
        $fechaRegistro = $plan["fecha_registro"];

        if ($existe) {
            $sqlUpdate = "
                UPDATE planes SET
                    nombre = ?,
                    configuraciones = ?,
                    estado = ?
                WHERE planes_id = ?
            ";

            $stmtUpdate = $conexionCliente->prepare($sqlUpdate);

            if (!$stmtUpdate) {
                throw new Exception("No se pudo preparar actualización del plan en cliente: " . $conexionCliente->error);
            }

            $stmtUpdate->bind_param("ssii", $nombre, $configuraciones, $estado, $planesId);

            if (!$stmtUpdate->execute()) {
                $error = $stmtUpdate->error;
                $stmtUpdate->close();
                throw new Exception("No se pudo actualizar el plan en cliente: " . $error);
            }

            $stmtUpdate->close();

        } else {
            $sqlInsert = "
                INSERT INTO planes (
                    planes_id,
                    nombre,
                    configuraciones,
                    estado,
                    fecha_registro
                ) VALUES (?, ?, ?, ?, ?)
            ";

            $stmtInsert = $conexionCliente->prepare($sqlInsert);

            if (!$stmtInsert) {
                throw new Exception("No se pudo preparar registro del plan en cliente: " . $conexionCliente->error);
            }

            $stmtInsert->bind_param("issis", $planesId, $nombre, $configuraciones, $estado, $fechaRegistro);

            if (!$stmtInsert->execute()) {
                $error = $stmtInsert->error;
                $stmtInsert->close();
                throw new Exception("No se pudo registrar el plan en cliente: " . $error);
            }

            $stmtInsert->close();
        }

        return true;
    }

    public static function obtenerMapaAsignacionesPlanPrincipal(
        $conexionPrincipal,
        $tabla,
        $itemField,
        $planesId
    ) {
        $sql = "
            SELECT
                {$itemField},
                estado
            FROM {$tabla}
            WHERE planes_id = ?
        ";

        $stmt = $conexionPrincipal->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo preparar la consulta de {$tabla} en DB_MAIN: " .
                $conexionPrincipal->error
            );
        }

        $stmt->bind_param("i", $planesId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $mapa = [];

        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) {
                $itemId = (int)$row[$itemField];

                if ($itemId <= 0) {
                    continue;
                }

                /*
                 * Estado oficial del permiso:
                 * 1 = Mostrar
                 * 0 = Ocultar
                 */
                $estado = ((int)$row["estado"] === 1) ? 1 : 0;

                /*
                 * Si por un error histórico existiera más de una fila lógica
                 * en DB_MAIN, la última lectura deja un solo estado efectivo.
                 */
                $mapa[$itemId] = $estado;
            }
        }

        $stmt->close();

        return $mapa;
    }

    public static function columnaEsAutoIncrement($conexion, $tabla, $columna) {
        $sql = "
            SELECT EXTRA
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo validar AUTO_INCREMENT de {$tabla}.{$columna}: " .
                $conexion->error
            );
        }

        $stmt->bind_param("ss", $tabla, $columna);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $esAutoIncrement = false;

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();
            $extra = strtolower((string)($row["EXTRA"] ?? ""));
            $esAutoIncrement = strpos($extra, "auto_increment") !== false;
        }

        $stmt->close();

        return $esAutoIncrement;
    }

    public static function guardarAsignacionPlanCliente(
        $conexionCliente,
        $tabla,
        $idField,
        $itemField,
        $itemId,
        $planesId,
        $estado
    ) {
        $estado = ((int)$estado === 1) ? 1 : 0;

        /*
         * La identidad funcional NO es el PK local.
         *
         * Es:
         *      item + plan
         *
         * Por eso jamás copiamos menu_plan_id / submenu_plan_id /
         * submenu1_plan_id desde DB_MAIN.
         */
        $sqlCheck = "
            SELECT {$idField}
            FROM {$tabla}
            WHERE {$itemField} = ?
              AND planes_id = ?
            LIMIT 1
        ";

        $stmtCheck = $conexionCliente->prepare($sqlCheck);

        if (!$stmtCheck) {
            throw new Exception(
                "No se pudo validar el permiso en {$tabla}: " .
                $conexionCliente->error
            );
        }

        $stmtCheck->bind_param("ii", $itemId, $planesId);
        $stmtCheck->execute();

        $resultado = $stmtCheck->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmtCheck->close();

        if ($existe) {
            /*
             * Se actualizan TODAS las coincidencias lógicas por si alguna
             * instalación antigua tiene duplicados item + plan.
             */
            $sqlUpdate = "
                UPDATE {$tabla}
                SET estado = ?
                WHERE {$itemField} = ?
                  AND planes_id = ?
            ";

            $stmtUpdate = $conexionCliente->prepare($sqlUpdate);

            if (!$stmtUpdate) {
                throw new Exception(
                    "No se pudo preparar la actualización de {$tabla}: " .
                    $conexionCliente->error
                );
            }

            $stmtUpdate->bind_param("iii", $estado, $itemId, $planesId);

            if (!$stmtUpdate->execute()) {
                $error = $stmtUpdate->error;
                $stmtUpdate->close();

                throw new Exception(
                    "No se pudo actualizar el permiso en {$tabla}: " .
                    $error
                );
            }

            $stmtUpdate->close();

            return true;
        }

        /*
         * Las tablas actuales usan AUTO_INCREMENT.
         * El INSERT no incluye el PK: MySQL genera un ID local libre.
         */
        if (self::columnaEsAutoIncrement($conexionCliente, $tabla, $idField)) {
            $sqlInsert = "
                INSERT INTO {$tabla} (
                    {$itemField},
                    planes_id,
                    estado
                ) VALUES (?, ?, ?)
            ";

            $stmtInsert = $conexionCliente->prepare($sqlInsert);

            if (!$stmtInsert) {
                throw new Exception(
                    "No se pudo preparar la inserción en {$tabla}: " .
                    $conexionCliente->error
                );
            }

            $stmtInsert->bind_param("iii", $itemId, $planesId, $estado);

        } else {
            /*
             * Compatibilidad con instalaciones antiguas que no tengan
             * AUTO_INCREMENT. El ID se genera dentro de la base cliente.
             */
            $sqlInsert = "
                INSERT INTO {$tabla} (
                    {$idField},
                    {$itemField},
                    planes_id,
                    estado
                )
                SELECT
                    COALESCE(MAX({$idField}), 0) + 1,
                    ?,
                    ?,
                    ?
                FROM {$tabla}
            ";

            $stmtInsert = $conexionCliente->prepare($sqlInsert);

            if (!$stmtInsert) {
                throw new Exception(
                    "No se pudo preparar la inserción compatible en {$tabla}: " .
                    $conexionCliente->error
                );
            }

            $stmtInsert->bind_param("iii", $itemId, $planesId, $estado);
        }

        if (!$stmtInsert->execute()) {
            $error = $stmtInsert->error;
            $stmtInsert->close();

            throw new Exception(
                "No se pudo registrar el permiso en {$tabla}: " .
                $error
            );
        }

        $stmtInsert->close();

        return true;
    }

    public static function desactivarAsignacionesObsoletasPlanCliente(
        $conexionCliente,
        $tabla,
        $itemField,
        $planesId,
        $itemsValidos
    ) {
        $itemsValidos = array_values(
            array_unique(
                array_filter(
                    array_map("intval", (array)$itemsValidos),
                    function($id) {
                        return $id > 0;
                    }
                )
            )
        );

        /*
         * Si DB_MAIN no tiene relaciones para ese plan, todo lo que exista
         * en la base cliente para ese plan debe quedar oculto.
         */
        if (empty($itemsValidos)) {
            $sql = "
                UPDATE {$tabla}
                SET estado = 0
                WHERE planes_id = ?
            ";

            $stmt = $conexionCliente->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "No se pudo preparar la limpieza lógica de {$tabla}: " .
                    $conexionCliente->error
                );
            }

            $stmt->bind_param("i", $planesId);

            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();

                throw new Exception(
                    "No se pudieron ocultar permisos obsoletos de {$tabla}: " .
                    $error
                );
            }

            $stmt->close();

            return true;
        }

        /*
         * Primero sincronizamos todo lo válido y SOLO al final ocultamos
         * relaciones que ya no existen en DB_MAIN.
         *
         * Esto es importante porque estas tablas históricamente son MyISAM:
         * no podemos depender de rollback para deshacer un cambio parcial.
         */
        $placeholders = implode(",", array_fill(0, count($itemsValidos), "?"));

        $sql = "
            UPDATE {$tabla}
            SET estado = 0
            WHERE planes_id = ?
              AND {$itemField} NOT IN ({$placeholders})
        ";

        $stmt = $conexionCliente->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "No se pudo preparar la depuración lógica de {$tabla}: " .
                $conexionCliente->error
            );
        }

        $tipos = "i" . str_repeat("i", count($itemsValidos));
        $valores = array_merge([$planesId], $itemsValidos);

        $params = [];
        $params[] = $tipos;

        foreach ($valores as $key => $valor) {
            $params[] = &$valores[$key];
        }

        call_user_func_array([$stmt, "bind_param"], $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();

            throw new Exception(
                "No se pudieron ocultar permisos obsoletos de {$tabla}: " .
                $error
            );
        }

        $stmt->close();

        return true;
    }

    public static function verificarAsignacionesPlanCliente(
        $conexionPrincipal,
        $conexionCliente,
        $planesId
    ) {
        $mapas = [
            [
                "tabla" => "menu_plan",
                "item" => "menu_id"
            ],
            [
                "tabla" => "submenu_plan",
                "item" => "submenu_id"
            ],
            [
                "tabla" => "submenu1_plan",
                "item" => "submenu1_id"
            ]
        ];

        foreach ($mapas as $mapa) {
            $tabla = $mapa["tabla"];
            $itemField = $mapa["item"];

            $mapaPrincipal = self::obtenerMapaAsignacionesPlanPrincipal(
                $conexionPrincipal,
                $tabla,
                $itemField,
                $planesId
            );

            foreach ($mapaPrincipal as $itemId => $estadoEsperado) {
                $sqlCliente = "
                    SELECT
                        COUNT(*) AS total,
                        MIN(estado) AS estado_min,
                        MAX(estado) AS estado_max
                    FROM {$tabla}
                    WHERE {$itemField} = ?
                      AND planes_id = ?
                ";

                $stmtCliente = $conexionCliente->prepare($sqlCliente);

                if (!$stmtCliente) {
                    throw new Exception(
                        "No se pudo preparar la verificación de {$tabla}: " .
                        $conexionCliente->error
                    );
                }

                $stmtCliente->bind_param("ii", $itemId, $planesId);
                $stmtCliente->execute();

                $resultadoCliente = $stmtCliente->get_result();
                $rowCliente = $resultadoCliente
                    ? $resultadoCliente->fetch_assoc()
                    : null;

                $stmtCliente->close();

                if (!$rowCliente || (int)$rowCliente["total"] <= 0) {
                    throw new Exception(
                        "La sincronización de {$tabla} quedó incompleta. " .
                        "Falta {$itemField}={$itemId} para el plan {$planesId}."
                    );
                }

                /*
                 * Si hubiera duplicados lógicos antiguos, todos deben terminar
                 * con el mismo estado.
                 */
                if (
                    (int)$rowCliente["estado_min"] !== (int)$estadoEsperado ||
                    (int)$rowCliente["estado_max"] !== (int)$estadoEsperado
                ) {
                    throw new Exception(
                        "La sincronización de {$tabla} no confirmó el estado de " .
                        "{$itemField}={$itemId}."
                    );
                }
            }
        }

        return true;
    }

    public static function copiarAsignacionesPlanCliente(
        $conexionPrincipal,
        $conexionCliente,
        $planesId
    ) {
        self::validarTablasPermisosCliente($conexionCliente);

        /*
         * Sincronización idempotente:
         *
         * 1. Lee DB_MAIN.
         * 2. Si la relación ya existe en cliente, actualiza estado.
         * 3. Si no existe, inserta SIN copiar el PK de DB_MAIN.
         * 4. Al final oculta lo que ya no existe en DB_MAIN.
         *
         * Estado:
         * 1 = Mostrar
         * 0 = Ocultar
         *
         * No se usa DELETE.
         */
        $mapas = [
            [
                "tabla" => "menu_plan",
                "id" => "menu_plan_id",
                "item" => "menu_id"
            ],
            [
                "tabla" => "submenu_plan",
                "id" => "submenu_plan_id",
                "item" => "submenu_id"
            ],
            [
                "tabla" => "submenu1_plan",
                "id" => "submenu1_plan_id",
                "item" => "submenu1_id"
            ]
        ];

        foreach ($mapas as $mapa) {
            $tabla = $mapa["tabla"];
            $idField = $mapa["id"];
            $itemField = $mapa["item"];

            $mapaPrincipal = self::obtenerMapaAsignacionesPlanPrincipal(
                $conexionPrincipal,
                $tabla,
                $itemField,
                $planesId
            );

            /*
             * Primero se escribe/actualiza lo válido.
             * En MyISAM esto reduce el riesgo de dejar todo oculto si una
             * operación posterior falla.
             */
            foreach ($mapaPrincipal as $itemId => $estado) {
                self::guardarAsignacionPlanCliente(
                    $conexionCliente,
                    $tabla,
                    $idField,
                    $itemField,
                    (int)$itemId,
                    $planesId,
                    $estado
                );
            }

            /*
             * Después se ocultan las relaciones que ya no forman parte del
             * catálogo oficial del plan.
             */
            self::desactivarAsignacionesObsoletasPlanCliente(
                $conexionCliente,
                $tabla,
                $itemField,
                $planesId,
                array_keys($mapaPrincipal)
            );
        }

        self::verificarAsignacionesPlanCliente(
            $conexionPrincipal,
            $conexionCliente,
            $planesId
        );

        return true;
    }

}