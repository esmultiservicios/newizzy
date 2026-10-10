<?php
ob_start();
// Ubicación: core/notaCredito/listarNotasCreditoFactura.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/../../controladores/notaCreditoControlador.php';
require_once __DIR__ . '/creditoFavorService.php';

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

    $facturaId = (int)($_POST['facturas_id'] ?? 0);
    if ($facturaId <= 0) {
        responderNotaCredito(false, 'Factura inválida.');
    }

    $modo = trim((string)($_POST['modo'] ?? ''));

    if ($modo === 'credito_favor_pago') {
        $empresaId = (int)($_SESSION['empresa_id_sd'] ?? 0);

        if ($empresaId <= 0) {
            responderNotaCredito(false, 'No se pudo determinar la empresa de la sesión.');
        }

        $conexion = mainModel::staticConnection();

        $data = CreditoFavorService::resumenDisponible(
            $conexion,
            $empresaId,
            $facturaId
        );

        $conexion->close();

        responderNotaCredito(
            true,
            'Crédito a favor consultado correctamente.',
            [
                'data' => $data,
                'credito_disponible' => (float)($data['credito_disponible'] ?? 0),
                'credito_aplicable' => (float)($data['credito_aplicable'] ?? 0),
                'cantidad_notas' => (int)($data['cantidad_notas'] ?? 0)
            ]
        );
    }

    $ctrl = new notaCreditoControlador();
    $data = $ctrl->listarNotas($facturaId);

    responderNotaCredito(true, 'Notas cargadas correctamente.', ['data' => $data]);
} catch (Throwable $e) {
    error_log('NotaCredito endpoint: ' . $e->getMessage());
    responderNotaCredito(false, $e->getMessage());
}
