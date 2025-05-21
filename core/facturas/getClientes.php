<?php
//getClientesphp
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$query = "SELECT clientes_id, nombre, rtn FROM clientes WHERE estado = 1 ORDER BY nombre";
$result = $mainModel->ejecutar_consulta_simple($query);

$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

echo json_encode($clientes);