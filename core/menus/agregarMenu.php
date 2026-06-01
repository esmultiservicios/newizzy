<?php
// core/menus/agregarMenu.php

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

function responderAgregarMenu($type, $title, $message, $extra = []) {
    echo json_encode(array_merge([
        "type" => $type,
        "title" => $title,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarTipoAgregarMenu($tipo) {
    $tipo = strtolower(trim($tipo));

    if (!in_array($tipo, ["menu", "submenu", "submenu1"], true)) {
        return "";
    }

    return $tipo;
}

function obtenerConfigAgregarMenu($tipo) {
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

function tablaExisteAgregarMenu($conexion, $tabla) {
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

function validarDependenciaAgregarMenu($conexion, $config, $tipo, $dependencia) {
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

function existeRegistroAgregarMenu($conexion, $tipo, $nombre, $dependencia) {
    if ($tipo === "menu") {
        $sql = "
            SELECT menu_id
            FROM menu
            WHERE name = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la validación del menú: " . $conexion->error);
        }

        $stmt->bind_param("s", $nombre);

    } elseif ($tipo === "submenu") {
        $sql = "
            SELECT submenu_id
            FROM submenu
            WHERE name = ?
              AND menu_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la validación del submenú: " . $conexion->error);
        }

        $stmt->bind_param("si", $nombre, $dependencia);

    } else {
        $sql = "
            SELECT submenu1_id
            FROM submenu1
            WHERE name = ?
              AND submenu_id = ?
            LIMIT 1
        ";

        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            throw new Exception("No se pudo preparar la validación del submenú nivel 2: " . $conexion->error);
        }

        $stmt->bind_param("si", $nombre, $dependencia);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();
    $existe = $resultado && $resultado->num_rows > 0;

    $stmt->close();

    return $existe;
}

function insertarMenuPrincipalAgregar($conexion, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible) {
    $sql = "
        INSERT INTO menu (
            name,
            descripcion,
            icon,
            orden,
            fecha_registro,
            visible
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar el registro del menú principal: " . $conexion->error);
    }

    $stmt->bind_param("sssisi", $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo registrar el menú principal: " . $stmt->error);
    }

    $nuevoId = (int)$conexion->insert_id;
    $stmt->close();

    return $nuevoId;
}

function insertarSubmenuAgregar($conexion, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible) {
    $sql = "
        INSERT INTO submenu (
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
        throw new Exception("No se pudo preparar el registro del submenú: " . $conexion->error);
    }

    $stmt->bind_param("isssisi", $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo registrar el submenú: " . $stmt->error);
    }

    $nuevoId = (int)$conexion->insert_id;
    $stmt->close();

    return $nuevoId;
}

function insertarSubmenu1Agregar($conexion, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible) {
    $sql = "
        INSERT INTO submenu1 (
            submenu_id,
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
        throw new Exception("No se pudo preparar el registro del submenú nivel 2: " . $conexion->error);
    }

    $stmt->bind_param("isssisi", $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);

    if (!$stmt->execute()) {
        throw new Exception("No se pudo registrar el submenú nivel 2: " . $stmt->error);
    }

    $nuevoId = (int)$conexion->insert_id;
    $stmt->close();

    return $nuevoId;
}

function sincronizarRegistroClienteAgregar($conexion, $tipo, $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible) {
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
            throw new Exception("No se pudo preparar sincronización del menú: " . $conexion->error);
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
            throw new Exception("No se pudo preparar sincronización del submenú: " . $conexion->error);
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
            throw new Exception("No se pudo preparar sincronización del submenú nivel 2: " . $conexion->error);
        }

        $stmt->bind_param("iisssisi", $id, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);
    }

    if (!$stmt->execute()) {
        throw new Exception("No se pudo sincronizar en cliente: " . $stmt->error);
    }

    $stmt->close();

    return true;
}

function obtenerBasesClientesActivasAgregar($conexionPrincipal) {
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
        $db = trim($row["db"]);

        if ($db !== "") {
            $bases[] = $db;
        }
    }

    return $bases;
}

$tipo = isset($_POST["tipo"]) ? normalizarTipoAgregarMenu($insMainModel->cleanStringConverterCase($_POST["tipo"])) : "";
$nombre = isset($_POST["nombre"]) ? $insMainModel->cleanStringConverterCase($_POST["nombre"]) : "";
$descripcion = isset($_POST["descripcion"]) ? $insMainModel->cleanStringConverterCase($_POST["descripcion"]) : "";
$icono = isset($_POST["icono"]) ? $insMainModel->cleanStringConverterCase($_POST["icono"]) : "";
$orden = isset($_POST["orden"]) && is_numeric($_POST["orden"]) ? (int)$_POST["orden"] : 0;
$dependencia = isset($_POST["dependencia"]) && $_POST["dependencia"] !== "" ? (int)$_POST["dependencia"] : 0;
$visible = isset($_POST["visible"]) ? (int)$_POST["visible"] : 1;
$fecha_registro = date("Y-m-d H:i:s");

if ($visible !== 1) {
    $visible = 0;
}

if ($tipo === "") {
    responderAgregarMenu("error", "Error", "Debe seleccionar un tipo de menú válido.");
}

if ($nombre === "") {
    responderAgregarMenu("error", "Error", "Debe ingresar el nombre interno del menú.");
}

if ($descripcion === "") {
    responderAgregarMenu("error", "Error", "Debe ingresar la descripción del menú.");
}

if ($tipo !== "menu" && $dependencia <= 0) {
    responderAgregarMenu("error", "Error", "Debe seleccionar la dependencia del elemento.");
}

$config = obtenerConfigAgregarMenu($tipo);

$conexionPrincipal = null;
$erroresClientes = [];
$nuevoId = 0;

try {
    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    if (!validarDependenciaAgregarMenu($conexionPrincipal, $config, $tipo, $dependencia)) {
        $conexionPrincipal->rollback();

        responderAgregarMenu(
            "warning",
            "Dependencia inválida",
            "La dependencia seleccionada no existe."
        );
    }

    if (existeRegistroAgregarMenu($conexionPrincipal, $tipo, $nombre, $dependencia)) {
        $conexionPrincipal->rollback();

        responderAgregarMenu(
            "warning",
            "Advertencia",
            "Este registro ya existe en el sistema."
        );
    }

    if ($tipo === "menu") {
        $nuevoId = insertarMenuPrincipalAgregar($conexionPrincipal, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);
    } elseif ($tipo === "submenu") {
        $nuevoId = insertarSubmenuAgregar($conexionPrincipal, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);
    } else {
        $nuevoId = insertarSubmenu1Agregar($conexionPrincipal, $dependencia, $nombre, $descripcion, $icono, $orden, $fecha_registro, $visible);
    }

    if ($nuevoId <= 0) {
        throw new Exception("No se pudo obtener el ID generado.");
    }

    if ($tipo === "menu") {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "guardar_o_actualizar_modulo_lista_blanca")) {
            $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $nombre);
        }
    }

    $basesClientes = obtenerBasesClientesActivasAgregar($conexionPrincipal);

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

                if (tablaExisteAgregarMenu($connCliente, $config["tabla"])) {
                    sincronizarRegistroClienteAgregar(
                        $connCliente,
                        $tipo,
                        $nuevoId,
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

    $mensaje = "Registro almacenado correctamente.";

    if (!empty($erroresClientes)) {
        $mensaje = "Registro almacenado en la base principal. Algunas bases de clientes no pudieron sincronizarse.";
    }

    echo json_encode([
        "type" => "success",
        "title" => "Éxito",
        "message" => $mensaje,
        "last_id" => $nuevoId,
        "created_type" => $tipo,
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