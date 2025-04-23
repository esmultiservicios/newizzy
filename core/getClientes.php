<?php	
    $peticionAjax = true;
    require_once "configGenerales.php";
    require_once "mainModel.php";
    
    $insMainModel = new mainModel();
    
    $result = $insMainModel->getClientesConsulta();
    
    $response = [
        'success' => false,
        'data' => []
    ];
    
    if($result->num_rows > 0){
        $clientes = [];
        while($consulta2 = $result->fetch_assoc()){
            $clientes[] = [
                'clientes_id' => $consulta2['clientes_id'], // Note: JavaScript expects 'colaboradores_id'
                'nombre' => $consulta2['nombre'],
                'rtn' => $consulta2['rtn'] ?? null // Assuming there might be an identidad field
            ];
        }
        $response['success'] = true;
        $response['data'] = $clientes;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);