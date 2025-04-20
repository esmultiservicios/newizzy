<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$result = $insMainModel->getColaboradoresConsulta();

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if($result->num_rows > 0){
    $response['success'] = true;
    while($consulta2 = $result->fetch_assoc()){
        $response['data'][] = [
            'colaboradores_id' => $consulta2['colaboradores_id'],
            'nombre' => $consulta2['nombre'],
            'identidad' => $consulta2['identidad']
        ];
    }
} else {
    $response['message'] = 'No hay datos que mostrar';
}

header('Content-Type: application/json');
echo json_encode($response);