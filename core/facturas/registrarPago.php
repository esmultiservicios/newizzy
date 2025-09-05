<?php
// core/facturas/registrarPago.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$out = [
  'success'     => false,
  'estado'      => false,
  'message'     => 'Error desconocido',
  'pago_id'     => null,
  'factura_id'  => null,
  'imprimir'    => false,
  'js_print'    => null,
];

function getNextIdLocked(mysqli $db, string $table, string $pk){
  if (!$db->query("LOCK TABLES {$table} WRITE")) {
    throw new Exception("No se pudo bloquear la tabla {$table}");
  }
  $res = $db->query("SELECT COALESCE(MAX({$pk}),0)+1 AS next_id FROM {$table}");
  if (!$res) {
    $db->query("UNLOCK TABLES");
    throw new Exception("No se pudo obtener correlativo de {$table}: ".$db->error);
  }
  $row = $res->fetch_assoc();
  $next = (int)$row['next_id'];
  if ($next <= 0) $next = 1;
  return $next; // El que llama debe hacer UNLOCK TABLES cuando termine
}

try {
  if (session_status() !== PHP_SESSION_ACTIVE) {
    if (!isset($_SESSION['user_sd'])) {
      session_start(['name' => 'SD']);
    } else {
      session_start();
    }
  }

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    throw new Exception('Método no permitido');
  }

  $raw  = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Datos JSON inválidos');
  }

  foreach (['facturaId','efectivo','transferencia','tarjeta','cambio'] as $f) {
    if (!isset($data[$f])) throw new Exception("Campo requerido faltante: $f");
  }

  $facturaId     = (int)$data['facturaId'];
  $efectivo      = max(0, (float)$data['efectivo']);
  $transferencia = max(0, (float)$data['transferencia']);
  $tarjeta       = max(0, (float)$data['tarjeta']);

  if ($facturaId <= 0) throw new Exception('Factura inválida');

  $usuarioId = (int)($_SESSION['colaborador_id_sd'] ?? $_SESSION['colaborador_id'] ?? $_SESSION['usuarios_id'] ?? $_SESSION['user_id'] ?? 0);
  $empresaId = (int)($_SESSION['empresa_id_sd'] ?? $_SESSION['empresa_id'] ?? 1);

  $cn = (new mainModel())->connection();
  if (!$cn) throw new Exception('Sin conexión a BD');

  // Con MyISAM los BEGIN/COMMIT no aplican, pero quedan por si migras a InnoDB
  @$cn->begin_transaction();

  // ---------- Validar factura ----------
  $qFactura = "SELECT importe, tipo_factura, estado FROM facturas WHERE facturas_id = ? FOR UPDATE";
  $st = $cn->prepare($qFactura);
  if (!$st) throw new Exception('Error preparando consulta de factura: '.$cn->error);
  $st->bind_param('i', $facturaId);
  if (!$st->execute()) throw new Exception('Error ejecutando consulta de factura: '.$st->error);
  $res = $st->get_result();
  if ($res->num_rows === 0) throw new Exception('Factura no encontrada');
  $row = $res->fetch_assoc();
  $st->close();

  $totalFactura = (float)$row['importe'];
  $tipoFactura  = (int)$row['tipo_factura']; // 1 contado, 2 crédito
  $estadoActual = (int)$row['estado'];       // 1 borrador, 2 pagada, 3 crédito, 4 cancelada

  if ($estadoActual === 2) throw new Exception('La factura ya está pagada');
  if ($estadoActual === 4) throw new Exception('La factura está cancelada');

  $totalPago = $efectivo + $transferencia + $tarjeta;
  if ($totalPago + 0.0001 < $totalFactura) {
    throw new Exception('El pago no cubre el total de la factura');
  }

  // Recalcular cambio: exceso de efectivo tras tarjeta+transferencia
  $faltanteTrasNoEfectivo = max(0.0, $totalFactura - ($tarjeta + $transferencia));
  $cambio = max(0.0, $efectivo - $faltanteTrasNoEfectivo);

  // ---------- Insertar PAGO (ID manual con LOCK) ----------
  $pagoId = getNextIdLocked($cn, 'pagos', 'pagos_id');

  $qPago = "INSERT INTO pagos
    (pagos_id, facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta, usuario, estado, empresa_id, fecha_registro, contabilizado, referencia_ingreso_id)
    VALUES
    (?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, 1, ?, NOW(), 0, NULL)";
  $stp = $cn->prepare($qPago);
  if (!$stp) {
    $cn->query("UNLOCK TABLES");
    throw new Exception('Error preparando registro de pago: '.$cn->error);
  }
  // tipos: i i i d d d d i i  => 'iiiddddii'
  $stp->bind_param('iiiddddii', $pagoId, $facturaId, $tipoFactura, $totalFactura, $efectivo, $cambio, $tarjeta, $usuarioId, $empresaId);
  if (!$stp->execute()) {
    $cn->query("UNLOCK TABLES");
    throw new Exception('Error insertando pago: '.$stp->error);
  }
  $stp->close();

  // liberar lock de pagos
  $cn->query("UNLOCK TABLES");

  // ---------- Insertar DETALLES DE PAGO (IDs manuales con LOCK) ----------
  $nextDetPagoId = getNextIdLocked($cn, 'pagos_detalles', 'pagos_detalles_id');

  $qDet = "INSERT INTO pagos_detalles
    (pagos_detalles_id, pagos_id, tipo_pago_id, banco_id, efectivo, descripcion1, descripcion2, descripcion3)
    VALUES (?, ?, ?, 0, ?, ?, '', '')";
  $std = $cn->prepare($qDet);
  if (!$std) {
    $cn->query("UNLOCK TABLES");
    throw new Exception('Error preparando detalle de pago: '.$cn->error);
  }

  if ($efectivo > 0) {
    $tipoDet = 1; $desc = 'Pago en efectivo';
    // tipos: i i i d s => 'iiids'
    $std->bind_param('iiids', $nextDetPagoId, $pagoId, $tipoDet, $efectivo, $desc);
    if (!$std->execute()) { $cn->query("UNLOCK TABLES"); throw new Exception('Error detalle efectivo: '.$std->error); }
    $nextDetPagoId++;
  }
  if ($tarjeta > 0) {
    $tipoDet = 2; $desc = 'Pago con tarjeta';
    $std->bind_param('iiids', $nextDetPagoId, $pagoId, $tipoDet, $tarjeta, $desc);
    if (!$std->execute()) { $cn->query("UNLOCK TABLES"); throw new Exception('Error detalle tarjeta: '.$std->error); }
    $nextDetPagoId++;
  }
  if ($transferencia > 0) {
    $tipoDet = 3; $desc = 'Pago por transferencia';
    $std->bind_param('iiids', $nextDetPagoId, $pagoId, $tipoDet, $transferencia, $desc);
    if (!$std->execute()) { $cn->query("UNLOCK TABLES"); throw new Exception('Error detalle transferencia: '.$std->error); }
    $nextDetPagoId++;
  }
  $std->close();

  // liberar lock de pagos_detalles
  $cn->query("UNLOCK TABLES");

  // ---------- Marcar factura pagada ----------
  $qUpd = "UPDATE facturas SET estado = 2 WHERE facturas_id = ?";
  $stu = $cn->prepare($qUpd);
  if (!$stu) throw new Exception('Error preparando actualización de factura: '.$cn->error);
  $stu->bind_param('i', $facturaId);
  if (!$stu->execute()) throw new Exception('Error actualizando factura: '.$stu->error);
  $stu->close();

  // ---------- Actualizar COBRAR_CLIENTES (saldo 0, estado 2) ----------
  $qCxcUpd = "UPDATE cobrar_clientes
                SET estado = 2, saldo = 0
              WHERE facturas_id = ? AND empresa_id = ?";
  $sc = $cn->prepare($qCxcUpd);
  if (!$sc) throw new Exception('Error preparando actualización de cobrar_clientes: '.$cn->error);
  $sc->bind_param('ii', $facturaId, $empresaId);
  if (!$sc->execute()) throw new Exception('Error actualizando cobrar_clientes: '.$sc->error);
  $af = $cn->affected_rows;
  $sc->close();

  // Si por alguna razón no existía el registro, lo creamos saldado
  if ($af === 0) {
    // Necesitamos el total de la factura para saldo 0, ya lo tenemos en $totalFactura
    // correlativo manual con LOCK
    $cxcId = getNextIdLocked($cn, 'cobrar_clientes', 'cobrar_clientes_id');
    $qCxcIns = "INSERT INTO cobrar_clientes
      (cobrar_clientes_id, clientes_id, facturas_id, fecha, saldo, estado, tipo_factura, usuario, empresa_id, fecha_registro)
      SELECT ?, f.clientes_id, f.facturas_id, CURDATE(), 0, 2, f.tipo_factura, ?, ?, NOW()
        FROM facturas f
       WHERE f.facturas_id = ?
       LIMIT 1";
    $sic = $cn->prepare($qCxcIns);
    if (!$sic) { $cn->query("UNLOCK TABLES"); throw new Exception('Error preparando inserción de CxC: '.$cn->error); }
    $sic->bind_param('iiii', $cxcId, $usuarioId, $empresaId, $facturaId);
    if (!$sic->execute()) { $cn->query("UNLOCK TABLES"); throw new Exception('Error insertando CxC saldado: '.$sic->error); }
    $sic->close();
    $cn->query("UNLOCK TABLES");
  }

  @$cn->commit();

  // si es contado (1) => indicar impresión
  $imprimir = ($tipoFactura === 1);

  echo json_encode([
    'success'     => true,
    'estado'      => true,
    'message'     => 'Pago registrado correctamente',
    'pago_id'     => $pagoId,
    'factura_id'  => $facturaId,
    'imprimir'    => $imprimir,
    'js_print'    => $imprimir ? ('printBill(' . $facturaId . ');') : null
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  // soltar locks si quedaron activos
  if (isset($cn) && $cn instanceof mysqli) {
    @$cn->query("UNLOCK TABLES");
    @ $cn->rollback();
  }
  $out['message']    = 'Error: '.$e->getMessage();
  $out['success']    = false;
  $out['estado']     = false;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}
