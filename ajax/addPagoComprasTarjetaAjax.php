<?php
// ajax/addPagoComprasTarjetaAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";

function obtenerPostPagoCompra($field) {
    if (!isset($_POST[$field])) {
        return '';
    }

    if (is_array($_POST[$field])) {
        $valor = '';
        foreach ($_POST[$field] as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $valor = $item;
            }
        }
        return $valor;
    }

    return trim((string)$_POST[$field]);
}

function valorNumericoPago($field) {
    $valor = obtenerPostPagoCompra($field);

    if ($valor === '') {
        return 0.0;
    }

    $valor = str_replace(["\xc2\xa0", ' '], '', $valor);
    $valor = str_ireplace(['Lps.', 'Lps', 'L.', 'L'], '', $valor);
    $valor = preg_replace('/[^0-9,\.\-]/', '', $valor);

    if ($valor === '' || $valor === '-' || $valor === '.' || $valor === ',') {
        return 0.0;
    }

    $ultimoPunto = strrpos($valor, '.');
    $ultimaComa = strrpos($valor, ',');

    if ($ultimoPunto !== false && $ultimaComa !== false) {
        if ($ultimoPunto > $ultimaComa) {
            $valor = str_replace(',', '', $valor);
        } else {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }
    } elseif ($ultimaComa !== false) {
        if (preg_match('/,\d{1,2}$/', $valor)) {
            $valor = str_replace('.', '', $valor);
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

function formatoNumeroPagoCompra($valor) {
    return number_format((float)$valor, 2, '.', '');
}

function responderErrorPagoCompra($message) {
    $title = addslashes('Error ðŸš¨');
    $message = addslashes($message);

    echo "<script>showNotify('error', '$title', '$message');</script>";
    exit;
}

function validarCamposRequeridosPagoCompra(array $required, array $labels = []) {
    $missing = [];

    foreach ($required as $field) {
        if (!isset($_POST[$field]) || trim(obtenerPostPagoCompra($field)) === '') {
            $missing[] = $labels[$field] ?? $field;
        }
    }

    return $missing;
}

$required = [
    'monto_efectivoPurchase',
    'exp',
    'cvcpwd'
];

$labels = [
    'monto_efectivoPurchase' => 'Monto tarjeta',
    'exp'                    => 'ExpiraciÃ³n',
    'cvcpwd'                 => 'CVC'
];

$missing = validarCamposRequeridosPagoCompra($required, $labels);

if (!empty($missing)) {
    responderErrorPagoCompra('Faltan los siguientes campos: ' . implode(', ', $missing) . '. Por favor, corrÃ­gelos.');
}

$montoTarjeta = valorNumericoPago('monto_efectivoPurchase');

if ($montoTarjeta <= 0) {
    responderErrorPagoCompra('El monto de tarjeta debe ser mayor a cero.');
}

$_POST['monto_efectivoPurchase'] = formatoNumeroPagoCompra($montoTarjeta);

if (isset($_POST['importe_tarjetaPurchase'])) {
    $_POST['importe_tarjetaPurchase'] = formatoNumeroPagoCompra(valorNumericoPago('importe_tarjetaPurchase'));
}

require_once "../controladores/pagoCompraControlador.php";
$insVarios = new pagoCompraControlador();

echo $insVarios->agregar_pago_compra_controlador_tarjeta();