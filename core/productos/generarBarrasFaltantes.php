<?php
// Generación masiva, limitada a la empresa de la sesión y a productos sin código.
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
header('Content-Type: application/json; charset=utf-8');

function responderBarras($ok, $mensaje, $count = 0) {
    echo json_encode(['success' => (bool)$ok, 'message' => (string)$mensaje, 'count' => (int)$count], JSON_UNESCAPED_UNICODE);
    exit;
}

$cn = null;
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        responderBarras(false, 'Método no permitido.');
    }
    $validacion = mainModel::validarSesion();
    if (!empty($validacion['error'])) {
        http_response_code(401);
        responderBarras(false, 'Debe iniciar sesión nuevamente.');
    }
    $empresaId = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $privilegio = (int)($_SESSION['privilegio_sd'] ?? 0);
    if ($empresaId <= 0 || !in_array($privilegio, [1, 2, 3, 4], true)) {
        http_response_code(403);
        responderBarras(false, 'No tiene permisos para modificar productos.');
    }
    $cn = (new mainModel())->connection();
    if (!$cn) {
        throw new RuntimeException('No fue posible abrir la base de datos.');
    }
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $cn->begin_transaction();
    $consultar = $cn->prepare("SELECT productos_id FROM productos WHERE empresa_id = ? AND (barCode IS NULL OR TRIM(barCode) = '' OR TRIM(barCode) = '0') ORDER BY productos_id ASC FOR UPDATE");
    $consultar->bind_param('i', $empresaId);
    $consultar->execute();
    $rs = $consultar->get_result();
    $ids = [];
    while ($fila = $rs->fetch_assoc()) {
        $ids[] = (int)$fila['productos_id'];
    }
    $consultar->close();
    if (!$ids) {
        $cn->commit();
        responderBarras(true, 'Todos los productos de esta empresa ya tienen un código registrado.', 0);
    }
    $existe = $cn->prepare('SELECT productos_id FROM productos WHERE empresa_id = ? AND barCode = ? LIMIT 1');
    $actualizar = $cn->prepare("UPDATE productos SET barCode = ? WHERE productos_id = ? AND empresa_id = ? AND (barCode IS NULL OR TRIM(barCode) = '' OR TRIM(barCode) = '0')");
    $count = 0;
    foreach ($ids as $id) {
        // Código 128-B interno, corto, estable y único por identificador de empresa/producto.
        $codigo = 'IZ' . $empresaId . '-' . $id;
        $existe->bind_param('is', $empresaId, $codigo);
        $existe->execute();
        if ($existe->get_result()->num_rows > 0) {
            throw new RuntimeException('Conflicto de código de barras para producto ' . $id . '. No se modificó ningún producto.');
        }
        $actualizar->bind_param('sii', $codigo, $id, $empresaId);
        $actualizar->execute();
        $count += $actualizar->affected_rows;
    }
    $existe->close();
    $actualizar->close();
    $cn->commit();
    responderBarras(true, 'Se generaron ' . $count . ' códigos de barra. Los códigos existentes no fueron modificados.', $count);
} catch (Throwable $e) {
    if ($cn instanceof mysqli) {
        try { $cn->rollback(); } catch (Throwable $ignored) {}
    }
    error_log('IZZY generarBarrasFaltantes: ' . $e->getMessage());
    http_response_code(500);
    responderBarras(false, 'No se pudieron generar los códigos. Revise el registro del servidor.');
}
