<?php
// Ruta: core/facturas/administrarOmisionesRecurrentes.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';
require_once __DIR__.'/RecurrenciasOmisiones.php';
header('Content-Type: application/json; charset=utf-8');
function izzyOmisionRespuesta(bool $ok, string $mensaje='', array $data=[]): void {
    echo json_encode(['ok'=>$ok, 'msg'=>$mensaje, 'data'=>$data], JSON_UNESCAPED_UNICODE);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); izzyOmisionRespuesta(false, 'Método no permitido.'); }
$auth = mainModel::validarSesion();
if (!empty($auth['error']) || empty($_SESSION['empresa_id_sd']) || empty($_SESSION['users_id_sd'])) {
    http_response_code(401); izzyOmisionRespuesta(false, 'Debe iniciar sesión.');
}
$csrfSesion = (string)($_SESSION['token_sd'] ?? '');
$csrfEsperado = hash_hmac('sha256', 'izzy-omisiones', $csrfSesion);
if ($csrfSesion === '' || !hash_equals($csrfEsperado, (string)($_POST['csrf_token'] ?? ''))) {
    http_response_code(403); izzyOmisionRespuesta(false, 'Solicitud no autorizada. Recargue la página.');
}
// Acceso limitado a administradores. Los permisos adicionales pueden integrarse con el ACL existente.
$priv = (int)($_SESSION['privilegio_sd'] ?? 0);
if (!in_array($priv, [1, 2], true)) { http_response_code(403); izzyOmisionRespuesta(false, 'No tiene permiso para administrar exclusiones.'); }
$recId = filter_var($_POST['rec_id'] ?? null, FILTER_VALIDATE_INT);
$accion = (string)($_POST['accion'] ?? '');
if (!$recId || $recId < 1 || !in_array($accion, ['listar','agregar','quitar'], true)) {
    http_response_code(422); izzyOmisionRespuesta(false, 'Solicitud inválida.');
}
try {
    $cn = (new mainModel())->connection();
    izzyRecurrenciasAsegurarOmisiones($cn);
    $empresaId = (int)$_SESSION['empresa_id_sd'];
    $st = $cn->prepare('SELECT rec_id, empresa_id, periodicidad, start_at, next_run_at, until_at, dia_mes, estado FROM facturas_recurrentes WHERE rec_id = ? AND empresa_id = ? LIMIT 1');
    if (!$st) throw new RuntimeException('No se pudo consultar la programación.');
    $st->bind_param('ii', $recId, $empresaId);
    $st->execute(); $rec = $st->get_result()->fetch_assoc(); $st->close();
    if (!$rec) { http_response_code(404); izzyOmisionRespuesta(false, 'Programación no encontrada.'); }
    if ($accion === 'listar') {
        $st = $cn->prepare("SELECT scheduled_at, motivo, observacion FROM facturas_recurrentes_omisiones WHERE rec_id = ? AND motivo = 'manual' ORDER BY scheduled_at ASC");
        $st->bind_param('i', $recId); $st->execute(); $rs = $st->get_result();
        $rows=[]; while ($r=$rs->fetch_assoc()) $rows[]=$r; $st->close();
        izzyOmisionRespuesta(true,'',$rows);
    }
    if ((int)$rec['estado'] !== 1) { http_response_code(409); izzyOmisionRespuesta(false,'La programación no está activa.'); }
    if ($accion === 'agregar') {
        $periodo=trim((string)($_POST['periodo'] ?? ''));
        $ciclo=izzyRecurrenciasResolverCiclo($rec,$periodo);
        if (!$ciclo || $ciclo < date('Y-m-d H:i:s')) {
            http_response_code(422); izzyOmisionRespuesta(false,'Seleccione un ciclo programado futuro.');
        }
        $st = $cn->prepare('SELECT ejecucion_id FROM facturas_recurrentes_ejecuciones WHERE rec_id = ? AND scheduled_at = ? LIMIT 1');
        $st->bind_param('is',$recId,$ciclo); $st->execute(); $ya=$st->get_result()->fetch_assoc(); $st->close();
        if ($ya) { http_response_code(409); izzyOmisionRespuesta(false,'Ese período ya tiene una ejecución registrada.'); }
        $motivo='manual'; $observacion='Período excluido por el administrador';
        $st=$cn->prepare('INSERT INTO facturas_recurrentes_omisiones (rec_id, scheduled_at, motivo, observacion) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE motivo=VALUES(motivo), observacion=VALUES(observacion)');
        $st->bind_param('isss',$recId,$ciclo,$motivo,$observacion);
        if (!$st->execute()) throw new RuntimeException('No se pudo guardar la omisión.'); $st->close();
        izzyOmisionRespuesta(true,'La siguiente factura de ese período será omitida.');
    }
    $ciclo=trim((string)($_POST['scheduled_at'] ?? ''));
    if (!preg_match('/^20\d{2}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',$ciclo)) {
        http_response_code(422); izzyOmisionRespuesta(false,'Fecha inválida.');
    }
    if ($ciclo < date('Y-m-d H:i:s')) { http_response_code(409); izzyOmisionRespuesta(false,'No puede restaurar un período ya vencido.'); }
    $st=$cn->prepare("DELETE FROM facturas_recurrentes_omisiones WHERE rec_id=? AND scheduled_at=? AND motivo='manual'");
    $st->bind_param('is',$recId,$ciclo);
    if (!$st->execute()) throw new RuntimeException('No se pudo restaurar el período.'); $st->close();
    izzyOmisionRespuesta(true,'Período restaurado.');
} catch (Throwable $e) {
    error_log('IZZY omisiones recurrentes: '.$e->getMessage());
    http_response_code(500); izzyOmisionRespuesta(false,'No se pudo completar la operación. Revise el registro del servidor.');
}
