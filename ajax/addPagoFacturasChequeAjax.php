<?php
// ajax/addPagoFacturasChequeAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../controladores/pagoFacturaControlador.php";

header('Content-Type: application/json; charset=utf-8');

$required = ['bk_nm_chk', 'check_num', 'factura_id_cheque'];
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
$ctrl->agregar_pago_factura_controlador_cheque();