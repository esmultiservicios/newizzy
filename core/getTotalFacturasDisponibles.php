<?php
// getTotalFacturasDisponibles.php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

// Validar sesión
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    echo $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
    ]);
    exit;
}

$empresa_id = $_SESSION['empresa_id_sd'];

$ultimoNumeroUsado = 0;
$rango_inicial = 0;
$rango_final = 0;
$contador = 0;
$fecha_limite = 'Sin definir';

// Obtener siguiente (último usado)
$resultNumero = $insMainModel->getTotalFacturasDisponiblesDB($empresa_id);
if ($resultNumero->num_rows > 0) {
    $row = $resultNumero->fetch_assoc();
    $ultimoNumeroUsado = (int)$row['numero'];
}

// Obtener rango inicial y final
$resultRango = $insMainModel->getNumeroMaximoPermitido($empresa_id);
if ($resultRango->num_rows > 0) {
    $row = $resultRango->fetch_assoc();
    $rango_final = (int)$row['rango_final'];
    $rango_inicial = (int)$row['rango_inicial'];
}

// Calcular total de facturas disponibles
if ($ultimoNumeroUsado === 0 || $ultimoNumeroUsado === $rango_inicial) {
    // No se ha usado ninguna factura o estamos en el primer número
    $totalFacturas = $rango_final - $rango_inicial + 1;
    $facturasPendientes = $totalFacturas;
} else {
    // Ya se han usado algunas
    $facturasPendientes = max(0, $rango_final - $ultimoNumeroUsado);
}

// Obtener fecha límite
$resultFecha = $insMainModel->getFechaLimiteFactura($empresa_id);
if ($resultFecha->num_rows > 0) {
    $row = $resultFecha->fetch_assoc();
    $contador = (int)$row['dias_transcurridos'];
    $fecha_limite = $row['fecha_limite'];
}

// Devolver datos en formato JSON
$datos = [
    'facturasPendientes' => $facturasPendientes,
    'contador' => $contador,
    'fechaLimite' => $fecha_limite
];

echo json_encode($datos);
exit;