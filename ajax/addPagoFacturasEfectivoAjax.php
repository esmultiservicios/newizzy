<?php
// ajax/addPagoFacturasEfectivoAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../controladores/pagoFacturaControlador.php";

header('Content-Type: application/json; charset=utf-8');

// Validación mínima (si te sirve mantenerla):
$required = ['monto_efectivo', 'efectivo_bill', 'factura_id_efectivo'];
$missing  = array_values(array_diff($required, array_keys($_POST)));
if (!empty($missing)) {
    echo json_encode([
        "status"=>false,
        "title"=>"Error",
        "message"=>"Faltan los siguientes campos: ".implode(", ", $missing)."."
    ]);
    exit;
}

$ctrl = new pagoFacturaControlador();
// IMPORTANTE: este método imprime JSON y hace exit por dentro.
$ctrl->agregar_pago_factura_controlador_efectivo();