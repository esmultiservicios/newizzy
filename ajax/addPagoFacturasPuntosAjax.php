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

$puntosDisponibles = valorNumericoPago('puntos_disponibles');
$puntosUsar = valorNumericoPago('puntos_usar');

if ($puntosDisponibles <= 0) {
    responderErrorPago("Los puntos disponibles deben ser mayores a cero.");
}

if ($puntosUsar <= 0) {
    responderErrorPago("Los puntos a usar deben ser mayores a cero.");
}

if ($puntosUsar > $puntosDisponibles + 0.0001) {
    responderErrorPago("Los puntos a usar no pueden ser mayores a los puntos disponibles.");
}

normalizarPostNumericoPago([
    'total_pago',
    'customer_bill_pay',
    'monto_efectivo',
    'importe_puntos',
    'puntos_disponibles',
    'puntos_usar'
]);

$_POST['puntos_disponibles'] = number_format($puntosDisponibles, 2, '.', '');
$_POST['puntos_usar'] = number_format($puntosUsar, 2, '.', '');

$ctrl = new pagoFacturaControlador();
$ctrl->agregar_pago_factura_controlador_puntos();