<?php
//obtenerPlanesActivos.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

$query = "SELECT planes_id, nombre FROM planes WHERE estado = 1 ORDER BY nombre ASC";
$result = $mainModel->ejecutar_consulta_simple($query);

$planes = [];
while ($row = $result->fetch_assoc()) {
    $planes[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $planes
]);