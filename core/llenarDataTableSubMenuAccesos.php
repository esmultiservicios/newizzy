<?php
$peticionAjax = true;
require_once __DIR__ . '/configGenerales.php';
require_once __DIR__ . '/mainModel.php';
header('Content-Type: application/json; charset=utf-8');

$privilegioId = (int)($_POST['privilegio_id_accesos'] ?? 0);
$empty = ['echo' => 1, 'totalrecords' => 0, 'totaldisplayrecords' => 0, 'data' => []];
if ($privilegioId <= 0) { echo json_encode($empty); exit; }

$connection = null;
$statement = null;
try {
    $model = new mainModel();
    $planId = (int)$model->obtener_planes_id_por_plan_id();
    if ($planId <= 0) { echo json_encode($empty); exit; }

    $connection = $model->connection();
    $sql = 'SELECT DISTINCT sm.submenu_id, sm.menu_id, sm.name AS submenu_name, m.name AS menu_name, '
        . 'sm.descripcion, m.descripcion AS descripcion_padre, '
        . 'COALESCE((SELECT a.estado FROM acceso_submenu a WHERE a.submenu_id=sm.submenu_id AND a.privilegio_id=? ORDER BY a.acceso_submenu_id DESC LIMIT 1),2) AS submenu_estado '
        . 'FROM plan p INNER JOIN submenu_plan sp ON sp.planes_id=p.planes_id '
        . 'INNER JOIN submenu sm ON sm.submenu_id=sp.submenu_id '
        . 'INNER JOIN menu m ON m.menu_id=sm.menu_id '
        . 'INNER JOIN menu_plan mp ON mp.menu_id=m.menu_id AND mp.planes_id=p.planes_id AND mp.estado=1 '
        . 'WHERE p.planes_id=? AND sp.estado=1 '
        . 'ORDER BY m.orden, m.descripcion, sm.orden, sm.descripcion, sm.submenu_id';
    $statement = $connection->prepare($sql);
    if (!$statement) throw new RuntimeException($connection->error);
    $statement->bind_param('ii', $privilegioId, $planId);
    $statement->execute();
    $result = $statement->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $state = (int)$row['submenu_estado'];
        $data[] = [
            'submenu_id' => (int)$row['submenu_id'], 'menu_id' => (int)$row['menu_id'],
            'submenu_name' => $row['submenu_name'], 'menu_name' => $row['menu_name'],
            'descripcion' => $row['descripcion'], 'descripcion_padre' => $row['descripcion_padre'],
            'estado' => $state, 'asignado' => $state === 1
        ];
    }
    echo json_encode(['echo' => 1, 'totalrecords' => count($data), 'totaldisplayrecords' => count($data), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Accesos de submenú nivel 1: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode($empty + ['error' => 'No se pudieron consultar los submenús habilitados en el plan.'], JSON_UNESCAPED_UNICODE);
} finally {
    if ($statement) $statement->close();
    if ($connection) $connection->close();
}
