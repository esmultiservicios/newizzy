<?php
ob_start();
// Ubicación: core/notaCredito/registrarNotaCredito.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/../../controladores/notaCreditoControlador.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('SD');
    session_start();
}


function responderNotaCredito($success, $message, array $extra = [])
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $mainModel = new mainModel();

    if (method_exists($mainModel, 'validarSesion')) {
        $validacion = $mainModel->validarSesion();

        if (is_array($validacion) && !empty($validacion['error'])) {
            responderNotaCredito(false, $validacion['mensaje'] ?? 'Sesión inválida.');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderNotaCredito(false, 'Método no permitido.');
}

$payload = json_decode((string)($_POST['data'] ?? ''), true);
if (!is_array($payload)) {
    responderNotaCredito(false, 'Los datos recibidos no son válidos.');
}

$ctrl = new notaCreditoControlador();
$data = $ctrl->registrarNota($payload);

responderNotaCredito(
    true,
    'Nota de Crédito emitida correctamente.',
    ['data' => $data]
);
} catch (Throwable $e) {
    error_log('NotaCredito endpoint: ' . $e->getMessage());
    responderNotaCredito(false, $e->getMessage());
}
