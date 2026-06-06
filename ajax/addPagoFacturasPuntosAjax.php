<?php
// ajax/addPagoFacturasPuntosAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../controladores/pagoFacturaControlador.php";

header('Content-Type: application/json; charset=utf-8');


function validarCamposRequeridosPago(array $required, array $labels = []) {
    $missing = [];

    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim((string)$_POST[$field]) === '') {
            $missing[] = $labels[$field] ?? $field;
        }
    }

    return $missing;
}

function valorNumericoPago($field) {
    if (!isset($_POST[$field])) {
        return 0;
    }

    $valor = str_replace([',', 'L', 'l', ' '], '', (string)$_POST[$field]);
    $valor = preg_replace('/[^0-9.\-]/', '', $valor);

    return is_numeric($valor) ? (float)$valor : 0;
}

function responderErrorPago($message) {
    echo json_encode([
        "status"  => false,
        "title"   => "Error",
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


$required = [
    'puntos_disponibles',
    'puntos_usar',
    'factura_id_puntos'
];

$labels = [
    'puntos_disponibles' => 'Puntos disponibles',
    'puntos_usar'        => 'Puntos a usar',
    'factura_id_puntos'  => 'Factura'
];

$missing = validarCamposRequeridosPago($required, $labels);

if (!empty($missing)) {
    responderErrorPago("Faltan los siguientes campos obligatorios: " . implode(", ", $missing) . ".");
}

if (valorNumericoPago('puntos_disponibles') <= 0) {
    responderErrorPago("Los puntos disponibles deben ser mayores a cero.");
}

if (valorNumericoPago('puntos_usar') <= 0) {
    responderErrorPago("Los puntos a usar deben ser mayores a cero.");
}

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_puntos();