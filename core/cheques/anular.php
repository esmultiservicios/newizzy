<?php
require_once __DIR__ . '/_bootstrap.php';

chequesSoloPost();
$sesion = chequesSesion();
$db = chequesDb();
chequesValidarInstalacion($db);
chequesValidarTokenAdmin($_POST['token'] ?? '');

$chequeId = isset($_POST['cheque_id']) ? (int)$_POST['cheque_id'] : 0;
$motivo = chequesTexto($_POST['motivo'] ?? '', 255);

if ($chequeId <= 0) {
    chequesJson(['success'=>false,'title'=>'Cheque inválido','message'=>'No se recibió un cheque válido.']);
}
if ($motivo === '') {
    chequesJson(['success'=>false,'title'=>'Motivo requerido','message'=>'Indique el motivo de la anulación.']);
}

$movimientoReintegroId = 0;
$saldoAntes = 0;
$saldoDespues = 0;

try {
    if (!$db->query("LOCK TABLES cheque WRITE, egresos WRITE, movimientos_cuentas WRITE, cuentas READ")) {
        throw new Exception('No se pudo bloquear la información para anular el cheque.');
    }

    $stmt = $db->prepare("
        SELECT cheque_id, numero_cheque, cuentas_id, empresa_id, egresos_id, importe, estado
        FROM cheque
        WHERE cheque_id=? AND empresa_id=?
        LIMIT 1
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param('ii', $chequeId, $sesion['empresa_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) throw new Exception('El cheque no existe o no pertenece a la empresa actual.');
    if ((int)$row['estado'] !== 1) throw new Exception('El cheque ya se encuentra anulado.');

    $cuentaId = (int)$row['cuentas_id'];
    $egresoId = (int)$row['egresos_id'];
    $importe = round((float)$row['importe'], 2);

    $saldoAntes = chequesObtenerSaldoActual($db, $cuentaId, $sesion['empresa_id']);
    $saldoDespues = round($saldoAntes + $importe, 2);

    $movimientoReintegroId = chequesSiguienteId($db, 'movimientos_cuentas', 'movimientos_cuentas_id');
    $fecha = date('Y-m-d');
    $fechaRegistro = date('Y-m-d H:i:s');

    $stmt = $db->prepare("
        INSERT INTO movimientos_cuentas (
            movimientos_cuentas_id, cuentas_id, empresa_id, fecha,
            ingreso, egreso, saldo, colaboradores_id, fecha_registro
        ) VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param(
        'iiisddis',
        $movimientoReintegroId, $cuentaId, $sesion['empresa_id'], $fecha,
        $importe, $saldoDespues, $sesion['colaboradores_id'], $fechaRegistro
    );
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    if ($egresoId > 0) {
        $stmt = $db->prepare("UPDATE egresos SET estado=0 WHERE egresos_id=? AND empresa_id=? LIMIT 1");
        if (!$stmt) throw new Exception($db->error);
        $stmt->bind_param('ii', $egresoId, $sesion['empresa_id']);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();
    }

    $stmt = $db->prepare("
        UPDATE cheque
        SET estado=0,
            fecha_anulacion=?,
            colaboradores_anula_id=?,
            motivo_anulacion=?,
            movimiento_reintegro_id=?
        WHERE cheque_id=? AND empresa_id=? AND estado=1
        LIMIT 1
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param(
        'sisiii',
        $fechaRegistro, $sesion['colaboradores_id'], $motivo,
        $movimientoReintegroId, $chequeId, $sesion['empresa_id']
    );
    if (!$stmt->execute() || $stmt->affected_rows !== 1) throw new Exception('No se pudo marcar el cheque como anulado.');
    $stmt->close();

    chequesDesbloquear($db);

    chequesJson([
        'success'=>true,
        'title'=>'Cheque anulado',
        'message'=>'El cheque fue anulado, el egreso quedó inactivo y el importe fue reintegrado a la cuenta.',
        'movimiento_reintegro_id'=>$movimientoReintegroId,
        'saldo_anterior'=>number_format($saldoAntes,2,'.',''),
        'saldo_nuevo'=>number_format($saldoDespues,2,'.','')
    ]);

} catch (Throwable $e) {
    // Si se insertó el reintegro pero no se completó la anulación, eliminarlo.
    if ($movimientoReintegroId > 0) {
        $db->query("DELETE FROM movimientos_cuentas WHERE movimientos_cuentas_id=".(int)$movimientoReintegroId." LIMIT 1");
    }
    // El egreso solo pudo pasar a 0 después del reintegro. Si el cheque sigue activo, restituimos egreso.
    $res = $db->query("SELECT estado, egresos_id FROM cheque WHERE cheque_id=".(int)$chequeId." LIMIT 1");
    if ($res && ($ch=$res->fetch_assoc()) && (int)$ch['estado']===1 && (int)$ch['egresos_id']>0) {
        $db->query("UPDATE egresos SET estado=1 WHERE egresos_id=".(int)$ch['egresos_id']." LIMIT 1");
    }

    chequesDesbloquear($db);
    error_log('CHEQUES anular: '.$e->getMessage());

    chequesJson([
        'success'=>false,
        'title'=>'No se pudo anular',
        'message'=>$e->getMessage()
    ],500);
}
