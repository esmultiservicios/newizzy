<?php
// ajax/addPagoFacturasTarjetaAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../controladores/pagoFacturaControlador.php";

header('Content-Type: application/json; charset=utf-8');

$required = ['monto_efectivo', 'exp', 'cvcpwd', 'factura_id_tarjeta'];
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
$ctrl->agregar_pago_factura_controlador_tarjeta();