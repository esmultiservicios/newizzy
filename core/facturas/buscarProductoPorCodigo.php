<?php
// core/facturas/buscarProductoPorCodigo.php
header('Content-Type: application/json; charset=utf-8');
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();
$db = $mainModel->connection();

$codigo = isset($_POST['codigo']) ? trim($_POST['codigo']) : '';
if (!$db || $codigo === '') {
  echo json_encode(['success'=>false,'message'=>'Parámetros inválidos']); exit;
}

$sql = "
SELECT
  p.productos_id,
  p.nombre,
  p.precio_venta,
  p.isv_venta,
  p.isv1,
  p.isv2,
  (SELECT valor FROM isv WHERE isv_id = 1 LIMIT 1) AS isv1_valor,
  (SELECT valor FROM isv WHERE isv_id = 2 LIMIT 1) AS isv2_valor
FROM productos p
WHERE p.estado = 1 AND p.barCode = ?
LIMIT 1
";
$st = $db->prepare($sql);
$st->bind_param('s', $codigo);
$st->execute();
$rs = $st->get_result();

if (!$rs || $rs->num_rows === 0) {
  echo json_encode(['success'=>false,'message'=>'Producto no encontrado']); exit;
}

$r = $rs->fetch_assoc();

$aplica = ((int)$r['isv_venta'] === 1);
$isvId = 0; $rate = 0.0; $label = '';

if ($aplica) {
  if ((int)$r['isv2'] === 1) {          // prioridad isv2
    $porc  = (float)($r['isv2_valor'] ?? 0);
    $isvId = 2; $rate = $porc / 100.0;
    $label = 'ISV ' . number_format($porc, 2, '.', '') . '%';
  } elseif ((int)$r['isv1'] === 1) {    // luego isv1
    $porc  = (float)($r['isv1_valor'] ?? 0);
    $isvId = 1; $rate = $porc / 100.0;
    $label = 'ISV ' . number_format($porc, 2, '.', '') . '%';
  }
}

echo json_encode([
  'success'  => true,
  'producto' => [
    'productos_id'      => (int)$r['productos_id'],
    'nombre'            => (string)$r['nombre'],
    'precio_venta'      => (float)$r['precio_venta'],
    'isv_venta'         => (int)$r['isv_venta'],
    'isv_id_aplicado'   => $isvId,
    'isv_rate_decimal'  => $rate,
    'isv_label'         => $label
  ]
], JSON_UNESCAPED_UNICODE);