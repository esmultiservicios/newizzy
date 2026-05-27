<?php
// core/productos/getSaldoProductoMovimiento.php
header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$out = [
  'success' => false,
  'status'  => false,
  'message' => 'Error desconocido',
  'saldo'   => 0,
  'detalle' => ''
];

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

  $cn = (new mainModel())->connection();
  if (!$cn) {
    throw new Exception('Sin conexión a BD');
  }

  $producto_id = isset($_POST['producto_id']) ? trim($_POST['producto_id']) : '';
  $almacen_id  = isset($_POST['almacen_id']) ? trim($_POST['almacen_id']) : '';
  $lote_id     = isset($_POST['lote_id']) ? trim($_POST['lote_id']) : '';

  if ($producto_id === '') {
    throw new Exception('Producto no especificado');
  }

  $producto_id = (int)$producto_id;
  $almacen_id  = ($almacen_id !== '') ? (int)$almacen_id : 0;
  $lote_id     = ($lote_id !== '') ? (int)$lote_id : 0;

  if ($producto_id <= 0) {
    throw new Exception('Producto inválido');
  }

  /*
    IMPORTANTE:
    Tu tabla movimientos tiene estos campos:
    - productos_id
    - cantidad_entrada
    - cantidad_salida
    - saldo
    - empresa_id
    - almacen_id
    - lote_id

    Aquí se toma el último saldo registrado del producto.
    Eso es mejor que sumar todo si tu sistema ya guarda el saldo acumulado en cada movimiento.
  */

  $where = "WHERE m.productos_id = ?";

  $types = "i";
  $params = [$producto_id];

  if ($almacen_id > 0) {
    $where .= " AND m.almacen_id = ?";
    $types .= "i";
    $params[] = $almacen_id;
  }

  if ($lote_id > 0) {
    $where .= " AND m.lote_id = ?";
    $types .= "i";
    $params[] = $lote_id;
  }

  /*
    Si en tu sesión tienes empresa_id, aquí se filtra automáticamente.
    Dejé varios nombres comunes por si tu sistema usa alguno.
  */
  $empresa_id = 0;

  if (isset($_SESSION['empresa_id_sd'])) {
    $empresa_id = (int)$_SESSION['empresa_id_sd'];
  } elseif (isset($_SESSION['empresa_id'])) {
    $empresa_id = (int)$_SESSION['empresa_id'];
  } elseif (isset($_SESSION['empresa_sd'])) {
    $empresa_id = (int)$_SESSION['empresa_sd'];
  }

  if ($empresa_id > 0) {
    $where .= " AND m.empresa_id = ?";
    $types .= "i";
    $params[] = $empresa_id;
  }

  $sql = "
    SELECT
      p.productos_id,
      p.nombre,
      p.cantidad_minima,
      p.cantidad_maxima,
      COALESCE((
        SELECT mm.saldo
        FROM movimientos mm
        WHERE mm.productos_id = m.productos_id
          AND mm.almacen_id = m.almacen_id
          AND mm.lote_id = m.lote_id
          " . ($empresa_id > 0 ? " AND mm.empresa_id = ? " : "") . "
        ORDER BY mm.movimientos_id DESC
        LIMIT 1
      ), 0) AS saldo_actual
    FROM movimientos m
    INNER JOIN productos p ON p.productos_id = m.productos_id
    $where
    ORDER BY m.movimientos_id DESC
    LIMIT 1
  ";

  /*
    Como el subquery también usa empresa_id cuando existe,
    debemos agregarlo al inicio de los parámetros si aplica.
  */
  $typesFinal = $types;
  $paramsFinal = $params;

  if ($empresa_id > 0) {
    $typesFinal = "i" . $types;
    $paramsFinal = array_merge([$empresa_id], $params);
  }

  $stmt = $cn->prepare($sql);

  if (!$stmt) {
    throw new Exception('Error preparando consulta: '.$cn->error);
  }

  $stmt->bind_param($typesFinal, ...$paramsFinal);

  if (!$stmt->execute()) {
    throw new Exception('Error ejecutando consulta: '.$stmt->error);
  }

  $rs = $stmt->get_result();

  if ($rs && $rs->num_rows > 0) {
    $row = $rs->fetch_assoc();

    $saldo = isset($row['saldo_actual']) ? (float)$row['saldo_actual'] : 0;
    $producto = isset($row['nombre']) ? $row['nombre'] : 'Producto';
    $cantidad_minima = isset($row['cantidad_minima']) ? (float)$row['cantidad_minima'] : 0;

    $estado = 'Disponible';

    if ($saldo <= 0) {
      $estado = 'Sin saldo';
    } elseif ($cantidad_minima > 0 && $saldo <= $cantidad_minima) {
      $estado = 'Saldo bajo';
    }

    echo json_encode([
      'success' => true,
      'status'  => true,
      'message' => 'OK',
      'saldo'   => $saldo,
      'estado'  => $estado,
      'producto'=> $producto,
      'detalle' => 'Saldo actual según producto, bodega y lote seleccionado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  /*
    Si no existe movimiento todavía, el producto puede existir,
    pero no tiene saldo registrado.
  */
  $sqlProducto = "
    SELECT productos_id, nombre, cantidad_minima, cantidad_maxima
    FROM productos
    WHERE productos_id = ?
    LIMIT 1
  ";

  $stmtProducto = $cn->prepare($sqlProducto);

  if (!$stmtProducto) {
    throw new Exception('Error preparando consulta de producto: '.$cn->error);
  }

  $stmtProducto->bind_param("i", $producto_id);

  if (!$stmtProducto->execute()) {
    throw new Exception('Error consultando producto: '.$stmtProducto->error);
  }

  $rsProducto = $stmtProducto->get_result();

  if ($rsProducto && $rsProducto->num_rows > 0) {
    $rowProducto = $rsProducto->fetch_assoc();

    echo json_encode([
      'success' => true,
      'status'  => true,
      'message' => 'OK',
      'saldo'   => 0,
      'estado'  => 'Sin movimientos',
      'producto'=> $rowProducto['nombre'],
      'detalle' => 'Este producto existe, pero no tiene movimientos registrados para la bodega o lote seleccionado.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  throw new Exception('No se encontró el producto seleccionado');

} catch (Throwable $e) {
  $out['message'] = 'Error: '.$e->getMessage();
  $out['success'] = false;
  $out['status']  = false;
  echo json_encode($out, JSON_UNESCAPED_UNICODE);
  exit;
}