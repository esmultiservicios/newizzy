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

    public static function actualizarServerCustomer($conexion, $serverCustomersId, $clienteId, $planesId, $validar, $estado) {
        if (!self::tablaExiste($conexion, "server_customers")) {
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

    public static function limpiarAsignacionesPlanCliente($conexionCliente, $planesId) {
        $tablas = [
            ["tabla" => "menu_plan", "campo" => "planes_id"],
            ["tabla" => "submenu_plan", "campo" => "planes_id"],
            ["tabla" => "submenu1_plan", "campo" => "planes_id"]
        ];

        foreach ($tablas as $item) {
            $sql = "DELETE FROM {$item["tabla"]} WHERE {$item["campo"]} = ?";
            $stmt = $conexionCliente->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar limpieza de {$item["tabla"]}: " . $conexionCliente->error);
            }

            $stmt->bind_param("i", $planesId);

            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                throw new Exception("No se pudo limpiar {$item["tabla"]}: " . $error);
            }

            $stmt->close();
        }

        return true;
    }

    public static function copiarAsignacionesPlanCliente($conexionPrincipal, $conexionCliente, $planesId) {
        self::validarTablasPermisosCliente($conexionCliente);
        self::limpiarAsignacionesPlanCliente($conexionCliente, $planesId);

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

            $sqlSelect = "
                SELECT
                    {$idField},
                    {$itemField},
                    planes_id,
                    estado
                FROM {$tabla}
                WHERE planes_id = ?
            ";

            $stmtSelect = $conexionPrincipal->prepare($sqlSelect);

            if (!$stmtSelect) {
                throw new Exception("No se pudo preparar consulta de {$tabla}: " . $conexionPrincipal->error);
            }

            $stmtSelect->bind_param("i", $planesId);
            $stmtSelect->execute();

            $resultado = $stmtSelect->get_result();

            if ($resultado) {
                while ($row = $resultado->fetch_assoc()) {
                    $id = (int)$row[$idField];
                    $itemId = (int)$row[$itemField];
                    $estado = (int)$row["estado"];

                    $sqlInsert = "
                        INSERT INTO {$tabla} (
                            {$idField},
                            {$itemField},
                            planes_id,
                            estado
                        ) VALUES (?, ?, ?, ?)
                    ";

                    $stmtInsert = $conexionCliente->prepare($sqlInsert);

                    if (!$stmtInsert) {
                        $stmtSelect->close();
                        throw new Exception("No se pudo preparar inserción en {$tabla}: " . $conexionCliente->error);
                    }

                    $stmtInsert->bind_param("iiii", $id, $itemId, $planesId, $estado);

                    if (!$stmtInsert->execute()) {
                        $error = $stmtInsert->error;
                        $stmtInsert->close();
                        $stmtSelect->close();
                        throw new Exception("No se pudo insertar permiso en {$tabla}: " . $error);
                    }

                    $stmtInsert->close();
                }
            }

            $stmtSelect->close();
        }

        return true;
    }
}