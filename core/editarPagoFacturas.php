<?php
// editarPagoFacturas.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido');
}

$facturas_id = isset($_POST['facturas_id']) ? (int)$_POST['facturas_id'] : 0;
if ($facturas_id <= 0) {
  exit(json_encode(['error' => true, 'message' => 'Factura inválida']));
}

/*
 * Traemos:
 * - cliente, clientes_id, fecha_factura, tipo (crédito/contado)
 * - importe ya guardado en facturas (redondeado a 2)
 * - saldo de cobrar_clientes (redondeado a 2)
 *
 * ¡Importante!: usamos ROUND en SQL para evitar 1098.9976 etc.
 */
$query = "
SELECT
  f.facturas_id                     AS facturas_id,
  DATE_FORMAT(f.fecha, '%d/%m/%Y')  AS fecha,
  c.clientes_id                     AS clientes_id,
  c.nombre                          AS cliente,
  f.fecha                           AS fecha_factura,
  f.tipo_factura                    AS credito,
  ROUND(f.importe, 2)               AS importe,
  ROUND(cc.saldo, 2)                AS saldo
FROM facturas f
INNER JOIN clientes c        ON f.clientes_id = c.clientes_id
INNER JOIN cobrar_clientes cc ON cc.facturas_id = f.facturas_id
WHERE f.facturas_id = ?
LIMIT 1";

$cn = $insMainModel->connection();
$st = $cn->prepare($query);
$st->bind_param('i', $facturas_id);
$st->execute();
$rs = $st->get_result();

$cliente = "";
$clientes_id = "";
$fecha_factura = "";
$importe = 0.00;
$saldo = 0.00;
$estado = 0;

if ($row = $rs->fetch_assoc()) {
  $cliente       = $row['cliente'];
  $clientes_id   = (int)$row['clientes_id'];
  $fecha_factura = $row['fecha_factura'];
  $estado        = (int)$row['credito'];                  // 1 contado, 2 crédito
  $importe       = round((float)$row['importe'] + 1e-9, 2);
  $saldo         = round((float)$row['saldo']   + 1e-9, 2);
}
$st->close();

/*
 * Si por alguna razón no hubiera fila en cobrar_clientes (caso raro),
 * usa el importe como saldo inicial.
 */
if ($saldo <= 0 && $importe > 0) {
  $saldo = $importe;
}

$datos = [
  0 => $cliente,
  1 => $clientes_id,
  2 => $fecha_factura,
  3 => $importe,       // ← mostrará exactamente lo guardado en facturas
  4 => $facturas_id,
  5 => $estado,
  6 => $saldo          // ← saldo redondeado igual que en BD
];

echo json_encode($datos, JSON_UNESCAPED_UNICODE);