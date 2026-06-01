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

function tablaExisteAsignaciones($conexion, $tabla) {
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

    return isset($row['total']) && (int)$row['total'] > 0;
}

function obtenerDatosPlanClienteAsignaciones($mainModel, $dbName) {
    $datos = [
        "user_extra" => 0,
        "fecha_registro" => ""
    ];

    $dbName = trim((string)$dbName);

    if ($dbName === "") {
        return $datos;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        return $datos;
    }

    if (!method_exists($mainModel, "connectToDatabase")) {
        return $datos;
    }

    $configCliente = [
        "host" => SERVER,
        "user" => USER,
        "pass" => PASS,
        "name" => $dbName
    ];

    $conexionCliente = $mainModel->connectToDatabase($configCliente);

    if (!$conexionCliente) {
        return $datos;
    }

    $stmt = null;

    try {
        if (!tablaExisteAsignaciones($conexionCliente, "plan")) {
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
            return $datos;
        }

        $stmt->execute();

        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows > 0) {
            $row = $resultado->fetch_assoc();

            $datos["user_extra"] = isset($row["user_extra"]) ? (int)$row["user_extra"] : 0;
            $datos["fecha_registro"] = $row["fecha_registro"] ?? "";
        }

    } catch (Exception $e) {
        return $datos;

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

        $fechaRegistro = $datosPlanCliente["fecha_registro"];

        if ($fechaRegistro === "") {
            $fechaRegistro = date("Y-m-d H:i:s");
        }

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
            "fecha_registro" => $fechaRegistro
        ];
    }

    responderAsignaciones(true, $asignaciones, "");

} catch (Exception $e) {
    responderAsignaciones(false, [], "Error en el servidor: " . $e->getMessage());

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}