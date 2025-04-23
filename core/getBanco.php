<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json'); // Especificamos que la respuesta es JSON

try {
    $insMainModel = new mainModel();
    $result = $insMainModel->getBanco();

    $bancos = [];
    if($result->num_rows > 0) {
        while($consulta2 = $result->fetch_assoc()) {
            $bancos[] = [
                'banco_id' => $consulta2['banco_id'],
                'nombre' => $consulta2['nombre']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $bancos
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener bancos: ' . $e->getMessage()
    ]);
}