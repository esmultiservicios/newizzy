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
            sc.sistema_id,
            sc.planes_id,
            sc.validar,
            sc.estado,
            c.nombre,
            c.rtn AS identificacion
        FROM server_customers sc
        INNER JOIN clientes c ON sc.clientes_id = c.clientes_id
        WHERE sc.db IS NOT NULL
          AND sc.db <> ''
          AND sc.db_imported = 1
        ORDER BY c.nombre ASC
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
            "nombre" => $row["nombre"],
            "identificacion" => $row["identificacion"]
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $clientes
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);

} finally {
    if ($stmt) { $stmt->close(); }
    if ($conexion) { $conexion->close(); }
}
