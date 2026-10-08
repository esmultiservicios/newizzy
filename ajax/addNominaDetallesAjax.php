<?php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once __DIR__ . '/../core/configGenerales.php';

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'title' => 'Método no permitido',
            'message' => 'La solicitud para registrar el detalle no es válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $required = [
        'nomina_id' => 'Nómina',
        'nominad_empleados' => 'Empleado'
    ];

    $missing = [];
    foreach ($required as $field => $label) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            $missing[] = $label;
        }
    }

    if ($missing) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Campos incompletos',
            'message' => 'Selecciona: ' . implode(', ', $missing) . '.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $numericFields = [
        'nominad_salario', 'salario', 'nominad_diast', 'nominad_horas25',
        'nominad_horas50', 'nominad_horas75', 'nominad_horas100',
        'nominad_retroactivo', 'nominad_bono', 'nominad_otros_ingresos',
        'nominad_deducciones', 'nominad_prestamo', 'nominad_ihss',
        'nominad_rap', 'nominad_isr', 'nominad_vale',
        'nominad_incapacidad_ihss', 'nominad_neto_ingreso',
        'nominad_neto_egreso', 'nominad_neto', 'hrse25_valor',
        'hrse50_valor', 'hrse75_valor', 'hrse100_valor'
    ];

    foreach ($numericFields as $field) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            $_POST[$field] = '0';
        } else {
            $normalized = str_replace(',', '', trim((string)$_POST[$field]));
            if (!is_numeric($normalized)) {
                echo json_encode([
                    'status' => 'error',
                    'title' => 'Valor inválido',
                    'message' => 'Revisa los importes y cantidades del detalle de nómina.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_POST[$field] = $normalized;
        }
    }

    if ((float)$_POST['nominad_salario'] <= 0) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Salario requerido',
            'message' => 'El empleado debe tener un salario mensual mayor que cero.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ((float)$_POST['nominad_diast'] < 0 || (float)$_POST['nominad_diast'] > 31) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Días inválidos',
            'message' => 'Los días trabajados deben estar entre 0 y 31.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../controladores/nominaControlador.php';
    $insVarios = new nominaControlador();
    echo (string)$insVarios->agregar_nomina_detalles_controlador();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('IZZY addNominaDetallesAjax: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'title' => 'No se pudo registrar',
        'message' => 'Ocurrió un error al registrar el detalle de nómina.'
    ], JSON_UNESCAPED_UNICODE);
}
