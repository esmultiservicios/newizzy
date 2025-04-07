<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

header('Content-Type: application/json'); // Asegura que la respuesta sea JSON

// Lista de campos obligatorios y sus nombres para mostrar
$camposObligatorios = [
    'user_empresa' => 'Empresa',
    'user_name' => 'Nombre',
    'user_telefono' => 'Teléfono',
    'email' => 'Correo electrónico',
    'user_pass' => 'Contraseña'
];

$missingFields = [];

// Verificar cada campo obligatorio
foreach ($camposObligatorios as $campo => $nombre) {
    if (!isset($_POST[$campo]) || empty(trim($_POST[$campo]))) {
        $missingFields[] = $nombre;
    }
}

// Si no faltan campos, procesar el registro
if (empty($missingFields)) {
    require_once "../controladores/clientesControlador.php";
    $insVarios = new clientesControlador();
    echo $insVarios->registrar_cliente_autonomo_controlador();
} else {
    http_response_code(400); // Bad Request
    echo json_encode([
        'estado' => false,
        'mensaje' => "Faltan campos obligatorios: " . implode(", ", $missingFields)
    ]);
}