<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json; charset=utf-8');

$ins = new mainModel();

// Validar sesión
$val = $ins->validarSesion();
if ($val['error']) {
  echo json_encode(["ok"=>false,"msg"=>"sesion"]); exit;
}

// Parámetro
$cotizacion_id = isset($_POST['cotizacion_id']) ? (int)$_POST['cotizacion_id'] : 0;
if ($cotizacion_id <= 0) {
  echo json_encode(["ok"=>false,"msg"=>"sin id"]); exit;
}

$conn = $ins->connection();

/* ===== Encabezado ===== */
$sqlH = "
  SELECT c.cotizacion_id, c.fecha, c.fecha_dolar, c.tipo_factura, c.importe,
         c.clientes_id, cl.nombre AS cliente_nombre,
         c.colaboradores_id, co.nombre AS colaborador_nombre,
         c.notas
  FROM cotizacion c
  INNER JOIN clientes cl       ON c.clientes_id = cl.clientes_id
  LEFT  JOIN colaboradores co  ON c.colaboradores_id = co.colaboradores_id
  WHERE c.cotizacion_id = {$cotizacion_id}
  LIMIT 1";
$rh = $conn->query($sqlH);
if (!$rh || $rh->num_rows === 0) {
  echo json_encode(["ok"=>false,"msg"=>"no header"]); exit;
}
$header = $rh->fetch_assoc();

/* ===== Detalle =====
   Solo columnas existentes + datos del producto (barCode, nombre)
*/
$sqlD = "
  SELECT cd.cotizacion_detalle_id,
         cd.productos_id,
         p.nombre AS producto,
         cd.cantidad,
         cd.precio,
         cd.descuento,
         cd.isv_valor,
         p.barCode
  FROM cotizacion_detalles cd
  INNER JOIN productos p ON cd.productos_id = p.productos_id
  WHERE cd.cotizacion_id = {$cotizacion_id}
  ORDER BY cd.cotizacion_detalle_id ASC";
$rd = $conn->query($sqlD);

$detalle = [];
while ($r = $rd->fetch_assoc()) {
  $detalle[] = [
    "cotizacion_detalle_id" => (int)$r["cotizacion_detalle_id"],
    "productos_id"          => (int)$r["productos_id"],
    "producto"              => $r["producto"],
    "cantidad"              => (float)$r["cantidad"],
    "precio"                => (float)$r["precio"],
    "descuento"             => (float)$r["descuento"],
    "isv_valor"             => (float)$r["isv_valor"],
    // Campos esperados por el front pero inexistentes en la tabla → defaults
    "isv_venta"             => 0,            // si luego lo necesitas, cálculalo
    "cantidad_mayoreo"      => 0,
    "precio_mayoreo"        => 0,
    "almacen_id"            => "",           // o un id por defecto si aplica
    "medida"                => "",           // ej. "unidad" si procede
    "barCode"               => $r["barCode"]
  ];
}

echo json_encode(["ok"=>true, "header"=>$header, "detalle"=>$detalle]);