<?php
//obtenerSistemas.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

$query = "SELECT sistema_id, nombre FROM sistema WHERE estado = 1 ORDER BY nombre ASC";
$result = $mainModel->ejecutar_consulta_simple($query);

$sistemas = [];
while ($row = $result->fetch_assoc()) {
    $sistemas[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $sistemas
]);