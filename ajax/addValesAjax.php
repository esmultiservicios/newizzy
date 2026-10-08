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
            'message' => 'La solicitud para registrar el vale no es válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $required = [
        'vale_fecha' => 'Fecha',
        'vale_empleado' => 'Empleado',
        'vale_monto' => 'Monto del vale'
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

    $monto = str_replace(',', '', trim((string)$_POST['vale_monto']));
    if (!is_numeric($monto) || (float)$monto <= 0) {
        echo json_encode([
            'status' => 'error',
            'title' => 'Monto inválido',
            'message' => 'El monto del vale debe ser mayor que cero.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $_POST['vale_monto'] = $monto;

    require_once __DIR__ . '/../controladores/nominaControlador.php';

    if (!class_exists('nominaControlador')) {
        throw new RuntimeException('No fue posible cargar el controlador de nómina.');
    }

    $ctrl = new nominaControlador();
    $res = $ctrl->agregar_vale_controlador();

    if (is_array($res)) {
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    } else {
        $texto = trim((string)$res);
        if ($texto === '' || ($texto[0] ?? '') !== '{') {
            throw new RuntimeException('El controlador no devolvió una respuesta JSON válida.');
        }
        echo $texto;
    }
} catch (Throwable $e) {
    http_response_code(500);
    error_log('IZZY addValesAjax: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'title' => 'No se pudo registrar',
        'message' => 'Ocurrió un error al registrar el vale. Intenta nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
}
