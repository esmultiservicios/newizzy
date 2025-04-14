<?php
//asignarMenuPlan.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

if (isset($_POST['plan_id']) && isset($_POST['menu_id'])) {
    $planId = $mainModel->cleanString($_POST['plan_id']);
    $menuId = $mainModel->cleanString($_POST['menu_id']);
    $estado = $mainModel->cleanString($_POST['estado']);
    
    // Verificar si ya existe
    $result = $mainModel->ejecutar_consulta("SELECT * FROM menu_plan WHERE planes_id = '$planId' AND menu_id = '$menuId'");
    
    if ($result->num_rows > 0) {
        // Actualizar estado
        $update = "UPDATE menu_plan SET estado = '$estado'
                   WHERE menu_id = '$menuId' AND planes_id = '$planId'";

        $mainModel->ejecutar_consulta_simple($update);
    } else {
        // Insertar nuevo
        $insert = "INSERT INTO menu_plan (menu_id, planes_id, estado) 
                   VALUES ('$menuId', '$planId', '$estado')";
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