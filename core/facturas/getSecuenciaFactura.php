<?php
$peticionAjax = true;
require_once __DIR__ . '/../../configGenerales.php';
require_once __DIR__ . '/../../mainModel.php';

$mainModel = new mainModel();

$query = "SELECT * FROM secuencia_facturacion WHERE activo = 1 AND documento_id = 1 LIMIT 1";
$result = $mainModel->ejecutar_consulta_simple($query);

if ($result->num_rows > 0) {
    $secuencia = $result->fetch_assoc();
    echo json_encode($secuencia);
} else {
    echo json_encode(['error' => 'No hay secuencia de facturación activa']);
}