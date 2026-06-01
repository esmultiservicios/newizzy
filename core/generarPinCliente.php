<?php
// core/generarPinCliente.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

header('Content-Type: application/json');

$insMainModel = new mainModel();

$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    echo json_encode([
        'success' => false,
        'error' => $validacion['mensaje'],
        'redirect' => $validacion['redireccion']
    ]);
    exit;
}

$codigoCliente = isset($_POST['codigoCliente']) ? $insMainModel->cleanString($_POST['codigoCliente']) : '';
$generateNew = isset($_POST['generateNew']) ? $insMainModel->cleanString($_POST['generateNew']) : '0';

$dbActual = $GLOBALS['db'];

if ($dbActual === DB_MAIN) {
    echo json_encode([
        'success' => false,
        'error' => 'La base principal no genera PIN de cliente'
    ]);
    exit;
}

if (empty($codigoCliente) || !is_numeric($codigoCliente)) {
    echo json_encode([
        'success' => false,
        'error' => 'Código de cliente inválido'
    ]);
    exit;
}

try {
    $mysqliMain = $insMainModel->connectionDBLocal(DB_MAIN);
    $mysqliCliente = $insMainModel->connectionDBLocal($dbActual);

    $clienteData = obtenerClienteDesdeMain($mysqliMain, $codigoCliente, $dbActual);

    if (!$clienteData) {
        throw new Exception("El código de cliente no pertenece a esta base de datos");
    }

    $serverCustomersId = (int)$clienteData['server_customers_id'];
    $codigoCliente = (int)$clienteData['codigo_cliente'];

    $pinData = obtenerPinValido($mysqliMain, $serverCustomersId, $codigoCliente);

    if ($generateNew === "1" || !$pinData) {
        $pin = generarPinUnico($mysqliMain, $mysqliCliente);

        $fechaHoraInicio = date("Y-m-d H:i:s");
        $fechaHoraFin = date("Y-m-d H:i:s", strtotime($fechaHoraInicio . " +5 minutes"));

        invalidarPinAnterior($mysqliMain, $serverCustomersId, $codigoCliente);
        invalidarPinAnterior($mysqliCliente, $serverCustomersId, $codigoCliente);

        insertarNuevoPin($mysqliMain, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin);
        insertarNuevoPin($mysqliCliente, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin);

    } else {
        $pin = (int)$pinData['pin'];
        $fechaHoraInicio = $pinData['fecha_hora_inicio'];
        $fechaHoraFin = $pinData['fecha_hora_fin'];

        sincronizarPinEnCliente(
            $mysqliCliente,
            $serverCustomersId,
            $codigoCliente,
            $pin,
            $fechaHoraInicio,
            $fechaHoraFin
        );
    }

    $mysqliMain->close();
    $mysqliCliente->close();

    echo json_encode([
        'success' => true,
        'pin' => $pin,
        'message' => 'PIN generado correctamente',
        'fecha_hora_fin' => $fechaHoraFin
    ]);
    exit;

} catch (Exception $e) {
    error_log("Error en generarPinCliente.php: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}

function obtenerClienteDesdeMain($mysqli, $codigoCliente, $dbActual)
{
    $query = "SELECT 
                    server_customers_id,
                    clientes_id,
                    codigo_cliente,
                    db,
                    planes_id,
                    sistema_id
              FROM server_customers
              WHERE codigo_cliente = ?
              AND db = ?
              AND estado = 1
              LIMIT 1";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al preparar cliente: " . $mysqli->error);
    }

    $stmt->bind_param("is", $codigoCliente, $dbActual);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}

function obtenerPinValido($mysqli, $serverCustomersId, $codigoCliente)
{
    $query = "SELECT 
                    pin,
                    fecha_hora_inicio,
                    fecha_hora_fin
              FROM pin
              WHERE server_customers_id = ?
              AND codigo_cliente = ?
              AND fecha_hora_fin > NOW()
              ORDER BY pin_id DESC
              LIMIT 1";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al preparar PIN válido: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $serverCustomersId, $codigoCliente);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row ?: null;
}

function generarPinUnico($mysqliMain, $mysqliCliente)
{
    $maxIntentos = 50;

    for ($i = 1; $i <= $maxIntentos; $i++) {
        $pin = random_int(100000, 999999);

        if (!pinExisteActivo($mysqliMain, $pin) && !pinExisteActivo($mysqliCliente, $pin)) {
            return $pin;
        }
    }

    throw new Exception("No se pudo generar un PIN único");
}

function pinExisteActivo($mysqli, $pin)
{
    $query = "SELECT pin_id
              FROM pin
              WHERE pin = ?
              AND fecha_hora_fin > NOW()
              LIMIT 1";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al validar PIN único: " . $mysqli->error);
    }

    $stmt->bind_param("i", $pin);
    $stmt->execute();

    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;

    $stmt->close();

    return $existe;
}

function invalidarPinAnterior($mysqli, $serverCustomersId, $codigoCliente)
{
    $fechaActual = date("Y-m-d H:i:s");

    $query = "UPDATE pin
              SET fecha_hora_fin = ?
              WHERE server_customers_id = ?
              AND codigo_cliente = ?
              AND fecha_hora_fin > NOW()";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al invalidar PIN anterior: " . $mysqli->error);
    }

    $stmt->bind_param("sii", $fechaActual, $serverCustomersId, $codigoCliente);

    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar PIN anterior: " . $stmt->error);
    }

    $stmt->close();
}

function insertarNuevoPin($mysqli, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin)
{
    $query = "INSERT INTO pin (
                    server_customers_id,
                    codigo_cliente,
                    pin,
                    fecha_hora_inicio,
                    fecha_hora_fin
              )
              VALUES (?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al preparar inserción de PIN: " . $mysqli->error);
    }

    $stmt->bind_param(
        "iiiss",
        $serverCustomersId,
        $codigoCliente,
        $pin,
        $fechaHoraInicio,
        $fechaHoraFin
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al insertar PIN: " . $stmt->error);
    }

    $stmt->close();
}

function sincronizarPinEnCliente($mysqliCliente, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin)
{
    $query = "SELECT pin_id
              FROM pin
              WHERE server_customers_id = ?
              AND codigo_cliente = ?
              AND pin = ?
              AND fecha_hora_fin > NOW()
              LIMIT 1";

    $stmt = $mysqliCliente->prepare($query);

    if (!$stmt) {
        throw new Exception("Error al preparar sincronización: " . $mysqliCliente->error);
    }

    $stmt->bind_param("iii", $serverCustomersId, $codigoCliente, $pin);
    $stmt->execute();

    $result = $stmt->get_result();
    $existe = $result->num_rows > 0;

    $stmt->close();

    if (!$existe) {
        invalidarPinAnterior($mysqliCliente, $serverCustomersId, $codigoCliente);
        insertarNuevoPin($mysqliCliente, $serverCustomersId, $codigoCliente, $pin, $fechaHoraInicio, $fechaHoraFin);
    }
}