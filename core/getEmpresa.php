<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

$datos = [    
    "empresa_id" => $_SESSION['empresa_id_sd'],
    "privilegio_colaborador" => $_SESSION['privilegio_sd']
];

$result = $insMainModel->getEmpresaSelect($datos);

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

if($result->num_rows > 0){
    $response['success'] = true;
    while($consulta2 = $result->fetch_assoc()){
        $response['data'][] = [
            'empresa_id' => $consulta2['empresa_id'],
            'nombre' => $consulta2['nombre']
        ];
    }
} else {
    $response['message'] = 'No hay datos que mostrar';
}

header('Content-Type: application/json');
echo json_encode($response);
?>