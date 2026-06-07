<?php
// core/planes/PlanesSyncHelper.php

class PlanesSyncHelper {

    public static function responder($data) {
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function respuesta($type, $title, $message, $extra = []) {
        self::responder(array_merge([
            "type" => $type,
            "title" => $title,
            "message" => $message
        ], $extra));
    }

    public static function normalizarEstado($estado) {
        return ((int)$estado === 1) ? 1 : 0;
    }

    public static function validarDbName($dbName) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
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
            return false;
        }

        if (!method_exists($mainModel, "connectToDatabase")) {
            return false;
        }

        return $mainModel->connectToDatabase([
            "host" => SERVER,
            "user" => USER,
            "pass" => PASS,
            "name" => $dbName
        ]);
    }

    public static function tablaExiste($conexion, $tabla) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $tabla);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado) {
            $stmt->close();
            return false;
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        return isset($row["total"]) && (int)$row["total"] > 0;
    }

    public static function columnaExiste($conexion, $tabla, $columna) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $tabla, $columna);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado) {
            $stmt->close();
            return false;
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        return isset($row["total"]) && (int)$row["total"] > 0;
    }

    public static function obtenerSiguienteId($conexion, $tabla, $campoId) {
        $tablasPermitidas = [
            "planes",
            "menu_plan",
            "submenu_plan",
            "submenu1_plan"
        ];

        $camposPermitidos = [
            "planes_id",
            "menu_plan_id",
            "submenu_plan_id",
            "submenu1_plan_id"
        ];

        if (!in_array($tabla, $tablasPermitidas, true)) {
            throw new Exception("Tabla no permitida para correlativo.");
        }

        if (!in_array($campoId, $camposPermitidos, true)) {
            throw new Exception("Campo no permitido para correlativo.");
        }

        $sql = "SELECT COALESCE(MAX({$campoId}), 0) + 1 AS siguiente FROM {$tabla}";
        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("No se pudo obtener el correlativo de {$tabla}: " . $conexion->error);
        }

        $row = $resultado->fetch_assoc();

        return (int)$row["siguiente"];
    }

    public static function obtenerBasesClientesActivas($conexionPrincipal) {
        $bases = [];

        $sql = "
            SELECT db
            FROM server_customers
            WHERE estado = 1
              AND db IS NOT NULL
              AND TRIM(db) != ''
            ORDER BY server_customers_id ASC
        ";

        $stmt = $conexionPrincipal->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar consulta de bases activas: " . $conexionPrincipal->error);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado) {
            while ($row = $resultado->fetch_assoc()) {
                $db = trim((string)$row["db"]);

                if ($db !== "" && self::validarDbName($db)) {
                    $bases[] = $db;
                }
            }
        }

        $stmt->close();

        return array_values(array_unique($bases));
    }

    public static function normalizarConfiguraciones($configuracionesJson) {
        if ($configuracionesJson === null || trim((string)$configuracionesJson) === "") {
            return null;
        }

        $configArray = json_decode($configuracionesJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($configArray)) {
            throw new Exception("El formato de configuraciones no es válido.");
        }

        $configLimpia = [];

        foreach ($configArray as $clave => $valor) {
            $clave = trim((string)$clave);

            if ($clave === "") {
                continue;
            }

            $configLimpia[$clave] = is_numeric($valor) ? (int)$valor : trim((string)$valor);
        }

        if (empty($configLimpia)) {
            return null;
        }

        return json_encode($configLimpia, JSON_UNESCAPED_UNICODE);
    }

    public static function asegurarEstructuraPlanes($conexion) {
        if (!self::tablaExiste($conexion, "planes")) {
            $sqlCreate = "
                CREATE TABLE planes (
                    planes_id int NOT NULL,
                    nombre char(40) COLLATE utf8mb4_spanish_ci NOT NULL,
                    configuraciones longtext COLLATE utf8mb4_spanish_ci NULL,
                    estado int NOT NULL DEFAULT 1 COMMENT '1. Mostrar 0. Ocultar',
                    fecha_registro timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci COMMENT='Tabla de planes del sistema'
            ";

            if (!$conexion->query($sqlCreate)) {
                throw new Exception("No se pudo crear la tabla planes: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, "planes", "planes_id")) {
            if (!$conexion->query("ALTER TABLE planes ADD planes_id int NOT NULL FIRST")) {
                throw new Exception("No se pudo agregar planes_id en planes: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, "planes", "nombre")) {
            if (!$conexion->query("ALTER TABLE planes ADD nombre char(40) COLLATE utf8mb4_spanish_ci NOT NULL AFTER planes_id")) {
                throw new Exception("No se pudo agregar nombre en planes: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, "planes", "configuraciones")) {
            if (!$conexion->query("ALTER TABLE planes ADD configuraciones longtext COLLATE utf8mb4_spanish_ci NULL AFTER nombre")) {
                throw new Exception("No se pudo agregar configuraciones en planes: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, "planes", "estado")) {
            if (!$conexion->query("ALTER TABLE planes ADD estado int NOT NULL DEFAULT 1 COMMENT '1. Mostrar 0. Ocultar' AFTER configuraciones")) {
                throw new Exception("No se pudo agregar estado en planes: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, "planes", "fecha_registro")) {
            if (!$conexion->query("ALTER TABLE planes ADD fecha_registro timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER estado")) {
                throw new Exception("No se pudo agregar fecha_registro en planes: " . $conexion->error);
            }
        }

        return true;
    }

    public static function asegurarEstructuraAsignacion($conexion, $tabla) {
        $map = [
            "menu_plan" => [
                "id" => "menu_plan_id",
                "item" => "menu_id"
            ],
            "submenu_plan" => [
                "id" => "submenu_plan_id",
                "item" => "submenu_id"
            ],
            "submenu1_plan" => [
                "id" => "submenu1_plan_id",
                "item" => "submenu1_id"
            ]
        ];

        if (!isset($map[$tabla])) {
            throw new Exception("Tabla de asignación no permitida.");
        }

        $idField = $map[$tabla]["id"];
        $itemField = $map[$tabla]["item"];

        if (!self::tablaExiste($conexion, $tabla)) {
            $sqlCreate = "
                CREATE TABLE {$tabla} (
                    {$idField} int NOT NULL,
                    {$itemField} int NOT NULL,
                    planes_id int NOT NULL,
                    estado int NOT NULL DEFAULT 1
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci
            ";

            if (!$conexion->query($sqlCreate)) {
                throw new Exception("No se pudo crear la tabla {$tabla}: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, $tabla, $idField)) {
            if (!$conexion->query("ALTER TABLE {$tabla} ADD {$idField} int NOT NULL FIRST")) {
                throw new Exception("No se pudo agregar {$idField} en {$tabla}: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, $tabla, $itemField)) {
            if (!$conexion->query("ALTER TABLE {$tabla} ADD {$itemField} int NOT NULL AFTER {$idField}")) {
                throw new Exception("No se pudo agregar {$itemField} en {$tabla}: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, $tabla, "planes_id")) {
            if (!$conexion->query("ALTER TABLE {$tabla} ADD planes_id int NOT NULL AFTER {$itemField}")) {
                throw new Exception("No se pudo agregar planes_id en {$tabla}: " . $conexion->error);
            }
        }

        if (!self::columnaExiste($conexion, $tabla, "estado")) {
            if (!$conexion->query("ALTER TABLE {$tabla} ADD estado int NOT NULL DEFAULT 1 AFTER planes_id")) {
                throw new Exception("No se pudo agregar estado en {$tabla}: " . $conexion->error);
            }
        }

        return true;
    }

    public static function existePlanPorId($conexion, $planId) {
        if (!self::tablaExiste($conexion, "planes")) {
            return false;
        }

        $sql = "SELECT planes_id FROM planes WHERE planes_id = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar validación de plan: " . $conexion->error);
        }

        $planId = (int)$planId;
        $stmt->bind_param("i", $planId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function existePlanDuplicado($conexion, $nombre, $planIdExcluir = 0) {
        if (!self::tablaExiste($conexion, "planes")) {
            return false;
        }

        if ((int)$planIdExcluir > 0) {
            $sql = "
                SELECT planes_id
                FROM planes
                WHERE nombre = ?
                  AND planes_id <> ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar validación de duplicado: " . $conexion->error);
            }

            $planIdExcluir = (int)$planIdExcluir;
            $stmt->bind_param("si", $nombre, $planIdExcluir);

        } else {
            $sql = "
                SELECT planes_id
                FROM planes
                WHERE nombre = ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar validación de duplicado: " . $conexion->error);
            }

            $stmt->bind_param("s", $nombre);
        }

        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function insertarPlan($conexion, $planId, $nombre, $estado, $fechaRegistro, $configuraciones) {
        self::asegurarEstructuraPlanes($conexion);

        $sql = "
            INSERT INTO planes (
                planes_id,
                nombre,
                estado,
                fecha_registro,
                configuraciones
            ) VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar el registro del plan: " . $conexion->error);
        }

        $stmt->bind_param("isiss", $planId, $nombre, $estado, $fechaRegistro, $configuraciones);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo registrar el plan: " . $error);
        }

        $stmt->close();

        return true;
    }

    public static function actualizarPlan($conexion, $planId, $nombre, $estado, $configuraciones) {
        self::asegurarEstructuraPlanes($conexion);

        $sql = "
            UPDATE planes SET
                nombre = ?,
                estado = ?,
                configuraciones = ?
            WHERE planes_id = ?
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la actualización del plan: " . $conexion->error);
        }

        $stmt->bind_param("sisi", $nombre, $estado, $configuraciones, $planId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo actualizar el plan: " . $error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    public static function upsertPlanCliente($conexion, $planId, $nombre, $estado, $fechaRegistro, $configuraciones) {
        self::asegurarEstructuraPlanes($conexion);

        if (self::existePlanPorId($conexion, $planId)) {
            self::actualizarPlan($conexion, $planId, $nombre, $estado, $configuraciones);
        } else {
            self::insertarPlan($conexion, $planId, $nombre, $estado, $fechaRegistro, $configuraciones);
        }

        return true;
    }

    public static function eliminarPlan($conexion, $planId) {
        if (!self::tablaExiste($conexion, "planes")) {
            return 0;
        }

        $tablasRelacion = [
            ["tabla" => "menu_plan", "campo" => "planes_id"],
            ["tabla" => "submenu_plan", "campo" => "planes_id"],
            ["tabla" => "submenu1_plan", "campo" => "planes_id"]
        ];

        foreach ($tablasRelacion as $relacion) {
            if (self::tablaExiste($conexion, $relacion["tabla"])) {
                $sql = "DELETE FROM {$relacion["tabla"]} WHERE {$relacion["campo"]} = ?";
                $stmt = $conexion->prepare($sql);

                if (!$stmt) {
                    throw new Exception("No se pudo preparar limpieza de {$relacion["tabla"]}: " . $conexion->error);
                }

                $stmt->bind_param("i", $planId);

                if (!$stmt->execute()) {
                    $error = $stmt->error;
                    $stmt->close();
                    throw new Exception("No se pudo limpiar {$relacion["tabla"]}: " . $error);
                }

                $stmt->close();
            }
        }

        $sql = "DELETE FROM planes WHERE planes_id = ?";
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar eliminación del plan: " . $conexion->error);
        }

        $stmt->bind_param("i", $planId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo eliminar el plan: " . $error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    public static function obtenerConfiguracionesPlan($conexion, $planId) {
        if (!self::tablaExiste($conexion, "planes")) {
            return null;
        }

        $sql = "
            SELECT configuraciones
            FROM planes
            WHERE planes_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar consulta de configuraciones: " . $conexion->error);
        }

        $stmt->bind_param("i", $planId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            return null;
        }

        $row = $resultado->fetch_assoc();
        $stmt->close();

        return $row["configuraciones"];
    }

    public static function actualizarConfiguracionesPlan($conexion, $planId, $configuraciones) {
        self::asegurarEstructuraPlanes($conexion);

        $sql = "
            UPDATE planes SET
                configuraciones = ?
            WHERE planes_id = ?
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar actualización de configuraciones: " . $conexion->error);
        }

        $stmt->bind_param("si", $configuraciones, $planId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudieron actualizar las configuraciones: " . $error);
        }

        $stmt->close();

        return true;
    }

    public static function upsertAsignacion($conexion, $tabla, $idField, $itemField, $planId, $itemId, $estado) {
        $tablasPermitidas = [
            "menu_plan" => ["id" => "menu_plan_id", "item" => "menu_id"],
            "submenu_plan" => ["id" => "submenu_plan_id", "item" => "submenu_id"],
            "submenu1_plan" => ["id" => "submenu1_plan_id", "item" => "submenu1_id"]
        ];

        if (!isset($tablasPermitidas[$tabla])) {
            throw new Exception("Tabla de asignación no permitida.");
        }

        if ($tablasPermitidas[$tabla]["id"] !== $idField) {
            throw new Exception("Campo ID no permitido para {$tabla}.");
        }

        if ($tablasPermitidas[$tabla]["item"] !== $itemField) {
            throw new Exception("Campo de item no permitido para {$tabla}.");
        }

        self::asegurarEstructuraAsignacion($conexion, $tabla);

        $sql = "
            SELECT {$idField}
            FROM {$tabla}
            WHERE planes_id = ?
              AND {$itemField} = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar validación de asignación en {$tabla}: " . $conexion->error);
        }

        $stmt->bind_param("ii", $planId, $itemId);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();
            $registroId = (int)$row[$idField];
            $stmt->close();

            $sqlUpdate = "
                UPDATE {$tabla}
                SET estado = ?
                WHERE {$idField} = ?
            ";

            $stmtUpdate = $conexion->prepare($sqlUpdate);

            if (!$stmtUpdate) {
                throw new Exception("No se pudo preparar actualización en {$tabla}: " . $conexion->error);
            }

            $stmtUpdate->bind_param("ii", $estado, $registroId);

            if (!$stmtUpdate->execute()) {
                $error = $stmtUpdate->error;
                $stmtUpdate->close();
                throw new Exception("No se pudo actualizar {$tabla}: " . $error);
            }

            $stmtUpdate->close();

            return true;
        }

        $stmt->close();

        $nuevoId = self::obtenerSiguienteId($conexion, $tabla, $idField);

        $sqlInsert = "
            INSERT INTO {$tabla} (
                {$idField},
                {$itemField},
                planes_id,
                estado
            ) VALUES (?, ?, ?, ?)
        ";

        $stmtInsert = $conexion->prepare($sqlInsert);

        if (!$stmtInsert) {
            throw new Exception("No se pudo preparar registro en {$tabla}: " . $conexion->error);
        }

        $stmtInsert->bind_param("iiii", $nuevoId, $itemId, $planId, $estado);

        if (!$stmtInsert->execute()) {
            $error = $stmtInsert->error;
            $stmtInsert->close();
            throw new Exception("No se pudo registrar en {$tabla}: " . $error);
        }

        $stmtInsert->close();

        return true;
    }
}