<?php
// core/menus/eliminarMenu.php

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

function responderEliminarMenu($type, $title, $message, $extra = []) {
    echo json_encode(array_merge([
        "type" => $type,
        "title" => $title,
        "message" => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function normalizarTipoEliminarMenu($tipo) {
    $tipo = strtolower(trim((string)$tipo));

    if (!in_array($tipo, ["menu", "submenu", "submenu1"], true)) {
        return "";
    }

    return $tipo;
}

function obtenerConfigEliminarMenu($tipo) {
    if ($tipo === "menu") {
        return [
            "tabla" => "menu",
            "id_field" => "menu_id",
            "dependency_table" => "submenu",
            "dependency_field" => "menu_id",
            "nombre_tipo" => "menú principal"
        ];
    }

    if ($tipo === "submenu") {
        return [
            "tabla" => "submenu",
            "id_field" => "submenu_id",
            "dependency_table" => "submenu1",
            "dependency_field" => "submenu_id",
            "nombre_tipo" => "submenú nivel 1"
        ];
    }

    return [
        "tabla" => "submenu1",
        "id_field" => "submenu1_id",
        "dependency_table" => "",
        "dependency_field" => "",
        "nombre_tipo" => "submenú nivel 2"
    ];
}

function tablaExisteEliminarMenu($conexion, $tabla) {
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

function obtenerNombreModuloEliminarMenu($conexion, $tabla, $idField, $id) {
    $sql = "SELECT name FROM {$tabla} WHERE {$idField} = ? LIMIT 1";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        return "";
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado && $resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        $nombre = isset($row['name']) ? trim((string)$row['name']) : "";
        $stmt->close();

        return $nombre;
    }

    $stmt->close();

    return "";
}

function tieneDependenciasEliminarMenu($conexion, $dependencyTable, $dependencyField, $id) {
    if ($dependencyTable === "" || $dependencyField === "") {
        return false;
    }

    $sql = "SELECT 1 FROM {$dependencyTable} WHERE {$dependencyField} = ? LIMIT 1";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la validación de dependencias: " . $conexion->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $existe = $resultado && $resultado->num_rows > 0;

    $stmt->close();

    return $existe;
}

function eliminarRegistroMenuPorTipo($conexion, $tabla, $idField, $id) {
    $sql = "DELETE FROM {$tabla} WHERE {$idField} = ?";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la eliminación en {$tabla}: " . $conexion->error);
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new Exception("No se pudo eliminar en {$tabla}: " . $error);
    }

    $stmt->close();

    return true;
}

function obtenerBasesClientesActivasEliminarMenu($conexionPrincipal) {
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
        throw new Exception("No se pudieron obtener las bases de datos de clientes: " . $conexionPrincipal->error);
    }

    $bases = [];

    while ($row = $resultado->fetch_assoc()) {
        $db = trim((string)$row['db']);

        if ($db !== "") {
            $bases[] = $db;
        }
    }

    return $bases;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$tipo = isset($_POST['tipo']) ? normalizarTipoEliminarMenu($_POST['tipo']) : "";

if ($id <= 0) {
    responderEliminarMenu("error", "Error", "No se recibió un ID válido.");
}

if ($tipo === "") {
    responderEliminarMenu("error", "Error", "No se recibió un tipo de menú válido.");
}

$config = obtenerConfigEliminarMenu($tipo);

$conexionPrincipal = null;
$erroresClientes = [];
$nombreModulo = "";

try {
    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    if (!tablaExisteEliminarMenu($conexionPrincipal, $config['tabla'])) {
        throw new Exception("La tabla {$config['tabla']} no existe en la base principal.");
    }

    $nombreModulo = obtenerNombreModuloEliminarMenu(
        $conexionPrincipal,
        $config['tabla'],
        $config['id_field'],
        $id
    );

    if ($nombreModulo === "") {
        $conexionPrincipal->rollback();

        responderEliminarMenu(
            "warning",
            "Registro no encontrado",
            "El {$config['nombre_tipo']} que intenta eliminar no existe."
        );
    }

    $tieneDependencias = tieneDependenciasEliminarMenu(
        $conexionPrincipal,
        $config['dependency_table'],
        $config['dependency_field'],
        $id
    );

    if ($tieneDependencias) {
        $conexionPrincipal->rollback();

        responderEliminarMenu(
            "warning",
            "Advertencia",
            "No se puede eliminar este {$config['nombre_tipo']} porque tiene elementos dependientes."
        );
    }

    eliminarRegistroMenuPorTipo(
        $conexionPrincipal,
        $config['tabla'],
        $config['id_field'],
        $id
    );

    if ($tipo === "menu" && $nombreModulo !== "") {
        $nombre_config = "configuracion_principal";

        if (method_exists($insMainModel, "eliminar_modulo_lista_blanca")) {
            $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $nombreModulo);
        }
    }

    $basesClientes = obtenerBasesClientesActivasEliminarMenu($conexionPrincipal);

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

                if (tablaExisteEliminarMenu($connCliente, $config['tabla'])) {
                    eliminarRegistroMenuPorTipo(
                        $connCliente,
                        $config['tabla'],
                        $config['id_field'],
                        $id
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

    $mensaje = "Registro eliminado correctamente.";

    if (!empty($erroresClientes)) {
        $mensaje = "Registro eliminado en la base principal. Algunas bases de clientes no pudieron sincronizarse.";
    }

    echo json_encode([
        "type" => "success",
        "title" => "Éxito",
        "message" => $mensaje,
        "deleted_type" => $tipo,
        "deleted_id" => $id,
        "deleted_name" => $nombreModulo,
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