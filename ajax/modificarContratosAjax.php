<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

// Configurar cabecera JSON primero
header('Content-Type: application/json');

// Campos obligatorios para edición
$requiredFields = [
    'contrato_id' => 'ID del Contrato',
    'contrato_colaborador_id' => 'Empleado',
    'contrato_tipo_contrato_id' => 'Tipo Contrato',
    'contrato_pago_planificado_id' => 'Pago Planificado',
    'contrato_tipo_empleado_id' => 'Tipo Empleado',
    'contrato_salario_mensual' => 'Salario Mensual',
    'contrato_fecha_inicio' => 'Fecha Inicio'
];

// Verificar campos obligatorios
$missingFields = [];
foreach ($requiredFields as $field => $name) {
    if (!isset($_POST[$field]) || $_POST[$field] === '') {
        $missingFields[] = $name;
    }
}

if (!empty($missingFields)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "error",
        "title" => "Error de validación",
        "message" => "Faltan los siguientes campos obligatorios: " . implode(", ", $missingFields),
        "missing_fields" => $missingFields
    ]);
    exit();
}

try {
    require_once "../controladores/contratoControlador.php";
    $insVarios = new contratoControlador();
    $response = $insVarios->edit_contrato_controlador();
    
    // Asegurar que la respuesta sea JSON
    if (is_array($response) || is_object($response)) {
        echo json_encode($response);
    } else {
        echo $response; // Asumimos que ya está en formato JSON si es string
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        "status" => "error",
        "title" => "Error en el servidor",
        "message" => "Ocurrió un error inesperado: " . $e->getMessage(),
        "error_details" => $e->getTraceAsString()
    ]);
}