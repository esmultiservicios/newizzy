<?php
$peticionAjax = true;
require_once __DIR__ . '/../core/configGenerales.php';
require_once __DIR__ . '/../controladores/clientesControlador.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['estado' => false, 'type' => 'error', 'title' => 'Registro', 'mensaje' => 'Utilice el formulario de registro.']);
    exit;
}
(new clientesControlador())->registrar_cliente_autonomo_controlador();
