<?php
// core/facturas/cancelarFacturaRecurrente.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
    }

    $id = (int)($_POST['rec_id'] ?? 0);
    if ($id<=0) { echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit; }

    $mainModel = new mainModel();
    $cn = $mainModel->connection();

    $stmt = $cn->prepare("UPDATE facturas_recurrentes SET estado=2 WHERE rec_id=? LIMIT 1");
    $stmt->bind_param("i",$id);
    if (!$stmt->execute()) {
        throw new Exception("Error al cancelar: ".$stmt->error);
    }
    $stmt->close();

    echo json_encode(['ok'=>true,'msg'=>'Recurrencia cancelada']); exit;

} catch (Throwable $e) {
    echo json_encode(['ok'=>false,'msg'=>'Error: '.$e->getMessage()]); exit;
}
