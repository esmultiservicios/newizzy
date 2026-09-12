<?php
require_once __DIR__ . '/_bootstrap.php';

chequesSoloPost();
$sesion = chequesSesion();
$db = chequesDb();
chequesValidarInstalacion($db);

$fecha = isset($_POST['fecha']) ? trim($_POST['fecha']) : '';
$numeroCheque = chequesTexto($_POST['numero_cheque'] ?? '', 30);
$proveedorId = isset($_POST['proveedores_id']) ? (int)$_POST['proveedores_id'] : 0;
$categoriaId = isset($_POST['categoria_gastos_id']) ? (int)$_POST['categoria_gastos_id'] : 0;
$factura = chequesTexto($_POST['factura'] ?? '', 50);
$importe = isset($_POST['importe']) ? round((float)$_POST['importe'], 2) : 0;
$observacion = chequesTexto($_POST['observacion'] ?? '', 255);

if (!chequesFechaValida($fecha)) {
    chequesJson(['success'=>false,'title'=>'Fecha inválida','message'=>'Seleccione una fecha válida para el cheque.']);
}
if ($numeroCheque === '') {
    chequesJson(['success'=>false,'title'=>'Número requerido','message'=>'Ingrese el número del cheque.']);
}
if ($proveedorId <= 0) {
    chequesJson(['success'=>false,'title'=>'Proveedor requerido','message'=>'Seleccione el proveedor o beneficiario del cheque.']);
}
if ($categoriaId <= 0) {
    chequesJson(['success'=>false,'title'=>'Categoría requerida','message'=>'Seleccione la categoría de gasto.']);
}
if ($importe <= 0) {
    chequesJson(['success'=>false,'title'=>'Importe inválido','message'=>'El importe del cheque debe ser mayor a cero.']);
}

$cuentaCheque = chequesCuentaTipoPago($db, 4, $sesion['empresa_id']);
if (!$cuentaCheque) {
    chequesJson([
        'success'=>false,
        'title'=>'Cuenta de cheques no configurada',
        'message'=>'Configure una cuenta contable para el tipo de pago Cheque antes de emitir.'
    ]);
}

$cuentaId = (int)$cuentaCheque['cuentas_id'];
$saldoActual = round((float)$cuentaCheque['saldo'], 2);

if ($importe > $saldoActual) {
    chequesJson([
        'success'=>false,
        'title'=>'Saldo insuficiente',
        'message'=>'La cuenta configurada no tiene saldo suficiente. Disponible: L. '.number_format($saldoActual,2)
    ]);
}

$stmt = $db->prepare("SELECT cheque_id FROM cheque WHERE empresa_id=? AND numero_cheque=? LIMIT 1");
$stmt->bind_param('is', $sesion['empresa_id'], $numeroCheque);
$stmt->execute();
$dup = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($dup) {
    chequesJson([
        'success'=>false,
        'title'=>'Cheque duplicado',
        'message'=>'Ya existe un cheque con el número '.$numeroCheque.' para esta empresa.'
    ]);
}

$stmt = $db->prepare("SELECT proveedores_id FROM proveedores WHERE proveedores_id=? AND estado=1 LIMIT 1");
$stmt->bind_param('i', $proveedorId);
$stmt->execute();
$proveedorOk = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$proveedorOk) {
    chequesJson(['success'=>false,'title'=>'Proveedor inválido','message'=>'El proveedor seleccionado no existe o está inactivo.']);
}

$stmt = $db->prepare("SELECT categoria_gastos_id FROM categoria_gastos WHERE categoria_gastos_id=? AND estado=1 LIMIT 1");
$stmt->bind_param('i', $categoriaId);
$stmt->execute();
$categoriaOk = $stmt->get_result()->num_rows > 0;
$stmt->close();
if (!$categoriaOk) {
    chequesJson(['success'=>false,'title'=>'Categoría inválida','message'=>'La categoría seleccionada no existe o está inactiva.']);
}

$fechaRegistro = date('Y-m-d H:i:s');
$saldoNuevo = round($saldoActual - $importe, 2);
$chequeId = 0;
$egresoId = 0;
$movimientoId = 0;

try {
    // MyISAM: el lock evita que dos procesos calculen el mismo saldo/correlativo al mismo tiempo.
    if (!$db->query("LOCK TABLES cheque WRITE, egresos WRITE, movimientos_cuentas WRITE, tipo_pago READ, cuentas READ, proveedores READ, categoria_gastos READ")) {
        throw new Exception('No se pudo bloquear las tablas para registrar el cheque.');
    }

    // Recalcular saldo ya dentro del lock.
    $saldoActual = chequesObtenerSaldoActual($db, $cuentaId, $sesion['empresa_id']);
    if ($importe > $saldoActual) {
        throw new Exception('Saldo insuficiente. Disponible: L. '.number_format($saldoActual,2));
    }
    $saldoNuevo = round($saldoActual - $importe, 2);

    $chequeId = chequesSiguienteId($db, 'cheque', 'cheque_id');
    $egresoId = chequesSiguienteId($db, 'egresos', 'egresos_id');
    $movimientoId = chequesSiguienteId($db, 'movimientos_cuentas', 'movimientos_cuentas_id');

    $tipoEgreso = 2;
    $estado = 1;
    $cero = 0.00;

    $stmt = $db->prepare("
        INSERT INTO egresos (
            egresos_id, cuentas_id, proveedores_id, empresa_id, tipo_egreso,
            fecha, factura, factura_pdf, subtotal, descuento, nc, impuesto,
            total, observacion, estado, colaboradores_id, fecha_registro, categoria_gastos_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param(
        'iiiiissdddddsiisi',
        $egresoId, $cuentaId, $proveedorId, $sesion['empresa_id'], $tipoEgreso,
        $fecha, $factura, $importe, $cero, $cero, $cero, $importe,
        $observacion, $estado, $sesion['colaboradores_id'], $fechaRegistro, $categoriaId
    );
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    $stmt = $db->prepare("
        INSERT INTO movimientos_cuentas (
            movimientos_cuentas_id, cuentas_id, empresa_id, fecha,
            ingreso, egreso, saldo, colaboradores_id, fecha_registro
        ) VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?)
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param(
        'iiisddis',
        $movimientoId, $cuentaId, $sesion['empresa_id'], $fecha,
        $importe, $saldoNuevo, $sesion['colaboradores_id'], $fechaRegistro
    );
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    $tipoPagoId = 4;
    $stmt = $db->prepare("
        INSERT INTO cheque (
            cheque_id, numero_cheque, cuentas_id, proveedores_id, empresa_id,
            tipo_pago_id, egresos_id, categoria_gastos_id, movimientos_cuentas_id,
            movimiento_reintegro_id, fecha, factura, importe, observacion,
            estado, colaboradores_id, fecha_registro, fecha_anulacion,
            colaboradores_anula_id, motivo_anulacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, 1, ?, ?, NULL, NULL, NULL)
    ");
    if (!$stmt) throw new Exception($db->error);
    $stmt->bind_param(
        'isiiiiiiissdsis',
        $chequeId, $numeroCheque, $cuentaId, $proveedorId, $sesion['empresa_id'],
        $tipoPagoId, $egresoId, $categoriaId, $movimientoId,
        $fecha, $factura, $importe, $observacion, $sesion['colaboradores_id'], $fechaRegistro
    );
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $stmt->close();

    chequesDesbloquear($db);

    chequesJson([
        'success'=>true,
        'title'=>'Cheque emitido',
        'message'=>'Cheque '.$numeroCheque.' registrado correctamente. Se creó el egreso y se debitó la cuenta contable.',
        'cheque_id'=>$chequeId,
        'egresos_id'=>$egresoId,
        'movimientos_cuentas_id'=>$movimientoId,
        'saldo_anterior'=>number_format($saldoActual,2,'.',''),
        'saldo_nuevo'=>number_format($saldoNuevo,2,'.','')
    ]);

} catch (Throwable $e) {
    // Compensación porque las tablas actuales del sistema pueden ser MyISAM.
    if ($chequeId > 0) $db->query("DELETE FROM cheque WHERE cheque_id=".(int)$chequeId." LIMIT 1");
    if ($movimientoId > 0) $db->query("DELETE FROM movimientos_cuentas WHERE movimientos_cuentas_id=".(int)$movimientoId." LIMIT 1");
    if ($egresoId > 0) $db->query("DELETE FROM egresos WHERE egresos_id=".(int)$egresoId." LIMIT 1");
    chequesDesbloquear($db);

    error_log('CHEQUES guardar: '.$e->getMessage());
    chequesJson([
        'success'=>false,
        'title'=>'No se pudo emitir el cheque',
        'message'=>$e->getMessage()
    ],500);
}
