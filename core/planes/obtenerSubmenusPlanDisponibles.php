<?php
// core/planes/obtenerSubmenusPlanDisponibles.php

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
            s.submenu_id,
            s.menu_id,
            s.name,
            s.descripcion,
            s.icon,
            s.orden,
            m.name AS menu_name,
            m.descripcion AS descripcion_padre,
            CASE
                WHEN sp.submenu_id IS NOT NULL AND sp.estado = 1 THEN 1
                ELSE 0
            END AS asignado
        FROM submenu s
        LEFT JOIN submenu_plan sp ON s.submenu_id = sp.submenu_id AND sp.planes_id = ?
        LEFT JOIN menu m ON s.menu_id = m.menu_id
        ORDER BY m.orden ASC, s.orden ASC, s.descripcion ASC
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
            'submenu_id' => (int)$row['submenu_id'],
            'name' => $row['name'],
            'menu_id' => (int)$row['menu_id'],
            'menu_name' => $row['menu_name'] ?? '',
            'descripcion_padre' => $row['descripcion_padre'] ?? '',
            'asignado' => (bool)$row['asignado'],
            'descripcion' => $row['descripcion'] ?? '',
            'icon' => $row['icon'] ?? '',
            'orden' => (int)($row['orden'] ?? 0)
        ];
    }

    PlanesSyncHelper::responder(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    PlanesSyncHelper::responder(['success' => false, 'message' => 'Error al obtener los submenús: ' . $e->getMessage(), 'data' => []]);

} finally {
    if ($stmt) {
        $stmt->close();
    }
    if ($conexion) {
        $conexion->close();
    }
}
