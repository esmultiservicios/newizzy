<?php
// core/AsignarPlanes/actualizarPlanCliente.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

if (method_exists($mainModel, 'validarSesion')) {
    $validacion = $mainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'type' => 'error',
            'title' => 'Sesión inválida',
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderActualizarPlanCliente($success, $type, $title, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'type' => $type,
        'title' => $title,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function nombreBaseDatosValidoActualizarPlanCliente($dbName) {
    $dbName = trim((string)$dbName);

    if ($dbName === '') {
        return false;
    }

    return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
}

function baseDatosExisteActualizarPlanCliente($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoActualizarPlanCliente($dbName)) {
        return false;
    }

    if (method_exists($mainModel, "databaseExists")) {
        try {
            return $mainModel->databaseExists($dbName) ? true : false;
        } catch (Throwable $e) {
            return false;
        }
    }

    $conexionServidor = null;
    $stmt = null;

    try {
        $conexionServidor = @new mysqli(SERVER, USER, PASS);

        if ($conexionServidor->connect_errno) {
            return false;
        }

        $sql = "
            SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = ?
            LIMIT 1
        ";

        $stmt = $conexionServidor->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $dbName);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado && $resultado->num_rows > 0;

    } catch (Throwable $e) {
        return false;

    } finally {
        if ($stmt) {
            $stmt->close();
        }

        if ($conexionServidor) {
            $conexionServidor->close();
        }
    }
}

function conectarBaseClienteSeguraActualizarPlanCliente($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoActualizarPlanCliente($dbName)) {
        return null;
    }

    if (!baseDatosExisteActualizarPlanCliente($mainModel, $dbName)) {
        return null;
    }

    if (!method_exists($mainModel, "connectToDatabase")) {
        return null;
    }

    $configCliente = [
        "host" => SERVER,
        "user" => USER,
        "pass" => PASS,
        "name" => $dbName
    ];

    try {
        $conexionCliente = $mainModel->connectToDatabase($configCliente);

        if (!$conexionCliente) {
            return null;
        }

        return $conexionCliente;

    } catch (Throwable $e) {
        return null;
    }
}

function tablaExisteActualizarPlanCliente($conexion, $tabla) {
    $sql = "
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
        LIMIT 1
    ";

    $stmt = null;

    try {
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $tabla);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado) {
            return false;
        }

        $row = $resultado->fetch_assoc();

        return isset($row['total']) && (int)$row['total'] > 0;

    } catch (Throwable $e) {
        return false;

    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

function columnaExisteActualizarPlanCliente($conexion, $tabla, $columna) {
    $sql = "
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
        LIMIT 1
    ";

    $stmt = null;

    try {
        $stmt = $conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $tabla, $columna);
        $stmt->execute();

        $resultado = $stmt->get_result();

        if (!$resultado) {
            return false;
        }

        $row = $resultado->fetch_assoc();

        return isset($row['total']) && (int)$row['total'] > 0;

    } catch (Throwable $e) {
        return false;

    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

function asegurarEstructuraPlanCliente($conexionCliente) {
    if (!tablaExisteActualizarPlanCliente($conexionCliente, "plan")) {
        $sqlCreate = "
            CREATE TABLE plan (
                plan_id int NOT NULL,
                planes_id int NOT NULL,
                user_extra int NOT NULL COMMENT 'Cantidad de Usuarios Extras',
                fecha_registro datetime NOT NULL
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci
        ";

        if (!$conexionCliente->query($sqlCreate)) {
            throw new Exception("No se pudo crear la tabla plan del cliente: " . $conexionCliente->error);
        }

        return true;
    }

    if (!columnaExisteActualizarPlanCliente($conexionCliente, "plan", "plan_id")) {
        $sqlAlter = "ALTER TABLE plan ADD plan_id int NOT NULL FIRST";

        if (!$conexionCliente->query($sqlAlter)) {
            throw new Exception("No se pudo agregar la columna plan_id en la tabla plan: " . $conexionCliente->error);
        }
    }

    if (!columnaExisteActualizarPlanCliente($conexionCliente, "plan", "planes_id")) {
        $sqlAlter = "ALTER TABLE plan ADD planes_id int NOT NULL DEFAULT 0 AFTER plan_id";

        if (!$conexionCliente->query($sqlAlter)) {
            throw new Exception("No se pudo agregar la columna planes_id en la tabla plan: " . $conexionCliente->error);
        }
    }

    if (!columnaExisteActualizarPlanCliente($conexionCliente, "plan", "user_extra")) {
        $sqlAlter = "ALTER TABLE plan ADD user_extra int NOT NULL DEFAULT 0 COMMENT 'Cantidad de Usuarios Extras' AFTER planes_id";

        if (!$conexionCliente->query($sqlAlter)) {
            throw new Exception("No se pudo agregar la columna user_extra en la tabla plan: " . $conexionCliente->error);
        }
    }

    if (!columnaExisteActualizarPlanCliente($conexionCliente, "plan", "fecha_registro")) {
        $sqlAlter = "ALTER TABLE plan ADD fecha_registro datetime NOT NULL AFTER user_extra";

        if (!$conexionCliente->query($sqlAlter)) {
            throw new Exception("No se pudo agregar la columna fecha_registro en la tabla plan: " . $conexionCliente->error);
        }
    }

    return true;
}

function obtenerServerCustomerActualizarPlanCliente($conexion, $serverCustomersId, $clienteId) {
    $sql = "
        SELECT 
            server_customers_id,
            clientes_id,
            planes_id,
            sistema_id,
            db,
            db_imported
        FROM server_customers
        WHERE server_customers_id = ?
          AND clientes_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta de server_customers: " . $conexion->error);
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

function validarPlanActivoActualizarPlanCliente($conexion, $planesId) {
    $sql = "
        SELECT planes_id
        FROM planes
        WHERE planes_id = ?
          AND estado = 1
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la validación del plan: " . $conexion->error);
    }

    $stmt->bind_param("i", $planesId);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $existe = $resultado && $resultado->num_rows > 0;

    $stmt->close();

    return $existe;
}

function actualizarServerCustomersPrincipal($conexion, $serverCustomersId, $planesId, $validar, $estado) {
    $sql = "
        UPDATE server_customers SET
            planes_id = ?,
            validar = ?,
            estado = ?
        WHERE server_customers_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la actualización de server_customers: " . $conexion->error);
    }

    $stmt->bind_param("iiii", $planesId, $validar, $estado, $serverCustomersId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception("No se pudo actualizar server_customers: " . $error);
    }

    $stmt->close();

    return true;
}

function actualizarPlanTablaCliente($conexionCliente, $planesId, $userExtra) {
    asegurarEstructuraPlanCliente($conexionCliente);

    $sqlCheck = "
        SELECT plan_id
        FROM plan
        WHERE plan_id = 1
        LIMIT 1
    ";

    $stmtCheck = $conexionCliente->prepare($sqlCheck);

    if (!$stmtCheck) {
        throw new Exception("No se pudo preparar la validación de la tabla plan del cliente: " . $conexionCliente->error);
    }

    $stmtCheck->execute();

    $resultado = $stmtCheck->get_result();
    $existePlan = $resultado && $resultado->num_rows > 0;

    $stmtCheck->close();

    if ($existePlan) {
        $sqlUpdate = "
            UPDATE plan SET
                planes_id = ?,
                user_extra = ?,
                fecha_registro = NOW()
            WHERE plan_id = 1
            LIMIT 1
        ";

        $stmtUpdate = $conexionCliente->prepare($sqlUpdate);

        if (!$stmtUpdate) {
            throw new Exception("No se pudo preparar la actualización del plan en la base del cliente: " . $conexionCliente->error);
        }

        $stmtUpdate->bind_param("ii", $planesId, $userExtra);

        if (!$stmtUpdate->execute()) {
            $error = $stmtUpdate->error;
            $stmtUpdate->close();
            throw new Exception("No se pudo actualizar la tabla plan del cliente: " . $error);
        }

        $stmtUpdate->close();

        return true;
    }

    $sqlInsert = "
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

    $stmtInsert = $conexionCliente->prepare($sqlInsert);

    if (!$stmtInsert) {
        throw new Exception("No se pudo preparar el registro del plan en la base del cliente: " . $conexionCliente->error);
    }

    $stmtInsert->bind_param("ii", $planesId, $userExtra);

    if (!$stmtInsert->execute()) {
        $error = $stmtInsert->error;
        $stmtInsert->close();
        throw new Exception("No se pudo insertar la tabla plan del cliente: " . $error);
    }

    $stmtInsert->close();

    return true;
}

$serverCustomersId = isset($_POST['server_customers_id']) ? (int)$_POST['server_customers_id'] : 0;
$clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
$planesId = isset($_POST['planes_id']) ? (int)$_POST['planes_id'] : 0;
$userExtra = isset($_POST['user_extra']) ? (int)$_POST['user_extra'] : 0;
$validar = isset($_POST['validar']) ? (int)$_POST['validar'] : 1;
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;

if ($serverCustomersId <= 0) {
    responderActualizarPlanCliente(false, "error", "Error", "No se recibió el ID de server_customers.");
}

if ($clienteId <= 0) {
    responderActualizarPlanCliente(false, "error", "Error", "Debe seleccionar un cliente válido.");
}

if ($planesId <= 0) {
    responderActualizarPlanCliente(false, "error", "Error", "Debe seleccionar un plan válido.");
}

if ($userExtra < 0) {
    $userExtra = 0;
}

if ($validar !== 1 && $validar !== 2) {
    $validar = 1;
}

if ($estado !== 1) {
    $estado = 0;
}

$conexionPrincipal = null;
$conexionCliente = null;

try {
    $conexionPrincipal = $mainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    if (!validarPlanActivoActualizarPlanCliente($conexionPrincipal, $planesId)) {
        throw new Exception("El plan seleccionado no existe o está inactivo.");
    }

    $serverCustomer = obtenerServerCustomerActualizarPlanCliente(
        $conexionPrincipal,
        $serverCustomersId,
        $clienteId
    );

    $dbName = trim((string)$serverCustomer['db']);
    $dbImported = isset($serverCustomer['db_imported']) ? (int)$serverCustomer['db_imported'] : 0;

    if ($dbName === "") {
        throw new Exception("El cliente no tiene una base de datos registrada.");
    }

    if (!nombreBaseDatosValidoActualizarPlanCliente($dbName)) {
        throw new Exception("El nombre de la base de datos del cliente no es válido.");
    }

    if ($dbImported !== 1) {
        throw new Exception("La base de datos del cliente aún no está marcada como importada.");
    }

    if (!baseDatosExisteActualizarPlanCliente($mainModel, $dbName)) {
        throw new Exception("La base de datos del cliente no existe o el usuario no tiene acceso.");
    }

    $conexionCliente = conectarBaseClienteSeguraActualizarPlanCliente($mainModel, $dbName);

    if (!$conexionCliente) {
        throw new Exception("No se pudo conectar a la base de datos del cliente.");
    }

    $conexionCliente->autocommit(false);

    actualizarServerCustomersPrincipal(
        $conexionPrincipal,
        $serverCustomersId,
        $planesId,
        $validar,
        $estado
    );

    actualizarPlanTablaCliente(
        $conexionCliente,
        $planesId,
        $userExtra
    );

    $conexionCliente->commit();
    $conexionPrincipal->commit();

    responderActualizarPlanCliente(
        true,
        "success",
        "Registro actualizado",
        "Plan actualizado correctamente en server_customers y en la tabla plan del cliente.",
        [
            "server_customers_id" => $serverCustomersId,
            "cliente_id" => $clienteId,
            "planes_id" => $planesId,
            "user_extra" => $userExtra,
            "validar" => $validar,
            "estado" => $estado,
            "db_cliente" => $dbName
        ]
    );

} catch (Throwable $e) {
    if ($conexionCliente) {
        $conexionCliente->rollback();
    }

    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    responderActualizarPlanCliente(
        false,
        "error",
        "Error",
        "Error al actualizar plan: " . $e->getMessage()
    );

} finally {
    if ($conexionCliente) {
        $conexionCliente->autocommit(true);
        $conexionCliente->close();
    }

    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}