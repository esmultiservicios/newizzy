<?php
// core/AsignarPlanes/actualizarPlanCliente.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/AsignarPlanesSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

if (method_exists($mainModel, 'validarSesion')) {
    $validacion = $mainModel->validarSesion();

    if (!empty($validacion['error'])) {
        AsignarPlanesSyncHelper::responder([
            'success' => false,
            'type' => 'error',
            'title' => 'Sesión inválida',
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
    }
}

$serverCustomersId = isset($_POST['server_customers_id']) ? (int)$_POST['server_customers_id'] : 0;
$clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
$planesId = isset($_POST['planes_id']) ? (int)$_POST['planes_id'] : 0;
$userExtra = isset($_POST['user_extra']) ? (int)$_POST['user_extra'] : 0;
$validar = isset($_POST['validar']) ? (int)$_POST['validar'] : 1;
$estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;

if ($serverCustomersId <= 0) {
    AsignarPlanesSyncHelper::responder([
        'success' => false,
        'type' => 'error',
        'title' => 'Error',
        'message' => 'No se recibió el ID de server_customers.'
    ]);
}

if ($clienteId <= 0) {
    AsignarPlanesSyncHelper::responder([
        'success' => false,
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Debe seleccionar un cliente válido.'
    ]);
}

if ($planesId <= 0) {
    AsignarPlanesSyncHelper::responder([
        'success' => false,
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Debe seleccionar un plan válido.'
    ]);
}

if ($userExtra < 0) {
    $userExtra = 0;
}

if ($validar !== 1 && $validar !== 2) {
    $validar = 1;
}

if ($estado !== 1) {
    $estado = 0;
}

$conexionPrincipal = null;
$conexionCliente = null;

try {
    $conexionPrincipal = AsignarPlanesSyncHelper::conectarPrincipal($mainModel);

    if (!$conexionPrincipal) {
        throw new Exception("No se pudo conectar a la base principal.");
    }

    $conexionPrincipal->autocommit(false);

    if (!AsignarPlanesSyncHelper::validarPlanActivoPrincipal($conexionPrincipal, $planesId)) {
        throw new Exception("El plan seleccionado no existe o está inactivo.");
    }

    $serverCustomer = AsignarPlanesSyncHelper::obtenerServerCustomer(
        $conexionPrincipal,
        $serverCustomersId,
        $clienteId
    );

    $dbName = trim((string)$serverCustomer['db']);

    if ($dbName === "") {
        throw new Exception("El cliente no tiene una base de datos registrada.");
    }

    if (!AsignarPlanesSyncHelper::validarDbName($dbName)) {
        throw new Exception("El nombre de la base de datos del cliente no es válido.");
    }

    $conexionCliente = AsignarPlanesSyncHelper::conectarCliente($mainModel, $dbName);

    if (!$conexionCliente) {
        throw new Exception("No se pudo conectar a la base de datos del cliente.");
    }

    $conexionCliente->autocommit(false);

    AsignarPlanesSyncHelper::actualizarServerCustomer(
        $conexionPrincipal,
        $serverCustomersId,
        $clienteId,
        $planesId,
        $validar,
        $estado
    );

    AsignarPlanesSyncHelper::actualizarServerCustomer(
        $conexionCliente,
        $serverCustomersId,
        $clienteId,
        $planesId,
        $validar,
        $estado
    );

    AsignarPlanesSyncHelper::actualizarPlanTablaCliente(
        $conexionCliente,
        $planesId,
        $userExtra
    );

    AsignarPlanesSyncHelper::sincronizarPlanCatalogoCliente(
        $conexionPrincipal,
        $conexionCliente,
        $planesId
    );

    AsignarPlanesSyncHelper::copiarAsignacionesPlanCliente(
        $conexionPrincipal,
        $conexionCliente,
        $planesId
    );

    $conexionCliente->commit();
    $conexionPrincipal->commit();

    AsignarPlanesSyncHelper::responder([
        'success' => true,
        'type' => 'success',
        'title' => 'Plan actualizado',
        'message' => 'El plan fue actualizado correctamente en la base principal y en la base del cliente.',
        'server_customers_id' => $serverCustomersId,
        'cliente_id' => $clienteId,
        'planes_id' => $planesId,
        'user_extra' => $userExtra,
        'validar' => $validar,
        'estado' => $estado,
        'db_cliente' => $dbName
    ]);

} catch (Throwable $e) {
    if ($conexionCliente) {
        $conexionCliente->rollback();
    }

    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    AsignarPlanesSyncHelper::responder([
        'success' => false,
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error al actualizar plan: ' . $e->getMessage()
    ]);

} finally {
    if ($conexionCliente) {
        $conexionCliente->autocommit(true);
        $conexionCliente->close();
    }

    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}