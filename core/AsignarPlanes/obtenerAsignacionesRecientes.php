<?php
// core/AsignarPlanes/obtenerAsignacionesRecientes.php

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
            'data' => [],
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function responderAsignaciones($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function nombreBaseDatosValidoAsignaciones($dbName) {
    $dbName = trim((string)$dbName);

    if ($dbName === '') {
        return false;
    }

    return preg_match('/^[a-zA-Z0-9_]+$/', $dbName) === 1;
}

function baseDatosExisteAsignaciones($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoAsignaciones($dbName)) {
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

function conectarBaseClienteSeguraAsignaciones($mainModel, $dbName) {
    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoAsignaciones($dbName)) {
        return null;
    }

    if (!baseDatosExisteAsignaciones($mainModel, $dbName)) {
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

function tablaExisteAsignaciones($conexion, $tabla) {
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

function obtenerDatosPlanClienteAsignaciones($mainModel, $dbName) {
    $datos = [
        "user_extra" => 0,
        "fecha_registro" => "",
        "db_disponible" => 0,
        "db_mensaje" => ""
    ];

    $dbName = trim((string)$dbName);

    if (!nombreBaseDatosValidoAsignaciones($dbName)) {
        $datos["db_mensaje"] = "Base de datos no registrada o nombre inválido";
        return $datos;
    }

    $conexionCliente = conectarBaseClienteSeguraAsignaciones($mainModel, $dbName);

    if (!$conexionCliente) {
        $datos["db_mensaje"] = "No se pudo conectar a la base del cliente o no existe";
        return $datos;
    }

    $stmt = null;

    try {
        $datos["db_disponible"] = 1;

        if (!tablaExisteAsignaciones($conexionCliente, "plan")) {
            $datos["db_mensaje"] = "La tabla plan no existe en la base del cliente";
            return $datos;
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
            $datos["db_mensaje"] = "No se pudo preparar la consulta del plan del cliente";
            return $datos;
        }

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();

            $datos["user_extra"] = isset($row["user_extra"]) ? (int)$row["user_extra"] : 0;
            $datos["fecha_registro"] = $row["fecha_registro"] ?? "";
            $datos["db_mensaje"] = "OK";
        } else {
            $datos["db_mensaje"] = "No hay registro en la tabla plan";
        }

    } catch (Throwable $e) {
        $datos["user_extra"] = 0;
        $datos["fecha_registro"] = "";
        $datos["db_disponible"] = 0;
        $datos["db_mensaje"] = "Error al consultar la base del cliente";

    } finally {
        if ($stmt) {
            $stmt->close();
        }

        $conexionCliente->close();
    }

    return $datos;
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
            c.nombre AS cliente_nombre,
            c.rtn AS identificacion,
            sc.planes_id,
            sc.validar,
            sc.estado,
            sc.codigo_cliente,
            sc.db,
            p.nombre AS plan_nombre,
            sc.sistema_id,
            s.nombre AS sistema_nombre
        FROM server_customers sc
        INNER JOIN clientes c ON sc.clientes_id = c.clientes_id
        INNER JOIN planes p ON sc.planes_id = p.planes_id
        INNER JOIN sistema s ON sc.sistema_id = s.sistema_id
        WHERE sc.db IS NOT NULL
          AND TRIM(sc.db) != ''
          AND sc.db_imported = 1
        ORDER BY c.nombre ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta principal: " . $conexion->error);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();

    if (!$resultado) {
        throw new Exception("No se pudo obtener el resultado de asignaciones.");
    }

    $asignaciones = [];

    while ($row = $resultado->fetch_assoc()) {
        $datosPlanCliente = obtenerDatosPlanClienteAsignaciones($mainModel, $row["db"]);

        $asignaciones[] = [
            "server_customers_id" => (int)$row["server_customers_id"],
            "cliente_id" => (int)$row["clientes_id"],
            "validar" => (int)$row["validar"],
            "estado" => (int)$row["estado"],
            "cliente" => [
                "nombre" => $row["cliente_nombre"],
                "identificacion" => $row["identificacion"],
                "codigo_cliente" => $row["codigo_cliente"]
            ],
            "planes_id" => (int)$row["planes_id"],
            "plan" => [
                "nombre" => $row["plan_nombre"]
            ],
            "sistema_id" => (int)$row["sistema_id"],
            "sistema" => [
                "sistema_id" => (int)$row["sistema_id"],
                "nombre" => $row["sistema_nombre"]
            ],
            "user_extra" => (int)$datosPlanCliente["user_extra"],
            "fecha_registro" => $datosPlanCliente["fecha_registro"],
            "db_cliente" => $row["db"],
            "db_disponible" => (int)$datosPlanCliente["db_disponible"],
            "db_mensaje" => $datosPlanCliente["db_mensaje"]
        ];
    }

    responderAsignaciones(true, $asignaciones, "");

} catch (Throwable $e) {
    responderAsignaciones(false, [], "Error en el servidor: " . $e->getMessage());

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}