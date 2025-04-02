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

$numeroAnterior = 0;
$numeroMaximo = 0;
$contador = 0;
$fecha_limite = 'Sin definir';  // Asignamos un valor predeterminado para fecha_limite

// Verificamos si hay registros para el total de facturas disponibles
if ($resultNumero->num_rows > 0) {
	$consultaNumero = $resultNumero->fetch_assoc();
	$numeroAnterior = $consultaNumero['numero'];
}

// Obtenemos el número máximo permitido
$resultNumeroMaximo = $insMainModel->getNumeroMaximoPermitido($empresa_id);

if ($resultNumeroMaximo->num_rows > 0) {
	$consultaNumeroMaximo = $resultNumeroMaximo->fetch_assoc();
	$numeroMaximo = $consultaNumeroMaximo['numero'];
}

// Calculamos las facturas pendientes
$facturasPendientes = intval($numeroMaximo) - intval($numeroAnterior);

// OBTENER LA FECHA LIMITE DE FACTURACION
$resultNFechaLimite = $insMainModel->getFechaLimiteFactura($empresa_id);

// Si no hay un resultado válido para la fecha límite, dejamos "Sin definir"
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

// Devolvemos los datos en formato JSON
echo json_encode($datos);
