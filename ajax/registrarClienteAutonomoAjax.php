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
    echo json_encode($insVarios->registrar_cliente_autonomo_controlador());
} else {
    echo "<script>
        showNotify('error', 'Error', 'Faltan campos obligatorios: " . implode(", ", $missingFields) . "');
    </script>";
}