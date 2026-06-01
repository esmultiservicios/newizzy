<?php
// core/planes/eliminarConfiguracionPlan.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/PlanesSyncHelper.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();
    if (!empty($validacion['error'])) {
        PlanesSyncHelper::responder([
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
    }
}

$planId = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;
$clave = isset($_POST['clave']) ? trim((string)$_POST['clave']) : '';

if ($planId <= 0 || $clave === '') {
    PlanesSyncHelper::responder([
        'success' => false,
        'message' => 'Datos incompletos.'
    ]);
}

$conexionPrincipal = null;
$erroresClientes = [];

try {
    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception('No se pudo conectar a la base principal.');
    }

    $conexionPrincipal->autocommit(false);

    $configActual = PlanesSyncHelper::obtenerConfiguracionesPlan($conexionPrincipal, $planId);

    if ($configActual === null && !PlanesSyncHelper::existePlanPorId($conexionPrincipal, $planId)) {
        $conexionPrincipal->rollback();
        PlanesSyncHelper::responder([
            'success' => false,
            'message' => 'Plan no encontrado.'
        ]);
    }

    $configuraciones = [];

    if (!empty($configActual)) {
        $configuraciones = json_decode($configActual, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($configuraciones)) {
            $configuraciones = [];
        }
    }

    if (!array_key_exists($clave, $configuraciones)) {
        $conexionPrincipal->rollback();
        PlanesSyncHelper::responder([
            'success' => false,
            'message' => 'La configuración no existe en este plan.'
        ]);
    }

    unset($configuraciones[$clave]);
    $nuevasConfiguraciones = empty($configuraciones) ? null : json_encode($configuraciones, JSON_UNESCAPED_UNICODE);

    PlanesSyncHelper::actualizarConfiguracionesPlan($conexionPrincipal, $planId, $nuevasConfiguraciones);

    $basesClientes = PlanesSyncHelper::obtenerBasesClientesActivas($conexionPrincipal);

    foreach ($basesClientes as $dbName) {
        try {
            $connCliente = PlanesSyncHelper::conectarCliente($insMainModel, $dbName);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar o validar la base {$dbName}.";
                continue;
            }

            try {
                $connCliente->autocommit(false);

                if (PlanesSyncHelper::tablaExiste($connCliente, 'planes') && PlanesSyncHelper::existePlanPorId($connCliente, $planId)) {
                    PlanesSyncHelper::actualizarConfiguracionesPlan($connCliente, $planId, $nuevasConfiguraciones);
                }

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

    PlanesSyncHelper::responder([
        'success' => true,
        'message' => 'Configuración eliminada correctamente.',
        'configuraciones' => $configuraciones,
        'warnings' => $erroresClientes
    ]);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    PlanesSyncHelper::responder([
        'success' => false,
        'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}
