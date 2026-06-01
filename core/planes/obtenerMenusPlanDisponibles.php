<?php
// core/planes/obtenerMenusPlanDisponibles.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/PlanesSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();
$planId = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;

if ($planId <= 0) {
    PlanesSyncHelper::responder(['success' => false, 'message' => 'ID de plan no válido.', 'data' => []]);
}

$conexion = null;
$stmt = null;
$data = [];

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    $sql = "
        SELECT
            m.menu_id,
            m.name,
            m.descripcion,
            CASE
                WHEN mp.menu_id IS NOT NULL AND mp.estado = 1 THEN 1
                ELSE 0
            END AS asignado
        FROM menu m
        LEFT JOIN menu_plan mp ON m.menu_id = mp.menu_id AND mp.planes_id = ?
        ORDER BY m.orden ASC, m.descripcion ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param('i', $planId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($row = $resultado->fetch_assoc()) {
        $data[] = [
            'menu_id' => (int)$row['menu_id'],
            'name' => $row['descripcion'] ?? $row['name'],
            'asignado' => (bool)$row['asignado']
        ];
    }

    PlanesSyncHelper::responder(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    PlanesSyncHelper::responder(['success' => false, 'message' => 'Error al obtener los menús: ' . $e->getMessage(), 'data' => []]);

} finally {
    if ($stmt) {
        $stmt->close();
    }
    if ($conexion) {
        $conexion->close();
    }
}
