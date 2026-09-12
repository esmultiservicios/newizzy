<?php
require_once __DIR__ . '/_bootstrap.php';

chequesSoloPost();
$sesion = chequesSesion();
$db = chequesDb();
chequesValidarInstalacion($db);
chequesValidarTokenAdmin($_POST['token'] ?? '');

$cuentaId = isset($_POST['cuentas_id']) ? (int)$_POST['cuentas_id'] : 0;
if ($cuentaId <= 0) {
    chequesJson(['success'=>false,'title'=>'Cuenta requerida','message'=>'Seleccione una cuenta contable válida.']);
}

$stmt = $db->prepare("SELECT cuentas_id, codigo, nombre FROM cuentas WHERE cuentas_id=? AND estado=1 LIMIT 1");
$stmt->bind_param('i', $cuentaId);
$stmt->execute();
$cuenta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cuenta) {
    chequesJson(['success'=>false,'title'=>'Cuenta inválida','message'=>'La cuenta seleccionada no existe o está inactiva.']);
}

$stmt = $db->prepare("UPDATE tipo_pago SET cuentas_id=?, fecha_registro=NOW() WHERE tipo_pago_id=4 LIMIT 1");
if (!$stmt) {
    chequesJson(['success'=>false,'title'=>'Error','message'=>'No se pudo preparar la actualización.'],500);
}
$stmt->bind_param('i', $cuentaId);

if (!$stmt->execute() || $stmt->affected_rows < 0) {
    $error = $stmt->error;
    $stmt->close();
    chequesJson(['success'=>false,'title'=>'No se pudo guardar','message'=>$error ?: 'No se pudo actualizar la cuenta para cheques.'],500);
}
$stmt->close();

chequesJson([
    'success'=>true,
    'title'=>'Cuenta actualizada',
    'message'=>'Los nuevos cheques usarán la cuenta '.$cuenta['codigo'].' - '.$cuenta['nombre'].'.',
    'cuenta'=>$cuenta
]);
