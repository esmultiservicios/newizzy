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

if (isset($_POST['bk_nm_chk']) && ((string)$_POST['bk_nm_chk'] === 'undefined' || (string)$_POST['bk_nm_chk'] === 'null')) {
    responderErrorPago("Debe seleccionar un banco válido.");
}

$importeCheque = valorNumericoPago('importe_cheque');

if ($importeCheque <= 0) {
    responderErrorPago("El importe de cheque debe ser mayor a cero.");
}

normalizarPostNumericoPago([
    'total_pago',
    'customer_bill_pay',
    'monto_efectivo',
    'importe_cheque'
]);

$_POST['importe_cheque'] = number_format($importeCheque, 2, '.', '');

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_cheque();