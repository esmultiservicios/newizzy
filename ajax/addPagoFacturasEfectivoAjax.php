<?php
// ajax/addPagoFacturasEfectivoAjax.php
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
        return 0.0;
    }

    $valor = trim((string)$_POST[$field]);

    if ($valor === '') {
        return 0.0;
    }

    $valor = str_replace(["\xc2\xa0", ' '], '', $valor);
    $valor = str_ireplace(['Lps.', 'Lps', 'L.', 'L'], '', $valor);
    $valor = preg_replace('/[^0-9,\.\-]/', '', $valor);

    if ($valor === '' || $valor === '-' || $valor === '.' || $valor === ',') {
        return 0.0;
    }

    $ultimaComa = strrpos($valor, ',');
    $ultimoPunto = strrpos($valor, '.');

    if ($ultimaComa !== false && $ultimoPunto !== false) {
        if ($ultimaComa > $ultimoPunto) {
            // Formato 1.500,00
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } else {
            // Formato 1,500.00
            $valor = str_replace(',', '', $valor);
        }
    } elseif ($ultimaComa !== false) {
        if (preg_match('/,\d{1,2}$/', $valor)) {
            $valor = str_replace(',', '.', $valor);
        } else {
            $valor = str_replace(',', '', $valor);
        }
    } elseif ($ultimoPunto !== false) {
        if (!preg_match('/\.\d{1,2}$/', $valor)) {
            $valor = str_replace('.', '', $valor);
        }
    }

    if (substr_count($valor, '-') > 1 || (strpos($valor, '-') !== false && strpos($valor, '-') !== 0)) {
        return 0.0;
    }

    return is_numeric($valor) ? (float)$valor : 0.0;
}

function normalizarPostNumericoPago(array $fields) {
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $_POST[$field] = number_format(valorNumericoPago($field), 2, '.', '');
        }
    }
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
    'efectivo_bill',
    'factura_id_efectivo'
];

$labels = [
    'efectivo_bill'       => 'Efectivo recibido',
    'factura_id_efectivo' => 'Factura'
];

$missing = validarCamposRequeridosPago($required, $labels);

if (!empty($missing)) {
    responderErrorPago("Faltan los siguientes campos obligatorios: " . implode(", ", $missing) . ".");
}

$efectivoRecibido = valorNumericoPago('efectivo_bill');
$montoAplicado = valorNumericoPago('monto_efectivo');

if ($montoAplicado <= 0) {
    $montoAplicado = valorNumericoPago('total_pago');
}

if ($montoAplicado <= 0) {
    $montoAplicado = valorNumericoPago('customer_bill_pay');
}

if ($efectivoRecibido <= 0) {
    responderErrorPago("El efectivo recibido debe ser mayor a cero.");
}

if ($montoAplicado <= 0) {
    responderErrorPago("El monto aplicado debe ser mayor a cero.");
}

normalizarPostNumericoPago([
    'total_pago',
    'customer_bill_pay',
    'monto_efectivo',
    'efectivo_bill',
    'efectivo_bill_bk',
    'cambio_efectivo',
    'cambio_efectivo_bk'
]);

$_POST['monto_efectivo'] = number_format($montoAplicado, 2, '.', '');
$_POST['efectivo_bill'] = number_format($efectivoRecibido, 2, '.', '');
$_POST['cambio_efectivo'] = number_format(max(0, $efectivoRecibido - $montoAplicado), 2, '.', '');

if (isset($_POST['efectivo_bill_bk'])) {
    $_POST['efectivo_bill_bk'] = $_POST['efectivo_bill'];
}

if (isset($_POST['cambio_efectivo_bk'])) {
    $_POST['cambio_efectivo_bk'] = $_POST['cambio_efectivo'];
}

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_efectivo();