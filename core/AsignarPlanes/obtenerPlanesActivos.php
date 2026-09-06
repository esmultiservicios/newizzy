<?php
// core/AsignarPlanes/obtenerPlanesActivos.php

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
    if (defined('DB_MAIN') && method_exists($mainModel, 'connectToDatabase')) {
        $conexion = $mainModel->connectToDatabase([
            "host" => SERVER,
            "user" => USER,
            "pass" => PASS,
            "name" => DB_MAIN
        ]);
    } else {
        $conexion = $mainModel->connection();
    }

    if (!$conexion) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $sql = "
        SELECT 
            planes_id,
            nombre
        FROM planes
        WHERE estado = 1
        ORDER BY nombre ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception("No se pudo preparar la consulta: " . $conexion->error);
    }

    $stmt->execute();

    $resultado = $stmt->get_result();
    $planes = [];

    while ($row = $resultado->fetch_assoc()) {
        $planes[] = [
            "planes_id" => (int)$row["planes_id"],
            "nombre" => $row["nombre"]
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $planes
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
