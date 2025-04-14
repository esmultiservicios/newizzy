<?php
//asignarSubmenu2Plan.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

if (isset($_POST['plan_id']) && isset($_POST['submenu1_id'])) {
    $planId = $mainModel->cleanString($_POST['plan_id']);
    $submenu1Id = $mainModel->cleanString($_POST['submenu1_id']);
    $estado = $mainModel->cleanString($_POST['estado']);
    
    $result = $mainModel->ejecutar_consulta("SELECT * FROM submenu1_plan WHERE planes_id = '$planId' AND submenu1_id = '$submenu1Id'");
    
    if ($result->num_rows > 0) {
        // Actualizar estado
        $update = "UPDATE submenu1_plan SET estado = '$estado'
                   WHERE submenu1_id = '$submenu1Id' AND planes_id = '$planId'";
        $mainModel->ejecutar_consulta_simple($update);
    } else {
        // Insertar nuevo
        $insert = "INSERT INTO submenu1_plan (submenu1_id, planes_id, estado) 
                   VALUES ('$submenu1Id', '$planId', '$estado')";
        $mainModel->ejecutar_consulta_simple($insert);
    }
    echo json_encode([
        'type' => 'success',  // El tipo de la respuesta, puede ser 'success' o 'error'
        'title' => 'Operación exitosa',
        'message' => 'El registro se ha actualizado correctamente.',
        'estado' => true  // true indica éxito
    ]);
} else {
    echo json_encode([
        'type' => 'error',  // El tipo de la respuesta, puede ser 'error'
        'title' => 'Error en la operación',
        'message' => 'Hubo un problema al procesar la solicitud.',
        'estado' => false  // false indica error
    ]);
}