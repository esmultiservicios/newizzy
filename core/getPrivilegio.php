<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

if(!isset($_SESSION['user_sd'])){ 
    session_start(['name'=>'SD']); 
}

$datos = [
    "privilegio_id" => $_SESSION['privilegio_sd'],
    "colaborador_id" => $_SESSION['colaborador_id_sd'],
    "DB_MAIN" => $_SESSION['db_cliente'],        
];    

$result = $insMainModel->getPrivilegio($datos);

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if($result->num_rows > 0){
    $response['success'] = true;
    while($consulta2 = $result->fetch_assoc()){
        $response['data'][] = [
            'privilegio_id' => $consulta2['privilegio_id'],
            'nombre' => $consulta2['nombre']
        ];
    }
} else {
    $response['message'] = 'No hay datos que mostrar';
}

header('Content-Type: application/json');
echo json_encode($response);