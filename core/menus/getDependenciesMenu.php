<?php
/** Devuelve las dependencias del formulario de administración de menús. */
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/MenuSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function responderDependenciasMenu($success, array $data = [], $message = '', $status = 200)
{
    http_response_code($status);
    echo json_encode([
        'success' => (bool)$success,
        'data' => $data,
        'message' => (string)$message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$model = new mainModel();
$validacion = mainModel::validarSesion();
if (!empty($validacion['error'])) {
    responderDependenciasMenu(false, [], $validacion['mensaje'] ?? 'La sesión no es válida.', 401);
}

$tipo = trim((string)($_POST['tipo'] ?? ''));
if (!in_array($tipo, ['getMenus', 'getAllSubmenus', 'getSubmenusByMenu'], true)) {
    responderDependenciasMenu(false, [], 'Tipo de dependencia no válido.', 400);
}

$conexion = null;
$stmt = null;
try {
    $conexion = MenuSyncHelper::conectarPrincipal($model);
    if (!$conexion) throw new RuntimeException('No se pudo conectar a la base principal.');
    MenuSyncHelper::validarEstructuraMenus($conexion);

    if ($tipo === 'getMenus') {
        $sql = 'SELECT menu_id AS id, name, descripcion FROM menu ORDER BY orden ASC, menu_id ASC';
        $stmt = $conexion->prepare($sql);
    } elseif ($tipo === 'getAllSubmenus') {
        $sql = 'SELECT s.submenu_id AS id, s.name, '
             . 'CONCAT(COALESCE(m.descripcion, "Menú"), " · ", s.descripcion) AS descripcion '
             . 'FROM submenu s INNER JOIN menu m ON m.menu_id=s.menu_id '
             . 'ORDER BY m.orden ASC, s.orden ASC, s.submenu_id ASC';
        $stmt = $conexion->prepare($sql);
    } else {
        $menuId = filter_var($_POST['menu_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$menuId || $menuId < 1) responderDependenciasMenu(false, [], 'Seleccione un menú principal válido.', 422);
        $sql = 'SELECT s.submenu_id AS id, s.name, s.descripcion '
             . 'FROM submenu s WHERE s.menu_id=? ORDER BY s.orden ASC, s.submenu_id ASC';
        $stmt = $conexion->prepare($sql);
        if ($stmt) $stmt->bind_param('i', $menuId);
    }

    if (!$stmt) throw new RuntimeException('No se pudo preparar la consulta de dependencias: ' . $conexion->error);
    if (!$stmt->execute()) throw new RuntimeException('No se pudieron consultar las dependencias: ' . $stmt->error);
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'id' => (int)$row['id'],
            'nombre' => (string)($row['name'] ?? ''),
            'descripcion' => (string)($row['descripcion'] ?? '')
        ];
    }
    responderDependenciasMenu(true, $data);
} catch (Throwable $e) {
    error_log('Dependencias de menús: ' . $e->getMessage());
    responderDependenciasMenu(false, [], 'No se pudieron consultar las dependencias en la base principal.', 500);
} finally {
    if ($stmt) $stmt->close();
    if ($conexion) $conexion->close();
}
