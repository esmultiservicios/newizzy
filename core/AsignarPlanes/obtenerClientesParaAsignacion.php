<?php
// core/AsignarPlanes/obtenerClientesParaAsignacion.php

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

function conectarPrincipalClientesAsignacion($mainModel) {
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

$conexion = null;
$stmt = null;

try {
    $conexion = conectarPrincipalClientesAsignacion($mainModel);

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $sql = "
        SELECT 
            sc.server_customers_id,
            sc.clientes_id,
            sc.sistema_id,
            sc.planes_id,
            sc.validar,
            sc.estado,
            sc.codigo_cliente,
            sc.db,
            c.nombre,
            c.rtn AS identificacion,
            s.nombre AS sistema_nombre
        FROM server_customers sc
        INNER JOIN clientes c ON sc.clientes_id = c.clientes_id
        INNER JOIN sistema s ON sc.sistema_id = s.sistema_id
        WHERE sc.estado = 1
          AND sc.db IS NOT NULL
          AND TRIM(sc.db) != ''
        ORDER BY c.nombre ASC, s.nombre ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta: " . $conexion->error);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();
    $clientes = [];

    while ($row = $resultado->fetch_assoc()) {
        $clientes[] = [
            "server_customers_id" => (int)$row["server_customers_id"],
            "clientes_id" => (int)$row["clientes_id"],
            "sistema_id" => (int)$row["sistema_id"],
            "planes_id" => (int)$row["planes_id"],
            "validar" => (int)$row["validar"],
            "estado" => (int)$row["estado"],
            "codigo_cliente" => $row["codigo_cliente"],
            "db" => $row["db"],
            "nombre" => $row["nombre"],
            "identificacion" => $row["identificacion"],
            "sistema" => [
                "nombre" => $row["sistema_nombre"]
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $clientes
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($stmt) {
        $stmt->close();
    }

    if ($conexion) {
        $conexion->close();
    }
}