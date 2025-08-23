<?php
// core/llenarDataTableReporteVentas.php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$datos = [
    'tipo_factura_reporte' => $_POST['tipo_factura_reporte'],
    'fechai'               => $_POST['fechai'],
    'fechaf'               => $_POST['fechaf'],
    'facturador'           => $_POST['facturador'],
    'vendedor'             => $_POST['vendedor'],
    'factura'              => $_POST['factura'],
    'empresa_id_sd'        => $_SESSION['empresa_id_sd'],
];

$result = $insMainModel->consultaVentas($datos);

$arreglo = array();
$data = [];

while ($row = $result->fetch_assoc()) {
    $ganancia = doubleval($row['subtotal']) - doubleval($row['subCosto']) - doubleval($row['isv']) - doubleval($row['descuento']);

    $data[] = array(
        'facturas_id'            => $row['facturas_id'],
        'fecha'                  => $row['fecha'],
        'tipo_documento'         => $row['tipo_documento'],
        'cliente'                => $row['cliente'],
        'numero'                 => $row['numero'],
        'numero_sort'          => (int)$row['number'],        // <--- CLAVE DE ORDEN REAL
        'number'                 => intval($row['number']),
        'subtotal'               => $row['subtotal'],
        'ganancia'               => $ganancia,
        'isv'                    => $row['isv'],
        'descuento'              => $row['descuento'],
        'total'                  => $row['total'],
        'vendedor'               => $row['vendedor'],
        'facturador'             => $row['facturador'],
        'estado_pago'            => $row['estado_pago'],     // para facturas
        'tipo_factura'           => $row['tipo_factura'],
        'documento_id'           => $row['documento_id'],    // 4 = Proforma
        'proforma_estado'        => $row['proforma_estado'], // 0 = Abierta, 1 = Cerrada
        'facturas_proforma_id'   => $row['facturas_proforma_id'] // <-- NUEVO
    );
}

$arreglo = array(
    'echo' => 1,
    'totalrecords' => count($data),
    'totaldisplayrecords' => count($data),
    'data' => $data
);

echo json_encode($arreglo);