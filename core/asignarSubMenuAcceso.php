<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$mainModel = new mainModel();

if (isset($_POST['submenu_id']) && isset($_POST['privilegio_id']) && isset($_POST['estado'])) {
    $submenu_id = $mainModel->cleanString($_POST['submenu_id']);
    $privilegio_id = $mainModel->cleanString($_POST['privilegio_id']);
    $estado = $mainModel->cleanString($_POST['estado']);
    $fecha_registro = date("Y-m-d H:i:s");

    // Verificar si ya existe el registro
    $query = "SELECT acceso_submenu_id FROM acceso_submenu 
              WHERE submenu_id = '$submenu_id' AND privilegio_id = '$privilegio_id'";
    $result = $mainModel->ejecutar_consulta_simple($query);

    if ($result->num_rows > 0) {
        // Actualizar
        $update = "UPDATE acceso_submenu 
                   SET estado = '$estado', fecha_registro = '$fecha_registro' 
                   WHERE submenu_id = '$submenu_id' AND privilegio_id = '$privilegio_id'";
        $mainModel->ejecutar_consulta_simple($update);
    } else {
        // Insertar
        $insert = "INSERT INTO acceso_submenu (submenu_id, privilegio_id, estado, fecha_registro) 
                   VALUES ('$submenu_id', '$privilegio_id', '$estado', '$fecha_registro')";
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
