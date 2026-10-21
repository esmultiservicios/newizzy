<?php
// Administración de ciclos omitidos desde el modal Facturas Recurrentes.
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';
require_once __DIR__.'/RecurrenciasOmisiones.php';
header('Content-Type: application/json; charset=utf-8');

function responderOmisiones(bool $ok, string $msg, array $data = []): void
{
    echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $model = new mainModel();
    $sesion = $model->validarSesion();
    if (!empty($sesion['error'])) throw new RuntimeException('Sesión inválida.');
    $empresa = (int)($_SESSION['empresa_id_sd'] ?? 0);
    $recId = filter_input(INPUT_POST, 'rec_id', FILTER_VALIDATE_INT) ?: (int)($_GET['rec_id'] ?? 0);
    if ($recId <= 0 || $empresa <= 0) throw new RuntimeException('Recurrencia inválida.');
    $cn = $model->connection();
    izzyRecurrenciasAsegurarOmisiones($cn);
    $st = $cn->prepare('SELECT * FROM facturas_recurrentes WHERE rec_id = ? AND empresa_id = ? LIMIT 1');
    $st->bind_param('ii', $recId, $empresa);
    $st->execute();
    $rec = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$rec) throw new RuntimeException('Recurrencia no encontrada en esta empresa.');
    $accion = (string)($_POST['accion'] ?? 'listar');
    if ($accion !== 'listar' && $_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Método no permitido.');
    if ($accion !== 'listar' && (($_SESSION['modo_solo_lectura_pago'] ?? '') === 'SI')) throw new RuntimeException('Cuenta en modo consulta.');
    if ($accion === 'agregar') {
        if ((int)$rec['estado'] !== 1) throw new RuntimeException('Esta recurrencia no está activa.');
        $periodo = trim((string)($_POST['periodo'] ?? ''));
        $fecha = izzyRecurrenciasResolverCiclo($rec, $periodo);
        if (!$fecha || $fecha < date('Y-m-d H:i:s')) throw new RuntimeException('Seleccione un período futuro correspondiente a esta recurrencia.');
        $st = $cn->prepare("INSERT INTO facturas_recurrentes_omisiones (rec_id, scheduled_at, motivo, observacion) VALUES (?, ?, 'manual', 'Excluido desde administración') ON DUPLICATE KEY UPDATE motivo = motivo");
        $st->bind_param('is', $recId, $fecha);
        if (!$st->execute()) throw new RuntimeException('No se pudo guardar la exclusión.');
        $st->close();
    } elseif ($accion === 'quitar') {
        $fecha = (string)($_POST['scheduled_at'] ?? '');
        if (!preg_match('/^20\d{2}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fecha) || $fecha < date('Y-m-d H:i:s')) throw new RuntimeException('No es posible reactivar un ciclo anterior.');
        $st = $cn->prepare("DELETE FROM facturas_recurrentes_omisiones WHERE rec_id = ? AND scheduled_at = ? AND motivo = 'manual'");
        $st->bind_param('is', $recId, $fecha);
        if (!$st->execute()) throw new RuntimeException('No se pudo restaurar el ciclo.');
        $st->close();
    } elseif ($accion !== 'listar') {
        throw new RuntimeException('Acción desconocida.');
    }
    $st = $cn->prepare("SELECT scheduled_at, motivo FROM facturas_recurrentes_omisiones WHERE rec_id = ? AND motivo = 'manual' AND scheduled_at >= NOW() ORDER BY scheduled_at");
    $st->bind_param('i', $recId);
    $st->execute();
    $rs = $st->get_result();
    $datos = [];
    while ($fila = $rs->fetch_assoc()) $datos[] = $fila;
    $st->close();
    responderOmisiones(true, $accion === 'listar' ? 'Exclusiones cargadas.' : 'Exclusiones actualizadas.', $datos);
} catch (Throwable $error) {
    http_response_code(400);
    responderOmisiones(false, $error->getMessage());
}
