<?php
// core/planes/asignarSubmenu2Plan.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/PlanesSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

$planId = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
$submenu1Id = isset($_POST['submenu1_id']) ? (int)$_POST['submenu1_id'] : 0;
$estado = isset($_POST['estado']) ? PlanesSyncHelper::normalizarEstado($_POST['estado']) : 0;

if ($planId <= 0 || $submenu1Id <= 0) {
    PlanesSyncHelper::respuesta('error', 'Error en la operación', 'Faltan parámetros requeridos.', ['estado' => false]);
}

$conexionPrincipal = null;
$erroresClientes = [];

try {
    $conexionPrincipal = $mainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception('No se pudo conectar a la base principal.');
    }

    $conexionPrincipal->autocommit(false);

    PlanesSyncHelper::upsertAsignacion($conexionPrincipal, 'submenu1_plan', 'submenu1_plan_id', 'submenu1_id', $planId, $submenu1Id, $estado);

    $basesClientes = PlanesSyncHelper::obtenerBasesClientesActivas($conexionPrincipal, $planId, true);

    foreach ($basesClientes as $dbName) {
        try {
            $connCliente = PlanesSyncHelper::conectarCliente($mainModel, $dbName);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar o validar la base {$dbName}.";
                continue;
            }

            try {
                $connCliente->autocommit(false);
                PlanesSyncHelper::upsertAsignacion($connCliente, 'submenu1_plan', 'submenu1_plan_id', 'submenu1_id', $planId, $submenu1Id, $estado);
                $connCliente->commit();
            } catch (Exception $eCliente) {
                $connCliente->rollback();
                $erroresClientes[] = "Error en {$dbName}: " . $eCliente->getMessage();
            } finally {
                $connCliente->autocommit(true);
                $connCliente->close();
            }
        } catch (Exception $eClienteGeneral) {
            $erroresClientes[] = "Error en {$dbName}: " . $eClienteGeneral->getMessage();
        }
    }

    $conexionPrincipal->commit();

    $mensaje = empty($erroresClientes)
        ? 'El submenú nivel 2 se ha actualizado correctamente.'
        : 'El submenú nivel 2 se actualizó en la base principal. Algunas bases de clientes no pudieron sincronizarse.';

    PlanesSyncHelper::respuesta('success', 'Operación exitosa', $mensaje, [
        'estado' => true,
        'warnings' => $erroresClientes
    ]);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    PlanesSyncHelper::respuesta('error', 'Error en la operación', 'Hubo un problema al procesar la solicitud: ' . $e->getMessage(), ['estado' => false]);

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}
