<?php
//llenarDataTableInvetarioTransferencia.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
  return $insMainModel->showNotification([
    "title"  => "Error de sesión",
    "text"   => $validacion['mensaje'],
    "type"   => "error",
    "funcion"=> "window.location.href = '".$validacion['redireccion']."'"
  ]);
}

$datos = [
  "tipo_producto_id" => $_POST['tipo_producto_id'],
  "bodega"           => $_POST['bodega'],
  "productos_id"     => $_POST['productos_id'],
  "empresa_id_sd"    => $_SESSION['empresa_id_sd']
];

$result = $insMainModel->getTranferenciaProductos($datos);

$data = [];
while($row = $result->fetch_assoc()){
  $result_productos = $insMainModel->getCantidadProductos($row['productos_id']);

  if($result_productos && $result_productos->num_rows > 0){
    while($consulta = $result_productos->fetch_assoc()){
      $bodega = ($row['almacen_id'] == 0 || $row['almacen_id'] == null) ? "Sin bodega" : $row['bodega'];

      $data[] = [
        "fecha_registro" => $row['fecha_registro'], // 'YYYY-MM-DD HH:mm:ss'
        "barCode"        => $row['barCode'],
        "producto"       => $row['producto'],
        "medida"         => $row['medida'],
        "movimientos_id" => $row['movimientos_id'],
        "entrada"        => (float)$row['entrada'],
        "salida"         => (float)$row['salida'],
        "saldo"          => (float)$row['saldo'],
        "saldo_anterior" => (float)$row['saldo_anterior'],
        "bodega"         => $bodega,
        "id_bodega"      => $row['almacen_id'],
        "productos_id"   => $row['productos_id'],
        "superior"       => $consulta['id_producto_superior'],
        "image"          => $row['image'],
        "numero_lote"    => $row['numero_lote'],
        "empresa_id"     => $row['empresa_id'],
        "lote_id"        => $row['lote_id']
      ];
    }
  }
}

echo json_encode([
  "echo" => 1,
  "totalrecords" => count($data),
  "totaldisplayrecords" => count($data),
  "data" => $data
]);