<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

$empresa_id = $_SESSION['empresa_id_sd'];

$insMainModel = new mainModel();

$resultNumero = $insMainModel->getTotalFacturasDisponiblesDB($empresa_id);

$ultimoNumeroUsado = 0;
$numeroMaximo = 0;
$rango_inicial = 0; // Nuevo: para capturar el rango inicial
$contador = 0;
$fecha_limite = 'Sin definir';

// Verificamos si hay registros para el total de facturas disponibles
if ($resultNumero->num_rows > 0) {
    $consultaNumero = $resultNumero->fetch_assoc();
    $ultimoNumeroUsado = $consultaNumero['numero'];
}

// Obtenemos el número máximo permitido y el rango inicial
$resultNumeroMaximo = $insMainModel->getNumeroMaximoPermitido($empresa_id);

if ($resultNumeroMaximo->num_rows > 0) {
    $consultaNumeroMaximo = $resultNumeroMaximo->fetch_assoc();
    $numeroMaximo = $consultaNumeroMaximo['numero'];
    $rango_inicial = $consultaNumeroMaximo['rango_inicial']; // Asumiendo que existe este campo
}

// Cálculo CORREGIDO para cualquier rango
if ($ultimoNumeroUsado == 0 || $ultimoNumeroUsado == $rango_inicial) {
    // Caso 1: No se ha usado ninguna factura
    // Caso 2: Estamos en el primer número del rango (ej. 00000001 de 00000001-00000050)
    $facturasPendientes = $numeroMaximo - $rango_inicial + 1;
} else {
    // Caso normal: cálculo estándar
    $facturasPendientes = $numeroMaximo - $ultimoNumeroUsado;
}

// Aseguramos que no sea negativo
$facturasPendientes = max(0, $facturasPendientes);

// OBTENER LA FECHA LIMITE DE FACTURACION
$resultNFechaLimite = $insMainModel->getFechaLimiteFactura($empresa_id);

if ($resultNFechaLimite->num_rows > 0) {
    $consultaFechaLimite = $resultNFechaLimite->fetch_assoc();
    $contador = $consultaFechaLimite['dias_transcurridos'];
    $fecha_limite = $consultaFechaLimite['fecha_limite'];
}

$datos = array(
    'facturasPendientes' => $facturasPendientes,
    'contador' => $contador,
    'fechaLimite' => $fecha_limite
);

echo json_encode($datos);