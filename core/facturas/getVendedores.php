<?php
$peticionAjax = true;
require_once __DIR__ . '/../../configGenerales.php';
require_once __DIR__ . '/../../mainModel.php';

$mainModel = new mainModel();

$query = "SELECT colaboradores_id, nombre FROM colaboradores WHERE estado = 1 ORDER BY nombre";
$result = $mainModel->ejecutar_consulta_simple($query);

$vendedores = [];
while ($row = $result->fetch_assoc()) {
    $vendedores[] = $row;
}

echo json_encode($vendedores);