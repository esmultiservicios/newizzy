<?php
$peticionAjax = true;
require_once __DIR__ . '/../../configGenerales.php';
require_once __DIR__ . '/../../mainModel.php';

$mainModel = new mainModel();

$query = "SELECT productos_id, nombre, precio_venta, isv_venta FROM productos WHERE estado = 1 ORDER BY nombre";
$result = $mainModel->ejecutar_consulta_simple($query);

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);