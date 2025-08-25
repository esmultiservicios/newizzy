<?php
// core/getDraftBills.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/configGenerales.php';
require_once __DIR__ . '/mainModel.php';

$Response = [
  'ok' => false,
  'type' => 'error',
  'title' => 'Error',
  'message' => 'Error desconocido',
  'header' => null,
  'detalle' => [],
  'totales' => ['subtotal' => 0.0, 'descuento' => 0.0, 'isv' => 0.0, 'total' => 0.0],
];

try {
  // Sesión (mantén tu nombre si usas uno específico)
  if (session_status() !== PHP_SESSION_ACTIVE) {
    if (!isset($_SESSION['user_sd'])) {
      session_start(['name' => 'SD']);
    } else {
      session_start();
    }
  }

  // Validar sesión si tu mainModel lo soporta
  if (method_exists('mainModel', 'validarSesion')) {
    $val = mainModel::validarSesion();
    if (!empty($val['error'])) {
      throw new Exception($val['mensaje'] ?? 'Sesión inválida');
    }
  }

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    throw new Exception('Método no permitido');
  }

  // Soportar JSON y form-data
  $raw = file_get_contents('php://input');
  $data = null;
  if ($raw) {
    $tmp = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      $data = $tmp;
    }
  }
  if (!$data) {
    $data = $_POST;
  }

  $facturas_id = isset($data['facturas_id']) ? (int)$data['facturas_id'] : 0;
  if ($facturas_id <= 0) {
    throw new Exception('Parámetro facturas_id inválido');
  }

  $db = (new mainModel())->connection();

  // ===== HEADER =====
  $sqlHeader = "
    SELECT
      f.facturas_id,
      f.clientes_id,
      f.colaboradores_id,
      f.secuencia_facturacion_id,
      f.apertura_id,
      f.number,
      f.tipo_factura,               -- 1=Contado, 2=Crédito
      f.importe,
      f.notas,
      f.fecha,
      f.estado,                     -- 1=Borrador 2=Pagada 3=Crédito 4=Cancelada
      f.usuario,
      f.empresa_id,
      f.fecha_registro,
      f.fecha_dolar,
      f.no_orden,
      f.constancia,
      f.identificativo_sag,
      f.numero_interno,
      c.cliente      AS cliente_nombre,
      c.rtn          AS cliente_rtn,
      co.colaborador AS colaborador_nombre
    FROM facturas f
    LEFT JOIN clientes c       ON c.clientes_id = f.clientes_id
    LEFT JOIN colaboradores co ON co.colaboradores_id = f.colaboradores_id
    WHERE f.facturas_id = ?
    LIMIT 1;
  ";
  $stmtH = $db->prepare($sqlHeader);
  if (!$stmtH) throw new Exception('Error preparando header: ' . $db->error);
  $stmtH->bind_param('i', $facturas_id);
  $stmtH->execute();
  $resH = $stmtH->get_result();
  if ($resH->num_rows === 0) throw new Exception('Factura no encontrada');
  $h = $resH->fetch_assoc();
  $stmtH->close();

  // Mapeos útiles
  $tipo_txt = ((int)$h['tipo_factura'] === 1) ? 'contado' : 'credito';
  $estado_map = [1 => 'borrador', 2 => 'pagada', 3 => 'credito', 4 => 'cancelada'];
  $estado_txt = $estado_map[(int)$h['estado']] ?? 'desconocido';

  $header = [
    'facturas_id' => (int)$h['facturas_id'],
    'clientes_id' => (int)$h['clientes_id'],
    'colaboradores_id' => (int)$h['colaboradores_id'],
    'secuencia_facturacion_id' => (int)$h['secuencia_facturacion_id'],
    'apertura_id' => (int)$h['apertura_id'],
    'number' => (int)$h['number'],
    'tipo_factura' => (int)$h['tipo_factura'],     // 1=Contado, 2=Crédito
    'tipo_factura_txt' => $tipo_txt,               // "contado" | "credito"
    'importe' => (float)$h['importe'],
    'notas' => (string)$h['notas'],
    'fecha' => (string)$h['fecha'],
    'estado' => (int)$h['estado'],
    'estado_txt' => $estado_txt,
    'usuario' => (int)$h['usuario'],
    'empresa_id' => (int)$h['empresa_id'],
    'fecha_registro' => (string)$h['fecha_registro'],
    'fecha_dolar' => (string)$h['fecha_dolar'],
    // Exoneración
    'no_orden' => $h['no_orden'],
    'constancia' => $h['constancia'],
    'identificativo_sag' => $h['identificativo_sag'],
    'numero_interno' => $h['numero_interno'],
    // Presentación
    'cliente_nombre' => $h['cliente_nombre'],
    'cliente_rtn' => $h['cliente_rtn'],
    'colaborador_nombre' => $h['colaborador_nombre'],
  ];

  // ===== DETALLE (SIN AGRUPAR: respeta líneas tal cual) =====
  $sqlDet = "
    SELECT
      fd.facturas_detalle_id,
      fd.facturas_id,
      fd.productos_id,
      fd.cantidad,
      fd.precio,
      fd.isv_valor,
      fd.descuento,
      COALESCE(med.nombre, fd.medida) AS medida,
      p.barCode       AS barCode,
      p.nombre        AS producto,
      p.isv_venta     AS isv_venta,
      p.almacen_id    AS almacen_id,
      p.precio_venta  AS precio_venta,
      p.cantidad_mayoreo,
      p.precio_mayoreo
    FROM facturas_detalles fd
    INNER JOIN productos p ON p.productos_id = fd.productos_id
    LEFT  JOIN medida   med ON med.medida_id = p.medida_id
    WHERE fd.facturas_id = ?
    ORDER BY fd.facturas_detalle_id ASC;
  ";
  $stmtD = $db->prepare($sqlDet);
  if (!$stmtD) throw new Exception('Error preparando detalle: ' . $db->error);
  $stmtD->bind_param('i', $facturas_id);
  $stmtD->execute();
  $resD = $stmtD->get_result();

  $detalle = [];
  $subtotal = 0.0;
  $descuento = 0.0;
  $isv = 0.0;

  while ($r = $resD->fetch_assoc()) {
    $linea = [
      'facturas_detalle_id' => (int)$r['facturas_detalle_id'],
      'productos_id' => (int)$r['productos_id'],
      'cantidad' => (float)$r['cantidad'],
      'precio' => (float)$r['precio'],
      'isv_valor' => (float)$r['isv_valor'],
      'descuento' => (float)$r['descuento'],
      'medida' => $r['medida'],
      // datos de producto
      'barCode' => $r['barCode'],
      'producto' => $r['producto'],
      'isv_venta' => (float)$r['isv_venta'],
      'almacen_id' => (int)$r['almacen_id'],
      'precio_venta' => (float)$r['precio_venta'],
      'cantidad_mayoreo' => (float)$r['cantidad_mayoreo'],
      'precio_mayoreo' => (float)$r['precio_mayoreo'],
    ];
    $detalle[] = $linea;

    // totales
    $subtotal += ((float)$r['precio']) * ((float)$r['cantidad']);
    $descuento += (float)$r['descuento'];
    $isv += (float)$r['isv_valor'];
  }
  $stmtD->close();

  $total = ($subtotal - $descuento) + $isv;

  $Response['ok'] = true;
  $Response['type'] = 'success';
  $Response['title'] = 'Éxito';
  $Response['message'] = 'Borrador cargado';
  $Response['header'] = $header;
  $Response['detalle'] = $detalle;
  $Response['totales'] = [
    'subtotal' => round($subtotal, 2),
    'descuento' => round($descuento, 2),
    'isv' => round($isv, 2),
    'total' => round($total, 2),
  ];

  echo json_encode($Response, JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  $Response['message'] = $e->getMessage();
  echo json_encode($Response, JSON_UNESCAPED_UNICODE);
  exit;
}