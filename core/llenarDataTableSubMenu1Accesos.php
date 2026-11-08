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
    $sql = 'SELECT DISTINCT s1.submenu1_id, s1.submenu_id, s.menu_id, s1.name AS submenu1_name, '
        . 's.name AS submenu_name, m.name AS menu_name, s.descripcion, '
        . 'm.descripcion AS descripcion_padre, s1.descripcion AS submenu_descripcion, '
        . 'COALESCE((SELECT a.estado FROM acceso_submenu1 a WHERE a.submenu1_id=s1.submenu1_id '
        . 'AND a.privilegio_id=? ORDER BY a.acceso_submenu1_id DESC LIMIT 1),2) AS submenu1_estado '
        . 'FROM plan p INNER JOIN submenu1_plan sp1 ON sp1.planes_id=p.planes_id '
        . 'INNER JOIN submenu1 s1 ON s1.submenu1_id=sp1.submenu1_id '
        . 'INNER JOIN submenu s ON s.submenu_id=s1.submenu_id '
        . 'INNER JOIN menu m ON m.menu_id=s.menu_id '
        . 'INNER JOIN submenu_plan sp ON sp.submenu_id=s.submenu_id AND sp.planes_id=p.planes_id AND sp.estado=1 '
        . 'INNER JOIN menu_plan mp ON mp.menu_id=m.menu_id AND mp.planes_id=p.planes_id AND mp.estado=1 '
        . 'WHERE p.planes_id=? AND sp1.estado=1 '
        . 'ORDER BY m.orden, m.descripcion, s.orden, s.descripcion, s1.orden, s1.descripcion, s1.submenu1_id';
    $statement = $connection->prepare($sql);
    if (!$statement) throw new RuntimeException($connection->error);
    $statement->bind_param('ii', $privilegioId, $planId);
    $statement->execute();
    $result = $statement->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $state = (int)$row['submenu1_estado'];
        $data[] = [
            'submenu1_id' => (int)$row['submenu1_id'], 'submenu_id' => (int)$row['submenu_id'],
            'menu_id' => (int)$row['menu_id'], 'submenu1_name' => $row['submenu1_name'],
            'submenu_name' => $row['submenu_name'], 'menu_name' => $row['menu_name'],
            'descripcion' => $row['descripcion'], 'descripcion_padre' => $row['descripcion_padre'],
            'submenu_descripcion' => $row['submenu_descripcion'], 'estado' => $state,
            'asignado' => $state === 1
        ];
    }
    echo json_encode(['echo' => 1, 'totalrecords' => count($data), 'totaldisplayrecords' => count($data), 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Accesos de submenú nivel 2: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode($empty + ['error' => 'No se pudieron consultar los submenús nivel 2 habilitados en el plan.'], JSON_UNESCAPED_UNICODE);
} finally {
    if ($statement) $statement->close();
    if ($connection) $connection->close();
}
