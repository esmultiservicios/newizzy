<?php
// core/productos/getIsvConfig.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$out = [
  'success' => false,
  'status'  => false,
  'message' => 'Error desconocido',
  'isv1'    => ['valor'=>0.00,'activar'=>0],
  'isv2'    => ['valor'=>0.00,'activar'=>0],
];

try {
  // sesión (mismo patrón que tu ejemplo)
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

  $cn = (new mainModel())->connection();
  if (!$cn) throw new Exception('Sin conexión a BD');

  // Traer isv_id 1 y 2 (si existen)
  $sql = "SELECT isv_id, isv_tipo_id, valor, activar
            FROM isv
           WHERE isv_id IN (1,2)";
  $rs = $cn->query($sql);
  if (!$rs) throw new Exception('Error consultando ISV: '.$cn->error);

  $isv1 = ['valor'=>0.00,'activar'=>0];
  $isv2 = ['valor'=>0.00,'activar'=>0];

  while ($row = $rs->fetch_assoc()) {
    $info = [
      'valor'   => (float)$row['valor'],
      'activar' => (int)$row['activar'],
    ];
    if ((int)$row['isv_id'] === 1) { $isv1 = $info; }
    if ((int)$row['isv_id'] === 2) { $isv2 = $info; }
  }

  echo json_encode([
    'success' => true,
    'status'  => true,
    'message' => 'OK',
    'isv1'    => $isv1,
    'isv2'    => $isv2,
  ], JSON_UNESCAPED_UNICODE);
  exit;

} catch (Throwable $e) {
  $out['message'] = 'Error: '.$e->getMessage();
  $out['success'] = false;
  $out['status']  = false;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}
