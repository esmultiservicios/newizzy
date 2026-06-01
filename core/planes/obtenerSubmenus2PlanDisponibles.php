<?php
// core/planes/obtenerSubmenus2PlanDisponibles.php

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
            s1.submenu1_id,
            s1.submenu_id,
            s1.name,
            s1.descripcion,
            s1.icon,
            s1.orden,
            s.name AS submenu_name,
            s.descripcion AS descripcion_padre,
            m.name AS menu_name,
            m.descripcion AS descripcion_menu,
            CASE
                WHEN sp.submenu1_id IS NOT NULL AND sp.estado = 1 THEN 1
                ELSE 0
            END AS asignado
        FROM submenu1 s1
        LEFT JOIN submenu1_plan sp ON s1.submenu1_id = sp.submenu1_id AND sp.planes_id = ?
        LEFT JOIN submenu s ON s1.submenu_id = s.submenu_id
        LEFT JOIN menu m ON s.menu_id = m.menu_id
        ORDER BY m.orden ASC, s.orden ASC, s1.orden ASC, s1.descripcion ASC
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
            'submenu1_id' => (int)$row['submenu1_id'],
            'name' => $row['name'],
            'submenu_id' => (int)$row['submenu_id'],
            'submenu_name' => $row['submenu_name'] ?? 'Sin submenú',
            'menu_name' => $row['menu_name'] ?? 'Sin menú',
            'asignado' => (bool)$row['asignado'],
            'descripcion' => $row['descripcion'] ?? '',
            'descripcion_padre' => $row['descripcion_padre'] ?? '',
            'descripcion_menu' => $row['descripcion_menu'] ?? '',
            'icon' => $row['icon'] ?? '',
            'orden' => (int)($row['orden'] ?? 0)
        ];
    }

    PlanesSyncHelper::responder(['success' => true, 'data' => $data]);

} catch (Exception $e) {
    PlanesSyncHelper::responder(['success' => false, 'message' => 'Error al obtener los submenús nivel 2: ' . $e->getMessage(), 'data' => []]);

} finally {
    if ($stmt) {
        $stmt->close();
    }
    if ($conexion) {
        $conexion->close();
    }
}
