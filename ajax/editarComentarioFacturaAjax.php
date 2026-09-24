<?php
/*
 * IZZY - Edición autorizada del comentario de factura
 * Respuesta JSON blindada: cualquier warning/notice generado por includes
 * o por el flujo interno no puede romper la respuesta AJAX.
 */
ob_start();
header('Content-Type: application/json; charset=utf-8');

function izzyComentarioResponder($payload, $status = 200) {
    if (ob_get_level() > 0) {
        $salidaPrevia = ob_get_contents();
        ob_clean();
        if ($salidaPrevia !== '') {
            error_log('editarComentarioFacturaAjax salida previa: ' . $salidaPrevia);
        }
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit;
}

try {
    $peticionAjax = true;
    require_once __DIR__ . '/../core/configGenerales.php';
    require_once __DIR__ . '/../controladores/facturasControlador.php';

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['colaborador_id_sd']) || empty($_SESSION['empresa_id_sd'])) {
        izzyComentarioResponder([
            'ok' => false,
            'mensaje' => 'La sesión no es válida o ha expirado.'
        ], 200);
    }

    $accion = isset($_POST['accion']) ? trim((string)$_POST['accion']) : '';
    $ins = new facturasControlador();

    if ($accion === 'consultar') {
        /*
         * IMPORTANTE:
         * - factura con comentario -> devuelve el comentario actual;
         * - notas NULL o '' -> devuelve ok=true y comentario_actual='';
         * - solamente factura inexistente/error real devuelve ok=false.
         */
        $respuesta = $ins->comentario_factura_controlador();
        if (is_array($respuesta) && !empty($respuesta['ok'])) {
            $respuesta['comentario_actual'] = isset($respuesta['comentario_actual'])
                ? (string)$respuesta['comentario_actual']
                : '';
        }
        izzyComentarioResponder($respuesta, 200);
    }

    if ($accion === 'actualizar') {
        izzyComentarioResponder($ins->actualizar_comentario_factura_controlador(), 200);
    }

    if ($accion === 'actualizar_cliente') {
        izzyComentarioResponder($ins->actualizar_cliente_factura_controlador(), 200);
    }

    izzyComentarioResponder(['ok'=>false, 'mensaje'=>'Acción no válida.'], 200);

} catch (Throwable $e) {
    error_log('editarComentarioFacturaAjax.php: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    izzyComentarioResponder([
        'ok' => false,
        'mensaje' => 'Error interno al procesar el comentario: ' . $e->getMessage()
    ], 200);
}
