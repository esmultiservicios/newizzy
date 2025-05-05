<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Definir puestos a excluir
$puestosExcluidos = ["'Reseller'", "'Clientes'"];
$valores = implode(", ", $puestosExcluidos);

$query = "SELECT c.colaboradores_id, c.nombre as nombre, c.identidad
          FROM colaboradores AS c
          INNER JOIN puestos AS p ON c.puestos_id = p.puestos_id
          WHERE c.estado = 1 
          AND c.colaboradores_id != 1 
          AND p.nombre NOT IN($valores)
          ORDER BY c.nombre ASC";

$result = $mainModel->ejecutar_consulta_simple($query);

$colaboradores = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $colaboradores[] = [
            "colaboradores_id" => $row['colaboradores_id'],
            "nombre" => $row['nombre'],
            "identidad" => $row['identidad'] // Agregado el campo identidad
        ];
    }
}

echo json_encode([
    "success" => true,
    "data" => $colaboradores
]);