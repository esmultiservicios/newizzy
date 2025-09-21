<?php
// core/facturas/getProductos.php
header('Content-Type: application/json; charset=utf-8');
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();
$db = $mainModel->connection();
if (!$db) { echo json_encode([]); exit; }

/*
  Regla exacta:
  - productos.isv_venta = 1  -> el producto calcula ISV (en factura)
  - productos.isv2 = 1       -> usar isv_id = 2 de tabla isv (ej. 18%)
  - else si productos.isv1=1 -> usar isv_id = 1 de tabla isv (ej. 15%)
  - Nunca ambos 1 al mismo tiempo (según tu lógica).
*/

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
WHERE p.estado = 1
ORDER BY p.nombre ASC
";

$res = $db->query($sql);
$out = [];

if ($res) {
  while ($r = $res->fetch_assoc()) {
    $precio  = (float)$r['precio_venta'];
    // OJO: en tu tabla isv_venta => 1: Sí, 2: No
    $aplica  = ((int)$r['isv_venta'] === 1);

    $isvId = 0; $rate = 0.0; $label = '';

    if ($aplica) {
      if ((int)$r['isv2'] === 1) {                     // prioridad isv2
        $porc  = (float)($r['isv2_valor'] ?? 0);
        $isvId = 2; $rate = $porc / 100.0;
        $label = 'ISV ' . number_format($porc, 2, '.', '') . '%';
      } elseif ((int)$r['isv1'] === 1) {               // luego isv1
        $porc  = (float)($r['isv1_valor'] ?? 0);
        $isvId = 1; $rate = $porc / 100.0;
        $label = 'ISV ' . number_format($porc, 2, '.', '') . '%';
      }
    }

    $out[] = [
      'productos_id'      => (int)$r['productos_id'],
      'nombre'            => (string)$r['nombre'],
      'precio_venta'      => $precio,
      'isv_venta'         => (int)$r['isv_venta'], // 1/2
      'isv_id_aplicado'   => $isvId,               // 0/1/2
      'isv_rate_decimal'  => $rate,                // 0 / 0.15 / 0.18
      'isv_label'         => $label                // "" o "ISV 15.00%" / "ISV 18.00%"
    ];
  }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);