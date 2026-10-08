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
            'message' => 'La solicitud para registrar el contrato no es válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once __DIR__ . '/../controladores/contratoControlador.php';
    $insVarios = new contratoControlador();
    echo (string)$insVarios->agregar_contrato_controlador();
} catch (Throwable $e) {
    http_response_code(500);
    error_log('IZZY addContratosAjax: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'title' => 'No se pudo registrar',
        'message' => 'Ocurrió un error al registrar el contrato. Intenta nuevamente.'
    ], JSON_UNESCAPED_UNICODE);
}
