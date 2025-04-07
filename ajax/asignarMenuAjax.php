<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if (isset($_POST['plan_id']) && isset($_POST['menu_id'])) {
    $insMainModel = new mainModel();

    // Obtener el último ID registrado
    $lastIdQuery = $insMainModel->ejecutar_consulta_simple("SELECT MAX(menu_plan_id) AS last_id FROM menu_plan");
    $lastIdRow = $lastIdQuery->fetch_assoc();
    $nextId = $lastIdRow['last_id'] + 1;

    $plan_id = intval($_POST['plan_id']);
    $menu_id = intval($_POST['menu_id']);

    $sql = "INSERT INTO menu_plan (menu_plan_id, menu_id, planes_id) 
            VALUES ('$nextId', '$menu_id', '$plan_id')";

    if ($insMainModel->ejecutar_consulta_simple($sql)) {
        echo json_encode([
            "type" => "success",
            "title" => "Éxito",
            "message" => "Menú asignado correctamente"
        ]);
    } else {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo asignar el menú"
        ]);
    }
} else {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}