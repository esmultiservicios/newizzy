<?php
$peticionAjax = true;
require_once __DIR__ . '/configGenerales.php';
require_once __DIR__ . '/mainModel.php';
header('Content-Type: application/json; charset=utf-8');

$privilegioId = (int)($_POST['privilegio_id_accesos'] ?? 0);
if ($privilegioId <= 0) { echo json_encode(['data' => []]); exit; }

$connection = null;
$statement = null;
try {
    $model = new mainModel();
    $planId = (int)$model->obtener_planes_id_por_plan_id();
    if ($planId <= 0) { echo json_encode(['data' => []]); exit; }

    $connection = $model->connection();
    $sql = 'SELECT DISTINCT m.menu_id, m.name AS menu_name, m.descripcion, '
        . 'COALESCE((SELECT a.estado FROM acceso_menu a WHERE a.menu_id=m.menu_id AND a.privilegio_id=? ORDER BY a.acceso_menu_id DESC LIMIT 1),2) AS menu_estado '
        . 'FROM plan p INNER JOIN menu_plan mp ON mp.planes_id=p.planes_id '
        . 'INNER JOIN menu m ON m.menu_id=mp.menu_id '
        . 'WHERE p.planes_id=? AND mp.estado=1 ORDER BY m.orden, m.descripcion, m.menu_id';
    $statement = $connection->prepare($sql);
    if (!$statement) throw new RuntimeException($connection->error);
    $statement->bind_param('ii', $privilegioId, $planId);
    $statement->execute();
    $result = $statement->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $state = (int)$row['menu_estado'];
        $data[] = [
            'menu_id' => (int)$row['menu_id'], 'menu_name' => $row['menu_name'], 'descripcion' => $row['descripcion'],
            'estado' => $state, 'asignado' => $state === 1
        ];
    }
    echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('Accesos de menú: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['data' => [], 'error' => 'No se pudieron consultar los menús habilitados en el plan.'], JSON_UNESCAPED_UNICODE);
} finally {
    if ($statement) $statement->close();
    if ($connection) $connection->close();
}
