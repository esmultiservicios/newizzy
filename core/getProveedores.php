<?php	
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$result = $insMainModel->getProveedoresConsulta();

$response = [];

if($result->num_rows > 0){
    $data = [];

    while($row = $result->fetch_assoc()){
        $data[] = [
            'proveedores_id' => $row['proveedores_id'], // nombre de campo que espera el JS
            'nombre' => $row['nombre'],
            'rtn' => $row['rtn'] ?? null // por si no trae RTN
        ];
    }

    $response = [
        'success' => true,
        'data' => $data
    ];
} else {
    $response = [
        'success' => false,
        'data' => []
    ];
}

// Importante: encabezado JSON y salida
header('Content-Type: application/json');
echo json_encode($response);
