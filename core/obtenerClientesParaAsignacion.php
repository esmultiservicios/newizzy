<?php
//obtenerClientesParaAsignacion.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

$query = "SELECT sc.clientes_id, c.nombre, c.rtn as identificacion 
          FROM server_customers sc
          JOIN clientes c ON sc.clientes_id = c.clientes_id
          WHERE sc.estado = 1
          ORDER BY c.nombre ASC";
$result = $mainModel->ejecutar_consulta_simple($query);

$clientes = [];
while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $clientes
]);