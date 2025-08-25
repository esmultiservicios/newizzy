<?php
// getCotizacionParaFactura.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$ins = new mainModel();

try {
  // Sesión
  if (!isset($_SESSION['user_sd'])) { session_start(['name'=>'SD']); }
  $val = $ins->validarSesion();
  if ($val['error']) {
    echo json_encode(["ok"=>false,"msg"=>"Error de sesión: ".$val['mensaje']]); exit;
  }

  // Parámetro
  $cotizacion_id = isset($_POST['cotizacion_id']) ? (int)$_POST['cotizacion_id'] : 0;
  if ($cotizacion_id <= 0) {
    echo json_encode(["ok"=>false,"msg"=>"Falta el id de la cotización"]); exit;
  }

  $cn = $ins->connection();

  /* ===== Encabezado ===== */
  $sqlH = "
    SELECT 
      c.cotizacion_id,
      c.fecha,
      c.fecha_dolar,
      c.tipo_factura,          -- 1=Contado, 2=Crédito (ajusta si en tu BD es distinto)
      c.importe,
      c.notas,

      c.clientes_id,
      cl.nombre   AS cliente_nombre,
      cl.rtn      AS cliente_rtn,

      c.colaboradores_id,
      co.nombre   AS colaborador_nombre
    FROM cotizacion c
    INNER JOIN clientes      cl ON c.clientes_id      = cl.clientes_id
    LEFT  JOIN colaboradores co ON c.colaboradores_id = co.colaboradores_id
    WHERE c.cotizacion_id = {$cotizacion_id}
    LIMIT 1";
  $rh = $cn->query($sqlH);
  if (!$rh || $rh->num_rows === 0) {
    echo json_encode(["ok"=>false,"msg"=>"No se encontró el encabezado de la cotización"]); exit;
  }
  $header = $rh->fetch_assoc();

  /* ===== Detalle ===== */
  $sqlD = "
    SELECT 
      cd.cotizacion_detalle_id,
      cd.productos_id,
      p.nombre           AS producto,
      p.barCode          AS barCode,

      cd.cantidad,
      cd.precio,
      cd.descuento,
      cd.isv_valor,

      -- Campos del producto para que el front los tenga listos
      p.isv_venta,
      p.cantidad_mayoreo,
      p.precio_mayoreo,
      p.precio_venta,
      p.almacen_id,
      p.medida_id,
      m.nombre           AS medida
    FROM cotizacion_detalles cd
    INNER JOIN productos p ON cd.productos_id = p.productos_id
    LEFT  JOIN medida   m ON p.medida_id     = m.medida_id
    WHERE cd.cotizacion_id = {$cotizacion_id}
    ORDER BY cd.cotizacion_detalle_id ASC";
  $rd = $cn->query($sqlD);

  $detalle = [];
  $subtotal = 0.0;
  $descuento = 0.0;
  $isv = 0.0;

  while ($r = $rd->fetch_assoc()) {
    $cant = (float)$r['cantidad'];
    $prec = (float)$r['precio'];
    $desc = (float)$r['descuento'];
    $isvv = (float)$r['isv_valor'];

    $subtotal  += ($prec * $cant);
    $descuento += $desc;
    $isv       += $isvv;

    $detalle[] = [
      "cotizacion_detalle_id" => (int)$r["cotizacion_detalle_id"],
      "productos_id"          => (int)$r["productos_id"],
      "producto"              => $r["producto"],
      "barCode"               => $r["barCode"],

      "cantidad"              => $cant,
      "precio"                => $prec,
      "descuento"             => $desc,
      "isv_valor"             => $isvv,

      // extras de producto
      "isv_venta"             => (float)$r["isv_venta"],
      "cantidad_mayoreo"      => (float)$r["cantidad_mayoreo"],
      "precio_mayoreo"        => (float)$r["precio_mayoreo"],
      "precio_venta"          => (float)$r["precio_venta"],
      "almacen_id"            => $r["almacen_id"],
      "medida_id"             => $r["medida_id"],
      "medida"                => $r["medida"],
    ];
  }

  $totales = [
    "subtotal"  => round($subtotal, 2),
    "descuento" => round($descuento, 2),
    "isv"       => round($isv, 2),
    "total"     => round(($subtotal - $descuento) + $isv, 2)
  ];

  echo json_encode([
    "ok"      => true,
    "msg"     => "Cotización encontrada",
    "header"  => $header,
    "detalle" => $detalle,
    "totales" => $totales
  ]);
  exit;

} catch (Throwable $e) {
  echo json_encode(["ok"=>false,"msg"=>"Error: ".$e->getMessage()]);
  exit;
}