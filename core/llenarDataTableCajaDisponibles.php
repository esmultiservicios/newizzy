<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

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
    "fechai" => $_POST['fechai'],
    "fechaf" => $_POST['fechaf'],
    "estado" => $_POST['estado'],
    "privilegio_id" => $_SESSION['privilegio_sd'],
    "colaborador_id" => $_SESSION['colaborador_id_sd'],
	"empresa_id_sd" => $_SESSION['empresa_id_sd'],
];

$result = $insMainModel->getCajas($datos);

$arreglo = array();
$importe_venta = 0;
$neto = 0;

$data = array();

while ($row = $result->fetch_assoc()) {
    $apertura_id = $row['apertura_id'];

    $result_venta = $insMainModel->getImporteVentaporUsuario($apertura_id);
    $row1 = $result_venta->fetch_assoc();
    $importe_venta = $row1['importe'];
    $factura_inicial = "";
    $neto = $importe_venta + $row['monto_apertura'];

    if ($row['factura_inicial'] == "") {
        $result_facturaInicial = $insMainModel->getFacturaInicial($apertura_id);
        $row_facturaInicial = $result_facturaInicial->fetch_assoc();

        // Verificación para evitar el error de acceso a índice nulo
        $factura_inicial = "";
        if ($row_facturaInicial && is_array($row_facturaInicial)) {
            $factura_inicial = $row_facturaInicial['prefijo'] . "" . str_pad($row_facturaInicial['numero'], $row_facturaInicial['relleno'], "0", STR_PAD_LEFT);
        }
    } else {
        $factura_inicial = $row['factura_inicial'];
    }

    $data[] = array(
        "apertura_id" => $apertura_id,
        "fecha" => $row['fecha'],
        "factura_inicial" => $factura_inicial,
        "factura_final" => $row['factura_final'],
        "caja" => $row['caja'],
        "usuario" => $row['usuario'],
        "monto_apertura" => isset($row['monto_apertura']) ? $row['monto_apertura'] : 0,
        "importe_venta" => isset($importe_venta) ? $importe_venta : 0,
        "neto" => $neto
    );
}

$arreglo = array(
    "echo" => 1,
    "totalrecords" => count($data),
    "totaldisplayrecords" => count($data),
    "data" => $data
);

echo json_encode($arreglo);
?>