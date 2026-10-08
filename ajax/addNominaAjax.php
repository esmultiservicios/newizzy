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
            'message' => 'La solicitud para registrar la nómina no es válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $required = [
        'nomina_detale' => 'Detalle',
        'nomina_pago_planificado_id' => 'Pago planificado',
        'nomina_empresa_id' => 'Empresa',
        'tipo_nomina' => 'Tipo de nómina',
        'nomina_fecha_inicio' => 'Fecha de inicio',
        'nomina_fecha_fin' => 'Fecha final',
        'pago_nomina' => 'Cuenta de pago'
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
            'message' => 'Completa: ' . implode(', ', $missing) . '.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $inicio = (string)$_POST['nomina_fecha_inicio'];
    $fin = (string)$_POST['nomina_fecha_fin'];

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Fechas inválidas',
            'message' => 'Revisa la fecha de inicio y la fecha final de la nómina.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($fin < $inicio) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Rango de fechas inválido',
            'message' => 'La fecha final no puede ser anterior a la fecha de inicio.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (mb_strlen(trim((string)$_POST['nomina_detale'])) > 100) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Detalle demasiado largo',
            'message' => 'El detalle de la nómina admite un máximo de 100 caracteres.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../controladores/nominaControlador.php';

    if (!class_exists('nominaControlador')) {
        throw new RuntimeException('No fue posible cargar el controlador de nómina.');
    }

    $insNomina = new nominaControlador();
    echo (string)$insNomina->agregar_nomina_controlador();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('IZZY addNominaAjax: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'title' => 'No se pudo registrar',
        'message' => 'Ocurrió un error al registrar la nómina. Intenta nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
}
