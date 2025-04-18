<?php
//eliminarPlan.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['plan_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de plan no recibido'
    ]);
    exit();
}

$insMainModel = new mainModel();
$planId = $insMainModel->cleanStringConverterCase($_POST['plan_id']);

try {
    $resultado = $insMainModel->eliminar_plan_modelo($planId);
    
    echo json_encode($resultado);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}

exit();