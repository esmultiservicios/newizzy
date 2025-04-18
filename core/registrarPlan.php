<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['nombre_plan']) || !isset($_POST['estado_plan'])) {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
    exit();
}

$insMainModel = new mainModel();

try {
    // Procesar datos
    $datosPlan = [
        'nombre' => $insMainModel->cleanStringConverterCase($_POST['nombre_plan']),
        'estado' => intval($_POST['estado_plan']),
        'fecha_registro' => date("Y-m-d H:i:s"),
        'configuraciones' => isset($_POST['configuraciones_json']) ? $_POST['configuraciones_json'] : null
    ];

    // Validaciones
    if (empty($datosPlan['nombre'])) {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "El nombre del plan es requerido"
        ]);
        exit();
    }

    // Validar JSON de configuraciones
    if ($datosPlan['configuraciones']) {
        $configArray = json_decode($datosPlan['configuraciones'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode([
                "type" => "error",
                "title" => "Error",
                "message" => "El formato de configuraciones no es válido"
            ]);
            exit();
        }
        $datosPlan['configuraciones'] = json_encode($configArray);
    }

    $resultado = $insMainModel->registrar_plan_modelo($datosPlan);

    if ($resultado['success']) {
        echo json_encode([
            "type" => "success",
            "title" => "Éxito",
            "message" => $resultado['message'],
            "id" => $resultado['insert_id']
        ]);
    } else {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo registrar el plan: " . $resultado['error']
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Error en el servidor: " . $e->getMessage()
    ]);
}

exit();