<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

header('Content-Type: application/json');

if (!isset($_POST['plan_id']) || !isset($_POST['nombre_plan'])) {
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
        'plan_id' => $insMainModel->cleanStringConverterCase($_POST['plan_id']),
        'nombre' => $insMainModel->cleanStringConverterCase($_POST['nombre_plan']),
        // Elimina la línea de usuarios o proporcióna un valor por defecto
        'estado' => intval($_POST['estado_plan']),
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

    $resultado = $insMainModel->actualizar_plan_modelo($datosPlan);

    if ($resultado['success']) {
        echo json_encode([
            "type" => "success",
            "title" => "Éxito",
            "message" => "Plan actualizado correctamente",
            "affected_rows" => $resultado['affected_rows']
        ]);
    } else {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo actualizar el plan: " . $resultado['error']
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