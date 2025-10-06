<?php
// ajax/loginAjax.php
$peticionAjax = true;
header('Content-Type: application/json; charset=UTF-8');

require_once '../core/configGenerales.php';

try {
    $token = $_POST['token'] ?? $_GET['token'] ?? null;

    if ($token !== null && $token !== '') {
        require_once '../controladores/loginControlador.php';
        $logout = new loginControlador();
        // Devuelve JSON robusto
        echo $logout->cerrar_sesion_controlador_json($token);
        exit;
    }

    // Si llega aquí, no es logout con token → responde JSON (no <script>)
    $missing = [];
    if (!isset($_POST['email']))    $missing[] = 'Correo Electrónico';
    if (!isset($_POST['password'])) $missing[] = 'Contraseña';

    if ($missing) {
        echo json_encode([
            'ok'      => false,
            'title'   => 'Error 🚨',
            'message' => 'Faltan los siguientes campos: ' . implode(', ', $missing) . '. Por favor, corrígelos.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'ok'      => false,
            'title'   => 'Solicitud inválida',
            'message' => 'Parámetros insuficientes.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode([
        'ok'      => false,
        'title'   => 'Error',
        'message' => 'Excepción: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}