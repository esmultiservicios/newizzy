<?php
// core/menus/MenuSyncHelper.php

class MenuSyncHelper {

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

    public static function validarDbName($dbName) {
        return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
    }

    public static function normalizarTipo($tipo) {
        $tipo = strtolower(trim((string)$tipo));

        if (!in_array($tipo, ["menu", "submenu", "submenu1"], true)) {
            return "";
        }

        return $tipo;
    }

    public static function obtenerConfig($tipo) {
        if ($tipo === "menu") {
            return [
                "tabla" => "menu",
                "id_field" => "menu_id",
                "parent_field" => "",
                "dependency_table" => "",
                "dependency_field" => "",
                "nombre_tipo" => "menú principal"
            ];
        }

        if ($tipo === "submenu") {
            return [
                "tabla" => "submenu",
                "id_field" => "submenu_id",
                "parent_field" => "menu_id",
                "dependency_table" => "menu",
                "dependency_field" => "menu_id",
                "nombre_tipo" => "submenú nivel 1"
            ];
        }

        return [
            "tabla" => "submenu1",
            "id_field" => "submenu1_id",
            "parent_field" => "submenu_id",
            "dependency_table" => "submenu",
            "dependency_field" => "submenu_id",
            "nombre_tipo" => "submenú nivel 2"
        ];
    }

    public static function tablaExiste($conexion, $tabla) {
        $tabla = trim((string)$tabla);

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            return false;
        }

        $tablaSafe = $conexion->real_escape_string($tabla);

        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tablaSafe}'
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

        $tablaSafe = $conexion->real_escape_string($tabla);
        $columnaSafe = $conexion->real_escape_string($columna);

        $sql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$tablaSafe}'
              AND COLUMN_NAME = '{$columnaSafe}'
            LIMIT 1
        ";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            return false;
        }

        $row = $resultado->fetch_assoc();

        return isset($row["total"]) && (int)$row["total"] > 0;
    }

    public static function validarEstructuraMenus($conexion) {
        $tablas = ["menu", "submenu", "submenu1"];

        foreach ($tablas as $tabla) {
            if (!self::tablaExiste($conexion, $tabla)) {
                throw new Exception("La tabla {$tabla} no existe en la base actual.");
            }
        }

        $columnas = [
            "menu" => ["menu_id", "name", "descripcion", "icon", "orden", "fecha_registro", "visible"],
            "submenu" => ["submenu_id", "menu_id", "name", "descripcion", "icon", "orden", "fecha_registro", "visible"],
            "submenu1" => ["submenu1_id", "submenu_id", "name", "descripcion", "icon", "orden", "fecha_registro", "visible"]
        ];

        foreach ($columnas as $tabla => $cols) {
            foreach ($cols as $columna) {
                if (!self::columnaExiste($conexion, $tabla, $columna)) {
                    throw new Exception("La columna {$columna} no existe en la tabla {$tabla}.");
                }
            }
        }

        return true;
    }

    public static function obtenerBasesClientesActivas($conexionPrincipal) {
        $bases = [];

        $sql = "
            SELECT db
            FROM server_customers
            WHERE estado = 1
              AND sistema_id = 1
              AND db IS NOT NULL
              AND TRIM(db) != ''
            ORDER BY server_customers_id ASC
        ";

        $resultado = $conexionPrincipal->query($sql);

        if (!$resultado) {
            throw new Exception("No se pudieron obtener las bases activas: " . $conexionPrincipal->error);
        }

        while ($row = $resultado->fetch_assoc()) {
            $db = trim((string)$row["db"]);

            if ($db !== "" && self::validarDbName($db)) {
                $bases[] = $db;
            }
        }

        return array_values(array_unique($bases));
    }

    public static function validarDependencia($conexion, $config, $tipo, $dependencia) {
        if ($tipo === "menu") {
            return true;
        }

        if ($dependencia <= 0) {
            return false;
        }

        $tabla = $config["dependency_table"];
        $campo = $config["dependency_field"];

        if (!self::tablaExiste($conexion, $tabla)) {
            return false;
        }

        $sql = "SELECT {$campo} FROM {$tabla} WHERE {$campo} = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la validación de dependencia: " . $conexion->error);
        }

        $stmt->bind_param("i", $dependencia);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function obtenerNombreActual($conexion, $tabla, $idField, $id) {
        if (!self::tablaExiste($conexion, $tabla)) {
            return "";
        }

        $sql = "SELECT name FROM {$tabla} WHERE {$idField} = ? LIMIT 1";
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar consulta del registro actual: " . $conexion->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado || $resultado->num_rows <= 0) {
            $stmt->close();
            return "";
        }

        $row = $resultado->fetch_assoc();
        $nombre = isset($row["name"]) ? trim((string)$row["name"]) : "";

        $stmt->close();

        return $nombre;
    }

    public static function existeDuplicado($conexion, $tipo, $idExcluir, $nombre, $dependencia) {
        if ($tipo === "menu") {
            $sql = "
                SELECT menu_id
                FROM menu
                WHERE name = ?
                  AND menu_id <> ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar validación de duplicado de menú: " . $conexion->error);
            }

            $stmt->bind_param("si", $nombre, $idExcluir);

        } elseif ($tipo === "submenu") {
            $sql = "
                SELECT submenu_id
                FROM submenu
                WHERE name = ?
                  AND menu_id = ?
                  AND submenu_id <> ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar validación de duplicado de submenú: " . $conexion->error);
            }

            $stmt->bind_param("sii", $nombre, $dependencia, $idExcluir);

        } else {
            $sql = "
                SELECT submenu1_id
                FROM submenu1
                WHERE name = ?
                  AND submenu_id = ?
                  AND submenu1_id <> ?
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar validación de duplicado de submenú nivel 2: " . $conexion->error);
            }

            $stmt->bind_param("sii", $nombre, $dependencia, $idExcluir);
        }

        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function obtenerSiguienteId($conexion, $tabla, $idField) {
        $permitidos = [
            "menu" => "menu_id",
            "submenu" => "submenu_id",
            "submenu1" => "submenu1_id"
        ];

        if (!isset($permitidos[$tabla]) || $permitidos[$tabla] !== $idField) {
            throw new Exception("Tabla o campo no permitido para correlativo.");
        }

        $sql = "SELECT COALESCE(MAX({$idField}), 0) + 1 AS siguiente FROM {$tabla}";
        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("No se pudo obtener el correlativo de {$tabla}: " . $conexion->error);
        }

        $row = $resultado->fetch_assoc();

        return (int)$row["siguiente"];
    }

    public static function insertarRegistro($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible) {
        self::validarEstructuraMenus($conexion);

        if ($tipo === "menu") {
            $sql = "
                INSERT INTO menu (
                    menu_id,
                    name,
                    descripcion,
                    icon,
                    orden,
                    fecha_registro,
                    visible
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar registro de menú: " . $conexion->error);
            }

            $stmt->bind_param("isssisi", $id, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible);

        } elseif ($tipo === "submenu") {
            $sql = "
                INSERT INTO submenu (
                    submenu_id,
                    menu_id,
                    name,
                    descripcion,
                    icon,
                    orden,
                    fecha_registro,
                    visible
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar registro de submenú: " . $conexion->error);
            }

            $stmt->bind_param("iisssisi", $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible);

        } else {
            $sql = "
                INSERT INTO submenu1 (
                    submenu1_id,
                    submenu_id,
                    name,
                    descripcion,
                    icon,
                    orden,
                    fecha_registro,
                    visible
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar registro de submenú nivel 2: " . $conexion->error);
            }

            $stmt->bind_param("iisssisi", $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo registrar el elemento: " . $error);
        }

        $stmt->close();

        return true;
    }

    public static function actualizarRegistro($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $visible) {
        self::validarEstructuraMenus($conexion);

        if ($tipo === "menu") {
            $sql = "
                UPDATE menu SET
                    name = ?,
                    descripcion = ?,
                    icon = ?,
                    orden = ?,
                    visible = ?
                WHERE menu_id = ?
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar actualización de menú: " . $conexion->error);
            }

            $stmt->bind_param("sssiii", $nombre, $descripcion, $icono, $orden, $visible, $id);

        } elseif ($tipo === "submenu") {
            $sql = "
                UPDATE submenu SET
                    menu_id = ?,
                    name = ?,
                    descripcion = ?,
                    icon = ?,
                    orden = ?,
                    visible = ?
                WHERE submenu_id = ?
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar actualización de submenú: " . $conexion->error);
            }

            $stmt->bind_param("isssiii", $dependencia, $nombre, $descripcion, $icono, $orden, $visible, $id);

        } else {
            $sql = "
                UPDATE submenu1 SET
                    submenu_id = ?,
                    name = ?,
                    descripcion = ?,
                    icon = ?,
                    orden = ?,
                    visible = ?
                WHERE submenu1_id = ?
            ";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new Exception("No se pudo preparar actualización de submenú nivel 2: " . $conexion->error);
            }

            $stmt->bind_param("isssiii", $dependencia, $nombre, $descripcion, $icono, $orden, $visible, $id);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo actualizar el elemento: " . $error);
        }

        $stmt->close();

        return true;
    }

    public static function upsertRegistro($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible) {
        self::validarEstructuraMenus($conexion);

        $config = self::obtenerConfig($tipo);
        $tabla = $config["tabla"];
        $idField = $config["id_field"];

        $existe = self::obtenerNombreActual($conexion, $tabla, $idField, $id) !== "";

        if ($existe) {
            self::actualizarRegistro($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $visible);
        } else {
            self::insertarRegistro($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fechaRegistro, $visible);
        }

        return true;
    }

    public static function tieneDependencias($conexion, $tipo, $id) {
        if ($tipo === "menu") {
            if (!self::tablaExiste($conexion, "submenu")) {
                return false;
            }

            $sql = "SELECT 1 FROM submenu WHERE menu_id = ? LIMIT 1";

        } elseif ($tipo === "submenu") {
            if (!self::tablaExiste($conexion, "submenu1")) {
                return false;
            }

            $sql = "SELECT 1 FROM submenu1 WHERE submenu_id = ? LIMIT 1";

        } else {
            return false;
        }

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar validación de dependencias: " . $conexion->error);
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $existe = $resultado && $resultado->num_rows > 0;

        $stmt->close();

        return $existe;
    }

    public static function eliminarRegistro($conexion, $tipo, $id) {
        $config = self::obtenerConfig($tipo);

        if (!self::tablaExiste($conexion, $config["tabla"])) {
            return true;
        }

        $sql = "DELETE FROM {$config["tabla"]} WHERE {$config["id_field"]} = ?";
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar eliminación: " . $conexion->error);
        }

        $stmt->bind_param("i", $id);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("No se pudo eliminar el elemento: " . $error);
        }

        $stmt->close();

        return true;
    }
}