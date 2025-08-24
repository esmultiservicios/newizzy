<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
  return $insMainModel->showNotification([
    "title" => "Error de sesión",
    "text"  => $validacion['mensaje'],
    "type"  => "error",
    "funcion" => "window.location.href = '".$validacion['redireccion']."'"
  ]);
}

$datos = [
  'tipo_producto_id' => $_POST['tipo_producto_id'],
  'fechai'           => $_POST['fechai'],
  'fechaf'           => $_POST['fechaf'],
  'bodega'           => $_POST['bodega'],
  'producto'         => $_POST['producto'],
  'cliente'          => $_POST['cliente'],
  'empresa_id_sd'    => $_SESSION['empresa_id_sd']
];

$result = $insMainModel->getMovimientosProductos($datos);

$data = [];
while ($row = $result->fetch_assoc()) {

  $bodega = ($row['almacen_id'] == 0 || $row['almacen_id'] == null) ? "Sin bodega" : $row['bodega'];

  $data[] = [
    'cliente'        => $row['cliente'],
    'comentario'     => $row['comentario'],
    'movimientos_id' => $row['movimientos_id'],
    'fecha_registro' => $row['fecha_registro'], // 'YYYY-MM-DD HH:mm:ss'
    'barCode'        => $row['barCode'],
    'producto'       => $row['producto'],
    'medida'         => $row['medida'],
    'documento'      => $row['documento'],

    // Números crudos (el front los formatea y ordena correctamente)
    'entrada'        => (float)$row['entrada'],
    'salida'         => (float)$row['salida'],
    'saldo'          => (float)$row['saldo'],
    'saldo_anterior' => (float)$row['saldo_anterior'],

    'bodega'         => $bodega,
    'id_bodega'      => $row['almacen_id'],
    'productos_id'   => $row['productos_id'],
    'numero_lote'    => $row['numero_lote'],
    'image'          => $row['image']
  ];
}

echo json_encode([
  'echo' => 1,
  'totalrecords' => count($data),
  'totaldisplayrecords' => count($data),
  'data' => $data
]);
