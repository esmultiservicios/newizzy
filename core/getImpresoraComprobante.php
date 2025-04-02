<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Obtener el formato del POST
$formato = $_POST["formato"] ?? null;

// Obtener los resultados de la consulta
$result = $insMainModel->consultaImpresoraFormato($formato);

$data = [];

while ($row = $result->fetch_assoc()) {
    // Asignar el formato según la descripción, con prioridad para 'Media Carta'
    $data[] = [
        "impresora_id" => $row['impresora_id'],
        "estado"       => $row['estado'],
        "formato"      => strpos($row['descripcion'], 'Media Carta') !== false ? 'Media Carta' :
                         (strpos($row['descripcion'], 'Carta') !== false ? 'Carta' :
                         (strpos($row['descripcion'], 'Ticket') !== false ? 'Ticket' : 'Desconocido')),
    ];
}

// Retornar el resultado como JSON
echo json_encode($data);
