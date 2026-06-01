<?php
// core/menus/editarMenu.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            "type" => "error",
            "title" => "Sesión inválida",
            "message" => $validacion['mensaje'] ?? "Sesión inválida"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderEditarMenu($type, $title, $message, $extra = []) {
    echo json_encode(array_merge([
        "type" => $type,
        "title" => $title,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarTipoEditarMenu($tipo) {
    $tipo = strtolower(trim((string)$tipo));

    if (!in_array($tipo, ["menu", "submenu", "submenu1"], true)) {
        return "";
    }

    return $tipo;
}

function obtenerConfigEditarMenu($tipo) {
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

function tablaExisteEditarMenu($conexion, $tabla) {
    $stmt = $conexion->prepare("SHOW TABLES LIKE ?");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $tabla);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $existe = $resultado && $resultado->num_rows > 0;

    $stmt->close();

    return $existe;
}

function obtenerNombreActualEditarMenu($conexion, $tabla, $idField, $id) {
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
    $nombre = isset($row['name']) ? trim((string)$row['name']) : "";

    $stmt->close();

    return $nombre;
}

function validarDependenciaEditarMenu($conexion, $config, $tipo, $dependencia) {
    if ($tipo === "menu") {
        return true;
    }

    if ($dependencia <= 0) {
        return false;
    }

    $tabla = $config["dependency_table"];
    $campo = $config["dependency_field"];

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

function existeDuplicadoEditarMenu($conexion, $tipo, $id, $nombre, $dependencia) {
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

        $stmt->bind_param("si", $nombre, $id);

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

        $stmt->bind_param("sii", $nombre, $dependencia, $id);

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

        $stmt->bind_param("sii", $nombre, $dependencia, $id);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();
    $existe = $resultado && $resultado->num_rows > 0;

    $stmt->close();

    return $existe;
}

function actualizarPrincipalEditarMenu($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $visible) {
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

        throw new Exception("No se pudo actualizar el registro: " . $error);
    }

    $stmt->close();

    return true;
}

function sincronizarClienteEditarMenu($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible) {
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
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                descripcion = VALUES(descripcion),
                icon = VALUES(icon),
                orden = VALUES(orden),
                visible = VALUES(visible)
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar sincronización de menú: " . $conexion->error);
        }

        $stmt->bind_param("isssisi", $id, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);

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
            ON DUPLICATE KEY UPDATE
                menu_id = VALUES(menu_id),
                name = VALUES(name),
                descripcion = VALUES(descripcion),
                icon = VALUES(icon),
                orden = VALUES(orden),
                visible = VALUES(visible)
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar sincronización de submenú: " . $conexion->error);
        }

        $stmt->bind_param("iisssisi", $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);

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
            ON DUPLICATE KEY UPDATE
                submenu_id = VALUES(submenu_id),
                name = VALUES(name),
                descripcion = VALUES(descripcion),
                icon = VALUES(icon),
                orden = VALUES(orden),
                visible = VALUES(visible)
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar sincronización de submenú nivel 2: " . $conexion->error);
        }

        $stmt->bind_param("iisssisi", $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);
    }

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new Exception("No se pudo sincronizar en cliente: " . $error);
    }

    $stmt->close();

    return true;
}

function obtenerBasesClientesActivasEditarMenu($conexionPrincipal) {
    $sql = "
        SELECT db
        FROM server_customers
        WHERE estado = 1
          AND db_imported = 1
          AND db IS NOT NULL
          AND db <> ''
        ORDER BY server_customers_id ASC
    ";

    $resultado = $conexionPrincipal->query($sql);

    if (!$resultado) {
        throw new Exception("No se pudieron obtener las bases de clientes: " . $conexionPrincipal->error);
    }

    $bases = [];

    while ($row = $resultado->fetch_assoc()) {
        $db = trim((string)$row["db"]);

        if ($db !== "") {
            $bases[] = $db;
        }
    }

    return $bases;
}

$id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
$tipo = isset($_POST["tipo"]) ? normalizarTipoEditarMenu($_POST["tipo"]) : "";
$nombre = isset($_POST["nombre"]) ? trim((string)$_POST["nombre"]) : "";
$descripcion = isset($_POST["descripcion"]) ? trim((string)$_POST["descripcion"]) : "";
$icono = isset($_POST["icono"]) ? trim((string)$_POST["icono"]) : "";
$orden = isset($_POST["orden"]) && is_numeric($_POST["orden"]) ? (int)$_POST["orden"] : 0;
$dependencia = isset($_POST["dependencia"]) && $_POST["dependencia"] !== "" ? (int)$_POST["dependencia"] : 0;
$visible = isset($_POST["visible"]) ? (int)$_POST["visible"] : 1;
$fecha_registro = date("Y-m-d H:i:s");

if ($visible !== 1) {
    $visible = 0;
}

if ($id <= 0) {
    responderEditarMenu("error", "Error", "No se recibió un ID válido.");
}

if ($tipo === "") {
    responderEditarMenu("error", "Error", "Debe seleccionar un tipo de menú válido.");
}

if ($nombre === "") {
    responderEditarMenu("error", "Error", "Debe ingresar el nombre interno del menú.");
}

if ($descripcion === "") {
    responderEditarMenu("error", "Error", "Debe ingresar la descripción del menú.");
}

if ($tipo !== "menu" && $dependencia <= 0) {
    responderEditarMenu("error", "Error", "Debe seleccionar la dependencia del elemento.");
}

$config = obtenerConfigEditarMenu($tipo);

$conexionPrincipal = null;
$erroresClientes = [];
$oldName = "";

try {
    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    if (!tablaExisteEditarMenu($conexionPrincipal, $config["tabla"])) {
        throw new Exception("La tabla {$config["tabla"]} no existe en la base principal.");
    }

    $oldName = obtenerNombreActualEditarMenu(
        $conexionPrincipal,
        $config["tabla"],
        $config["id_field"],
        $id
    );

    if ($oldName === "") {
        $conexionPrincipal->rollback();

        responderEditarMenu(
            "warning",
            "Registro no encontrado",
            "El {$config["nombre_tipo"]} que intenta editar no existe."
        );
    }

    if (!validarDependenciaEditarMenu($conexionPrincipal, $config, $tipo, $dependencia)) {
        $conexionPrincipal->rollback();

        responderEditarMenu(
            "warning",
            "Dependencia inválida",
            "La dependencia seleccionada no existe."
        );
    }

    if (existeDuplicadoEditarMenu($conexionPrincipal, $tipo, $id, $nombre, $dependencia)) {
        $conexionPrincipal->rollback();

        responderEditarMenu(
            "warning",
            "Advertencia",
            "Ya existe un registro con este nombre y dependencia."
        );
    }

    actualizarPrincipalEditarMenu(
        $conexionPrincipal,
        $tipo,
        $id,
        $dependencia,
        $nombre,
        $descripcion,
        $icono,
        $orden,
        $visible
    );

    if ($tipo === "menu" && $oldName !== $nombre) {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "guardar_o_actualizar_modulo_lista_blanca")) {
            $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
        }

        if (method_exists($insMainModel, "eliminar_modulo_lista_blanca")) {
            $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $oldName);
        }
    }

    $basesClientes = obtenerBasesClientesActivasEditarMenu($conexionPrincipal);

    foreach ($basesClientes as $dbName) {
        try {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                $erroresClientes[] = "Nombre de base inválido omitido: {$dbName}";
                continue;
            }

            if (method_exists($insMainModel, "databaseExists") && !$insMainModel->databaseExists($dbName)) {
                $erroresClientes[] = "La base {$dbName} no existe.";
                continue;
            }

            if (!method_exists($insMainModel, "connectToDatabase")) {
                $erroresClientes[] = "No existe el método connectToDatabase en mainModel.";
                continue;
            }

            $configCliente = [
                "host" => SERVER,
                "user" => USER,
                "pass" => PASS,
                "name" => $dbName
            ];

            $connCliente = $insMainModel->connectToDatabase($configCliente);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar a la base {$dbName}.";
                continue;
            }

            try {
                $connCliente->autocommit(false);

                if (tablaExisteEditarMenu($connCliente, $config["tabla"])) {
                    sincronizarClienteEditarMenu(
                        $connCliente,
                        $tipo,
                        $id,
                        $dependencia,
                        $nombre,
                        $descripcion,
                        $icono,
                        $orden,
                        $fecha_registro,
                        $visible
                    );
                }

                $connCliente->commit();

            } catch (Exception $eCliente) {
                $connCliente->rollback();
                $erroresClientes[] = "Error en {$dbName}: " . $eCliente->getMessage();

            } finally {
                $connCliente->autocommit(true);
                $connCliente->close();
            }

        } catch (Exception $eClienteGeneral) {
            $erroresClientes[] = "Error en {$dbName}: " . $eClienteGeneral->getMessage();
        }
    }

    $conexionPrincipal->commit();

    $mensaje = "Registro actualizado correctamente.";

    if (!empty($erroresClientes)) {
        $mensaje = "Registro actualizado en la base principal. Algunas bases de clientes no pudieron sincronizarse.";
    }

    echo json_encode([
        "type" => "success",
        "title" => "Éxito",
        "message" => $mensaje,
        "updated_type" => $tipo,
        "updated_id" => $id,
        "warnings" => $erroresClientes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Error en el servidor: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}