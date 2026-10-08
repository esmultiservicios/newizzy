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
            'message' => 'La solicitud para actualizar el detalle no es válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (
        !isset($_POST['nomina_id'], $_POST['nomina_detalles_id']) ||
        trim((string)$_POST['nomina_id']) === '' ||
        trim((string)$_POST['nomina_detalles_id']) === ''
    ) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Datos incompletos',
            'message' => 'No se pudo identificar la nómina o el detalle a actualizar.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $numericFields = [
        'nominad_diast', 'nominad_horas25', 'nominad_horas50', 'nominad_horas75',
        'nominad_horas100', 'nominad_retroactivo', 'nominad_bono',
        'nominad_otros_ingresos', 'nominad_deducciones', 'nominad_prestamo',
        'nominad_ihss', 'nominad_rap', 'nominad_isr', 'nominad_vale',
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

    require_once __DIR__ . '/../controladores/nominaControlador.php';
    $insVarios = new nominaControlador();
    echo (string)$insVarios->edit_nomina_detalles_controlador();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('IZZY modificarNominaDetallesAjax: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'title' => 'No se pudo actualizar',
        'message' => 'Ocurrió un error al actualizar el detalle de nómina.'
    ], JSON_UNESCAPED_UNICODE);
}
