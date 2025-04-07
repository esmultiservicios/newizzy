<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if (isset($_POST['nombre_plan']) && isset($_POST['usuarios_plan']) && isset($_POST['estado_plan'])) {
    $insMainModel = new mainModel();

    // Obtener el último ID registrado
    $lastIdQuery = $insMainModel->ejecutar_consulta_simple("SELECT MAX(planes_id) AS last_id FROM planes");
    $lastIdRow = $lastIdQuery->fetch_assoc();
    $nextId = $lastIdRow['last_id'] + 1;

    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre_plan']);
    $usuarios = intval($_POST['usuarios_plan']);
    $estado = intval($_POST['estado_plan']);
    $fecha_registro = date("Y-m-d H:i:s");

    $sql = "INSERT INTO planes (planes_id, nombre, usuarios, estado, fecha_registro) 
            VALUES ('$nextId', '$nombre', '$usuarios', '$estado', '$fecha_registro')";

    if ($insMainModel->ejecutar_consulta_simple($sql)) {
        echo json_encode([
            "type" => "success",
            "title" => "Éxito",
            "message" => "Plan registrado correctamente"
        ]);
    } else {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo completar la operación"
        ]);
    }
} else {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}