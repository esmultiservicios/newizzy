<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

header('Content-Type: application/json');

if(!isset($_POST['plan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de plan no recibido'
    ]);
    exit();
}

$insMainModel = new mainModel();
$planId = $insMainModel->cleanStringConverterCase($_POST['plan_id']);

try {
    $resultado = $insMainModel->obtener_plan_modelo($planId);
    
    if ($resultado['success']) {
        // Procesar configuraciones para asegurar que es un array
        $configs = [];
        if (!empty($resultado['data']['configuraciones'])) {
            $configs = json_decode($resultado['data']['configuraciones'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $configs = [];
            }
        }
        $resultado['data']['configuraciones_json'] = $configs;
    }
    
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}

exit();