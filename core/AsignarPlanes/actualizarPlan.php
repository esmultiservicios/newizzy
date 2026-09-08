<?php
// core/planes/actualizarPlan.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/PlanesSyncHelper.php';
require_once __DIR__ . '/../correo/NotificationService.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();
    if (!empty($validacion['error'])) {
        PlanesSyncHelper::respuesta('error', 'Sesión inválida', $validacion['mensaje'] ?? 'Sesión inválida');
    }
}

if (!isset($_POST['plan_id']) || !isset($_POST['nombre_plan'])) {
    PlanesSyncHelper::respuesta('error', 'Error', 'Datos incompletos.');
}

$planId = (int)$_POST['plan_id'];
$nombre = trim((string)$_POST['nombre_plan']);
$estado = isset($_POST['estado_plan']) ? PlanesSyncHelper::normalizarEstado($_POST['estado_plan']) : 0;
$configuracionesEntrada = isset($_POST['configuraciones_json']) ? $_POST['configuraciones_json'] : null;
$fechaRegistro = date('Y-m-d H:i:s');

if ($planId <= 0) {
    PlanesSyncHelper::respuesta('error', 'Error', 'ID de plan no válido.');
}

if ($nombre === '') {
    PlanesSyncHelper::respuesta('error', 'Error', 'El nombre del plan es requerido.');
}

$conexionPrincipal = null;
$erroresClientes = [];
$basesSincronizadas = [];
$affectedRows = 0;

try {
    $configuraciones = PlanesSyncHelper::normalizarConfiguraciones($configuracionesEntrada);

    $conexionPrincipal = PlanesSyncHelper::conectarPrincipal($insMainModel);

    if (!$conexionPrincipal) {
        throw new Exception('No se pudo conectar a la base principal.');
    }

    $conexionPrincipal->autocommit(false);

    PlanesSyncHelper::asegurarEstructuraPlanes($conexionPrincipal);

    $planAnterior = null;
    $stAnterior = $conexionPrincipal->prepare('SELECT nombre,estado,configuraciones FROM planes WHERE planes_id=? LIMIT 1');
    if ($stAnterior) { $stAnterior->bind_param('i',$planId); $stAnterior->execute(); $planAnterior=$stAnterior->get_result()->fetch_assoc(); $stAnterior->close(); }

    if (!PlanesSyncHelper::existePlanPorId($conexionPrincipal, $planId)) {
        $conexionPrincipal->rollback();
        PlanesSyncHelper::respuesta('warning', 'Registro no encontrado', 'El plan que intenta actualizar no existe.');
    }

    if (PlanesSyncHelper::existePlanDuplicado($conexionPrincipal, $nombre, $planId)) {
        $conexionPrincipal->rollback();
        PlanesSyncHelper::respuesta('warning', 'Advertencia', 'Ya existe otro plan con ese nombre.');
    }

    $affectedRows = PlanesSyncHelper::actualizarPlan(
        $conexionPrincipal,
        $planId,
        $nombre,
        $estado,
        $configuraciones
    );

    $basesClientes = PlanesSyncHelper::obtenerBasesClientesActivas($conexionPrincipal);

    foreach ($basesClientes as $dbName) {
        $connCliente = null;

        try {
            $connCliente = PlanesSyncHelper::conectarCliente($insMainModel, $dbName);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar a la base {$dbName}.";
                continue;
            }

            $connCliente->autocommit(false);

            PlanesSyncHelper::upsertPlanCliente(
                $connCliente,
                $planId,
                $nombre,
                $estado,
                $fechaRegistro,
                $configuraciones
            );

            $connCliente->commit();
            $basesSincronizadas[] = $dbName;

        } catch (Exception $eCliente) {
            if ($connCliente) {
                $connCliente->rollback();
            }

            $erroresClientes[] = "Error en {$dbName}: " . $eCliente->getMessage();

        } finally {
            if ($connCliente) {
                $connCliente->autocommit(true);
                $connCliente->close();
            }
        }
    }

    $conexionPrincipal->commit();

    try {
        $svc = new NotificationService();
        $cambios = [
            'Nombre' => [
                'anterior' => $planAnterior['nombre'] ?? '',
                'nuevo' => $nombre
            ],
            'Estado' => [
                'anterior' => (int)($planAnterior['estado'] ?? 0) === 1 ? 'Activo' : 'Inactivo',
                'nuevo' => $estado === 1 ? 'Activo' : 'Inactivo'
            ]
        ];
        $detallePlan = ['Plan ID' => $planId, 'Plan' => $nombre];

        foreach ($basesSincronizadas as $dbClienteNotificar) {
            $svc->notifyAdmins(
                $dbClienteNotificar,
                0,
                'IZZY · Plan actualizado',
                'Se actualizó un plan disponible para este cliente.',
                $detallePlan,
                $cambios,
                'billing'
            );
        }

        $svc->notifyAdmins(
            $svc->mainDbName(),
            0,
            'IZZY · Plan actualizado',
            'Se actualizó un plan del catálogo principal y se sincronizó con las bases de clientes disponibles.',
            $detallePlan + [
                'Bases sincronizadas' => count($basesSincronizadas),
                'Advertencias' => count($erroresClientes)
            ],
            $cambios,
            'audit'
        );
    } catch (Throwable $e) {
        error_log('Planes - notificación actualización: ' . $e->getMessage());
    }

    $mensaje = empty($erroresClientes)
        ? 'Plan actualizado correctamente y sincronizado en las bases de clientes activas.'
        : 'Plan actualizado en la base principal, pero algunas bases de clientes no pudieron sincronizarse.';

    PlanesSyncHelper::respuesta('success', 'Éxito', $mensaje, [
        'affected_rows' => $affectedRows,
        'warnings' => $erroresClientes
    ]);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    PlanesSyncHelper::respuesta('error', 'Error', 'Error en el servidor: ' . $e->getMessage());

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}