<?php
// core/facturas/procesarFactura.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$out = [
  'estado'  => false,
  'success' => false,
  'message' => 'Error desconocido',
];

try {
  $mainModel = new mainModel();

  // (Opcional) si tu mainModel lo soporta
  if (method_exists($mainModel, 'validarSesion')) {
    $val = $mainModel->validarSesion();
    if (!empty($val['error']) && $val['error']) {
      echo json_encode(['estado'=>false,'success'=>false,'message'=>$val['mensaje']]); exit;
    }
  }

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    throw new Exception('Método no permitido');
  }

  // Body: JSON o form
  $raw = file_get_contents('php://input');
  $data = null;
  if ($raw) {
    $tmp = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) $data = $tmp;
  }
  if (!$data) $data = $_POST;

  // Campos del front
  $clienteId   = isset($data['clienteId']) ? (int)$data['clienteId'] : 0;
  $vendedorId  = isset($data['vendedorId']) ? (int)$data['vendedorId'] : 0;
  $tipoFactura = isset($data['tipoFactura']) ? (int)$data['tipoFactura'] : 0; // 1 contado, 2 crédito
  $aperturaId  = isset($data['aperturaId']) ? (int)$data['aperturaId'] : 0;   // puede venir del front
  $notas       = isset($data['notas']) ? trim((string)$data['notas']) : '';
  $productos   = isset($data['productos']) && is_array($data['productos']) ? $data['productos'] : [];
  $secIdClient = isset($data['secuencia_facturacion_id']) ? (int)$data['secuencia_facturacion_id'] : 0;

  if ($clienteId <= 0) throw new Exception('Cliente inválido');
  // si no enviaron vendedor, usar colaborador logueado
  if ($vendedorId === 0) {
    $vendedorId = (int)($_SESSION['colaborador_id_sd']
                  ?? $_SESSION['colaborador_id']
                  ?? 0);
  }
  if (!in_array($tipoFactura, [1,2], true)) throw new Exception('Tipo de factura inválido');
  if (empty($productos)) throw new Exception('Debe agregar al menos un producto');

  // Contexto
  $usuarioId = (int)($_SESSION['colaborador_id_sd'] ?? $_SESSION['colaborador_id'] ?? $_SESSION['usuarios_id'] ?? $_SESSION['user_id'] ?? 0);
  $empresaId = (int)($_SESSION['empresa_id_sd'] ?? $_SESSION['empresa_id'] ?? 1);

  $db = $mainModel->connection();
  if (!$db) throw new Exception('Sin conexión a BD');

  // ==================================================
  // 0) Obtener los % ISV activos por isv_id (1 y 2)
  //     - Siempre leemos tabla isv porque pueden cambiar
  // ==================================================
  $isv1Rate = 0.00; // decimal (0.15)
  $isv2Rate = 0.00; // decimal (0.18)

  $qISV = "SELECT isv_id, valor FROM isv WHERE activar = 1 AND isv_id IN (1,2)";
  $rISV = $db->query($qISV);
  if (!$rISV) throw new Exception('No se pudieron leer valores ISV: '.$db->error);
  while ($row = $rISV->fetch_assoc()) {
    $id = (int)$row['isv_id'];
    $val = ((float)$row['valor']) / 100.0;
    if ($id === 1) $isv1Rate = $val;
    if ($id === 2) $isv2Rate = $val;
  }
  $rISV->free();

  // ==================================================
  // 0.1) Resolver apertura_id (OBLIGATORIO)
  //      - Si no viene, buscamos apertura de HOY, del
  //        usuario logueado (cajero) y empresa, estado=1
  // ==================================================
  if ($aperturaId <= 0) {
    $sqlA = "SELECT apertura_id
             FROM apertura
             WHERE colaboradores_id = ?
               AND fecha = CURDATE()
               AND estado = 1
               AND empresa_id = ?
             LIMIT 1";
    $stA = $db->prepare($sqlA);
    if (!$stA) throw new Exception('Error preparando consulta de apertura: '.$db->error);
    $stA->bind_param('ii', $usuarioId, $empresaId);
    $stA->execute();
    $rsA = $stA->get_result();
    if ($rsA && $rsA->num_rows > 0) {
      $aperturaId = (int)$rsA->fetch_assoc()['apertura_id'];
    }
    $stA->close();
  }

  if ($aperturaId <= 0) {
    throw new Exception('Caja no aperturada para este usuario hoy. Debe aperturar caja antes de facturar.');
  }

  // ================================================
  // 1) Resolver SECUENCIA + RECUPERAR NÚMERO FALLIDO
  // ================================================
  $secuenciaId   = 0;
  $numeroFactura = 0;
  $documentoId   = 1; // por defecto (Factura electrónica)

  if ($secIdClient > 0) {
    $sqlS = "SELECT secuencia_facturacion_id, empresa_id, activo, siguiente, incremento, rango_inicial, rango_final, fecha_limite, documento_id
             FROM secuencia_facturacion
             WHERE secuencia_facturacion_id = ?
             LIMIT 1";
    $st = $db->prepare($sqlS);
    if (!$st) throw new Exception('Error preparando consulta de secuencia: '.$db->error);
    $st->bind_param('i', $secIdClient);
    $st->execute();
    $rs = $st->get_result();
    if ($rs->num_rows === 0) throw new Exception('Secuencia no encontrada');
    $sec = $rs->fetch_assoc();
    $st->close();

    if ((int)$sec['empresa_id'] !== $empresaId) throw new Exception('Secuencia no pertenece a la empresa');
    if ((int)$sec['activo'] !== 1) throw new Exception('La secuencia no está activa');
    if (strtotime($sec['fecha_limite']) < strtotime(date('Y-m-d'))) throw new Exception('Secuencia vencida');

    $secuenciaId = (int)$sec['secuencia_facturacion_id'];
    $documentoId = (int)$sec['documento_id'];

    $sig  = (int)$sec['siguiente'];
    $rini = (int)$sec['rango_inicial'];
    $rfin = (int)$sec['rango_final'];
    $inc  = (int)$sec['incremento'];
    if ($sig < $rini || $sig > $rfin) throw new Exception('Secuencia fuera de rango');

  } else {
    $sqlS = "SELECT secuencia_facturacion_id, empresa_id, activo, siguiente, incremento, rango_inicial, rango_final, fecha_limite, documento_id
             FROM secuencia_facturacion
             WHERE empresa_id = ? AND activo = 1 AND fecha_limite >= CURDATE()
             ORDER BY fecha_activacion ASC, fecha_limite ASC
             LIMIT 1";
    $st = $db->prepare($sqlS);
    if (!$st) throw new Exception('Error preparando consulta de secuencia: '.$db->error);
    $st->bind_param('i', $empresaId);
    $st->execute();
    $rs = $st->get_result();
    if ($rs->num_rows === 0) throw new Exception('No hay secuencia de facturación activa');
    $sec = $rs->fetch_assoc();
    $st->close();

    $secuenciaId = (int)$sec['secuencia_facturacion_id'];
    $documentoId = (int)$sec['documento_id'];

    $sig  = (int)$sec['siguiente'];
    $rini = (int)$sec['rango_inicial'];
    $rfin = (int)$sec['rango_final'];
    $inc  = (int)$sec['incremento'];
    if ($sig < $rini || $sig > $rfin) throw new Exception('Secuencia fuera de rango');
  }

  // --- Buscar número fallido primero (tabla InnoDB) ---
  @$db->begin_transaction();

  $numeroFallido = null;
  $qFall = "SELECT numero FROM secuencia_factura_fallida
            WHERE empresa_id = ? AND documento_id = ?
            ORDER BY numero ASC
            LIMIT 1 FOR UPDATE";
  if ($stmF = $db->prepare($qFall)) {
    $stmF->bind_param('ii', $empresaId, $documentoId);
    $stmF->execute();
    $resF = $stmF->get_result();
    if ($resF && $resF->num_rows > 0) {
      $numeroFallido = (int)$resF->fetch_assoc()['numero'];
    }
    $stmF->close();
  }

  if ($numeroFallido !== null) {
    $numeroFactura = $numeroFallido;

    if ($delF = $db->prepare("DELETE FROM secuencia_factura_fallida WHERE empresa_id = ? AND documento_id = ? AND numero = ?")) {
      $delF->bind_param('iii', $empresaId, $documentoId, $numeroFallido);
      $delF->execute();
      $delF->close();
    }
    @$db->commit();
  } else {
    $numeroFactura = $sig;
    $nuevoSig = $sig + ($inc > 0 ? $inc : 1);

    $sqlUpdSeq = "UPDATE secuencia_facturacion SET siguiente = ? WHERE secuencia_facturacion_id = ?";
    $up = $db->prepare($sqlUpdSeq);
    if ($up) {
      $up->bind_param('ii', $nuevoSig, $secuenciaId);
      $up->execute();
      $up->close();
    }
    @$db->commit();
  }

  // =================================
  // 2) Correlativo para facturas_id
  // =================================
  $res = $db->query("SELECT COALESCE(MAX(facturas_id),0)+1 AS next_id FROM facturas");
  if (!$res) throw new Exception("No se pudo calcular correlativo de facturas: ".$db->error);
  $row = $res->fetch_assoc();
  $facturaId = (int)$row['next_id'];
  if ($facturaId <= 0) $facturaId = 1;

  // ===========================
  // 3) Insertar ENCABEZADO
  // ===========================
  $estadoInicial = ($tipoFactura === 1) ? 1 : 3; // 1=Borrador, 3=Crédito

  $sqlInsH = "INSERT INTO facturas
    (facturas_id, clientes_id, secuencia_facturacion_id, apertura_id, number, tipo_factura, colaboradores_id,
     importe, notas, fecha, estado, usuario, empresa_id, fecha_registro, fecha_dolar,
     no_orden, constancia, identificativo_sag, numero_interno)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, 0.00, ?, CURDATE(), ?, ?, ?, NOW(), CURDATE(), NULL, NULL, NULL, NULL)";
  $sth = $db->prepare($sqlInsH);
  if (!$sth) throw new Exception('Error preparando encabezado: '.$db->error);
  $sth->bind_param(
    'iiiiiiisiii',
    $facturaId, $clienteId, $secuenciaId, $aperturaId, $numeroFactura, $tipoFactura, $vendedorId,
    $notas, $estadoInicial, $usuarioId, $empresaId
  );
  if (!$sth->execute()) throw new Exception('Error insertando factura: '.$sth->error);
  $sth->close();

  // ===========================
  // 4) Insertar DETALLES
  // ===========================
  $resd = $db->query("SELECT COALESCE(MAX(facturas_detalle_id),0)+1 AS next_id FROM facturas_detalles");
  if (!$resd) throw new Exception("No se pudo calcular correlativo de detalles: ".$db->error);
  $rd = $resd->fetch_assoc();
  $nextDetId = (int)$rd['next_id'];
  if ($nextDetId <= 0) $nextDetId = 1;

  // Traemos banderas isv1/isv2 e isv_venta por producto
  $sqlProd = "SELECT p.productos_id, p.isv_venta, p.isv1, p.isv2, COALESCE(m.nombre,'UND') AS medida
              FROM productos p
              LEFT JOIN medida m ON m.medida_id = p.medida_id
              WHERE p.productos_id = ?
              LIMIT 1";
  $stp = $db->prepare($sqlProd);
  if (!$stp) throw new Exception('Error preparando consulta producto: '.$db->error);

  // Ajustado: ahora insertamos también isv_valor1
  $sqlDetIns = "INSERT INTO facturas_detalles
    (facturas_detalle_id, facturas_id, productos_id, cantidad, precio, isv_valor, isv_valor1, descuento, medida)
    VALUES (?,?,?,?,?,?,?,?,?)";
  $std = $db->prepare($sqlDetIns);
  if (!$std) throw new Exception('Error preparando insert de detalle: '.$db->error);

  foreach ($productos as $item) {
    $prodId   = (int)($item['productoId'] ?? $item['productos_id'] ?? 0);
    $cantidad = (int)($item['cantidad'] ?? 0);
    $precio   = (float)($item['precio'] ?? 0);
    $desc     = (float)($item['descuento'] ?? 0);

    if ($prodId <= 0 || $cantidad <= 0 || $precio < 0) {
      throw new Exception('Producto inválido en el detalle');
    }

    $stp->bind_param('i', $prodId);
    $stp->execute();
    $rp = $stp->get_result();
    if (!$rp || $rp->num_rows === 0) {
      throw new Exception('Producto no encontrado (ID '.$prodId.')');
    }
    $prow = $rp->fetch_assoc();

    // isv_venta: 1 = Sí, 2 = No
    $isvVenta = ((int)$prow['isv_venta'] === 1);
    $flagIsv1 = (int)$prow['isv1']; // 0 o 1
    $flagIsv2 = (int)$prow['isv2']; // 0 o 1
    $medida   = (string)$prow['medida'];

    $precioUnit = round($precio, 4);

    // Calculamos por UNIDAD y luego por LÍNEA
    $isv15Unit = 0.0;
    $isv18Unit = 0.0;

    if ($isvVenta) {
      if ($flagIsv1 === 1) {               // usa isv_id = 1
        $isv15Unit = round($precioUnit * $isv1Rate, 4);
        $isv18Unit = 0.0;
      } elseif ($flagIsv2 === 1) {         // usa isv_id = 2
        $isv18Unit = round($precioUnit * $isv2Rate, 4);
        $isv15Unit = 0.0;
      } else {
        // ni isv1 ni isv2 activos => no grava
        $isv15Unit = 0.0;
        $isv18Unit = 0.0;
      }
    }

    $isv15Linea = $isv15Unit * $cantidad;
    $isv18Linea = $isv18Unit * $cantidad;

    // bind: iiiidddds (i,i,i,i, d, d, d, d, s)
    $std->bind_param(
      'iiiidddds',
      $nextDetId, $facturaId, $prodId, $cantidad,
      $precioUnit, $isv15Linea, $isv18Linea, $desc, $medida
    );
    if (!$std->execute()) throw new Exception('Error insertando detalle: '.$std->error);
    $nextDetId++;
  }
  $stp->close();
  $std->close();

  // ===========================================================
  // 5) Recalcular TOTAL desde facturas_detalles (consistente)
  //     Incluye ambos ISV: isv_valor (15) + isv_valor1 (18)
  // ===========================================================
  $qTotal = "SELECT ROUND(SUM((cantidad * precio) - descuento + isv_valor + isv_valor1), 2) AS total
             FROM facturas_detalles
             WHERE facturas_id = ?";
  $stt = $db->prepare($qTotal);
  if (!$stt) throw new Exception('Error preparando cálculo de total: '.$db->error);
  $stt->bind_param('i', $facturaId);
  $stt->execute();
  $rt = $stt->get_result();
  $rowT = $rt->fetch_assoc();
  $stt->close();

  $total = (float)($rowT['total'] ?? 0.00);
  $total = round($total + 1e-9, 2);

  // 5b) Actualizar TOTAL en facturas
  $sqlUpd = "UPDATE facturas SET importe = ? WHERE facturas_id = ?";
  $stu = $db->prepare($sqlUpd);
  if (!$stu) throw new Exception('Error preparando actualización de factura: '.$db->error);
  $stu->bind_param('di', $total, $facturaId);
  if (!$stu->execute()) throw new Exception('Error actualizando factura: '.$stu->error);
  $stu->close();

  // ===========================
  // 6) Registrar en COBRAR_CLIENTES (siempre)
  // ===========================
  if (!$db->query("LOCK TABLES cobrar_clientes WRITE")) {
    throw new Exception('No se pudo bloquear la tabla cobrar_clientes');
  }
  $resCc = $db->query("SELECT COALESCE(MAX(cobrar_clientes_id),0)+1 AS next_id FROM cobrar_clientes");
  if (!$resCc) {
    $db->query("UNLOCK TABLES");
    throw new Exception("No se pudo calcular correlativo de cobrar_clientes: ".$db->error);
  }
  $rowCc = $resCc->fetch_assoc();
  $cxcId = (int)$rowCc['next_id'];
  if ($cxcId <= 0) $cxcId = 1;

  $sqlCxc = "INSERT INTO cobrar_clientes
    (cobrar_clientes_id, clientes_id, facturas_id, fecha, saldo, estado, tipo_factura, usuario, empresa_id, fecha_registro)
    VALUES (?, ?, ?, CURDATE(), ?, 1, ?, ?, ?, NOW())";
  $stc = $db->prepare($sqlCxc);
  if (!$stc) {
    $db->query("UNLOCK TABLES");
    throw new Exception('Error preparando registro en cobrar_clientes: '.$db->error);
  }
  $stc->bind_param('iiidiii', $cxcId, $clienteId, $facturaId, $total, $tipoFactura, $usuarioId, $empresaId);
  if (!$stc->execute()) {
    $db->query("UNLOCK TABLES");
    throw new Exception('Error insertando en cobrar_clientes: '.$stc->error);
  }
  $stc->close();
  $db->query("UNLOCK TABLES");

  echo json_encode([
    'estado'     => true,
    'success'    => true,
    'message'    => 'Factura registrada correctamente',
    'factura_id' => $facturaId,
    'total'      => number_format($total, 2, '.', '')
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  $out['message'] = $e->getMessage();
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}