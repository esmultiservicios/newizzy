<?php
// core/AsignarPlanes/verificarPlanCliente.php

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
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderVerificarPlanCliente($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function nombreBaseDatosValidoVerificarPlanCliente($dbName) {
    $dbName = trim((string)$dbName);

    if ($dbName === '') {
        return false;
    }

    return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
}

function baseDatosExisteVerificarPlanCliente($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoVerificarPlanCliente($dbName)) {
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

function conectarBaseClienteSeguraVerificarPlanCliente($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoVerificarPlanCliente($dbName)) {
        return null;
    }

    if (!baseDatosExisteVerificarPlanCliente($mainModel, $dbName)) {
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

function tablaExisteVerificarPlanCliente($conexion, $tabla) {
    $stmt = null;

    try {
        $stmt = $conexion->prepare("SHOW TABLES LIKE ?");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("s", $tabla);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado && $resultado->num_rows > 0;

    } catch (Throwable $e) {
        return false;

    } finally {
        if ($stmt) {
            $stmt->close();
        }
    }
}

function obtenerPlanInternoCliente($mainModel, $dbName) {
    $datosPlan = [
        "user_extra" => 0,
        "fecha_registro" => "",
        "db_disponible" => 0,
        "db_mensaje" => ""
    ];

    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoVerificarPlanCliente($dbName)) {
        $datosPlan["db_mensaje"] = "Base de datos no registrada o nombre inválido";
        return $datosPlan;
    }

    $conexionCliente = conectarBaseClienteSeguraVerificarPlanCliente($mainModel, $dbName);

    if (!$conexionCliente) {
        $datosPlan["db_mensaje"] = "No se pudo conectar a la base del cliente o no existe";
        return $datosPlan;
    }

    $stmt = null;

    try {
        $datosPlan["db_disponible"] = 1;

        if (!tablaExisteVerificarPlanCliente($conexionCliente, "plan")) {
            $datosPlan["db_mensaje"] = "La tabla plan no existe en la base del cliente";
            return $datosPlan;
        }

        $sql = "
            SELECT 
                IFNULL(user_extra, 0) AS user_extra,
                fecha_registro
            FROM plan
            WHERE plan_id = 1
            LIMIT 1
        ";

        $stmt = $conexionCliente->prepare($sql);

        if (!$stmt) {
            $datosPlan["db_mensaje"] = "No se pudo preparar la consulta del plan del cliente";
            return $datosPlan;
        }

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();

            $datosPlan["user_extra"] = isset($row["user_extra"]) ? (int)$row["user_extra"] : 0;
            $datosPlan["fecha_registro"] = $row["fecha_registro"] ?? "";
            $datosPlan["db_mensaje"] = "OK";
        } else {
            $datosPlan["db_mensaje"] = "No hay registro en la tabla plan";
        }

    } catch (Throwable $e) {
        $datosPlan["user_extra"] = 0;
        $datosPlan["fecha_registro"] = "";
        $datosPlan["db_disponible"] = 0;
        $datosPlan["db_mensaje"] = "Error al consultar la base del cliente";

    } finally {
        if ($stmt) {
            $stmt->close();
        }

        $conexionCliente->close();
    }

    return $datosPlan;
}

$clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;

if ($clienteId <= 0) {
    responderVerificarPlanCliente([
        'success' => false,
        'message' => 'ID de cliente no proporcionado'
    ]);
}

$conexion = null;
$stmt = null;

try {
    $conexion = $mainModel->connection();

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $sql = "
        SELECT 
            sc.server_customers_id,
            sc.clientes_id,
            sc.planes_id,
            sc.sistema_id,
            sc.db,
            sc.validar,
            sc.estado,
            c.nombre AS cliente_nombre
        FROM server_customers sc
        INNER JOIN clientes c ON sc.clientes_id = c.clientes_id
        WHERE sc.clientes_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta: " . $conexion->error);
    }

    $stmt->bind_param("i", $clienteId);
    $stmt->execute();

    $resultado = $stmt->get_result();

    if (!$resultado || $resultado->num_rows <= 0) {
        responderVerificarPlanCliente([
            'success' => true,
            'exists' => false
        ]);
    }

    $data = $resultado->fetch_assoc();

    $planCliente = obtenerPlanInternoCliente($mainModel, trim((string)$data['db']));

    $data['server_customers_id'] = (int)$data['server_customers_id'];
    $data['clientes_id'] = (int)$data['clientes_id'];
    $data['planes_id'] = (int)$data['planes_id'];
    $data['sistema_id'] = (int)$data['sistema_id'];
    $data['validar'] = (int)$data['validar'];
    $data['estado'] = (int)$data['estado'];
    $data['user_extra'] = (int)$planCliente['user_extra'];
    $data['fecha_registro_plan_cliente'] = $planCliente['fecha_registro'];
    $data['db_disponible'] = (int)$planCliente['db_disponible'];
    $data['db_mensaje'] = $planCliente['db_mensaje'];

    responderVerificarPlanCliente([
        'success' => true,
        'exists' => true,
        'data' => $data
    ]);

} catch (Throwable $e) {
    responderVerificarPlanCliente([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}