<?php
if ($peticionAjax) {
    require_once "../modelos/mainModel.php";
} else {
    require_once "./modelos/mainModel.php";
}

session_start(['name' => 'SD']);

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mainModel = new mainModel();
    
    $codigo_bitacora = $_POST['codigo_bitacora'] ?? '';
    $hora_salida = $_POST['hora_salida'] ?? '';
    
    $result = $mainModel->actualizar_hora_salida_bitacora($codigo_bitacora, $hora_salida);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Bitácora actualizada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la bitácora']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}