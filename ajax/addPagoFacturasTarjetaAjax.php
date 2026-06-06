<?php
// ajax/addPagoFacturasTarjetaAjax.php
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
    'monto_efectivo',
    'importe_tarjeta',
    'factura_id_tarjeta'
];

$labels = [
    'monto_efectivo'     => 'Monto aplicado',
    'importe_tarjeta'    => 'Importe de tarjeta',
    'factura_id_tarjeta' => 'Factura'
];

$missing = validarCamposRequeridosPago($required, $labels);

if (!empty($missing)) {
    responderErrorPago("Faltan los siguientes campos obligatorios: " . implode(", ", $missing) . ".");
}

if (valorNumericoPago('importe_tarjeta') <= 0) {
    responderErrorPago("El importe de tarjeta debe ser mayor a cero.");
}

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_tarjeta();
