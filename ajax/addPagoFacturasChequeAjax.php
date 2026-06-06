<?php
// ajax/addPagoFacturasChequeAjax.php
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
    'importe_cheque',
    'factura_id_cheque'
];

$labels = [
    'bk_nm_chk'          => 'Banco',
    'importe_cheque'    => 'Importe de cheque',
    'factura_id_cheque' => 'Factura'
];

$missing = validarCamposRequeridosPago($required, $labels);

if (!empty($missing)) {
    responderErrorPago("Faltan los siguientes campos obligatorios: " . implode(", ", $missing) . ".");
}

if ((string)$_POST['bk_nm_chk'] === 'undefined' || (string)$_POST['bk_nm_chk'] === 'null') {
    responderErrorPago("Debe seleccionar un banco válido.");
}

if (valorNumericoPago('importe_cheque') <= 0) {
    responderErrorPago("El importe de cheque debe ser mayor a cero.");
}

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_cheque();
