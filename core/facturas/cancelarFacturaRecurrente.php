<?php
// Ubicación: core/facturas/cancelarFacturaRecurrente.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    $mainModel = new mainModel();
    $validacion = $mainModel->validarSesion();
    if (!empty($validacion['error'])) {
        throw new Exception($validacion['mensaje'] ?? 'Sesión inválida.');
    }

    $recId = (int)($_POST['rec_id'] ?? 0);
    $empresaId = (int)($_SESSION['empresa_id_sd'] ?? 0);
    if ($recId <= 0 || $empresaId <= 0) {
        throw new Exception('Recurrencia inválida.');
    }

    $cn = $mainModel->connection();
    $stmt = $cn->prepare(
        'UPDATE facturas_recurrentes SET estado = 2 WHERE rec_id = ? AND empresa_id = ? AND estado = 1 LIMIT 1'
    );
    $stmt->bind_param('ii', $recId, $empresaId);
    if (!$stmt->execute()) {
        throw new Exception('No se pudo cancelar la recurrencia: '.$stmt->error);
    }
    $afectadas = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'ok' => $afectadas > 0,
        'msg' => $afectadas > 0 ? 'Recurrencia cancelada correctamente.' : 'La recurrencia ya no está activa o no pertenece a esta empresa.'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'msg' => 'Error: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
