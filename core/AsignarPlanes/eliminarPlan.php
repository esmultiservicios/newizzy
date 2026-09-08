<?php
// core/planes/eliminarPlan.php

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
        PlanesSyncHelper::respuesta('error', 'Sesión inválida', $validacion['mensaje'] ?? 'Sesión inválida', ['estado' => false]);
    }
}

$planId = isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : 0;

if ($planId <= 0) {
    PlanesSyncHelper::respuesta('error', 'Error', 'ID de plan no válido.', ['estado' => false]);
}

$conexionPrincipal = null;
$erroresClientes = [];
$basesSincronizadas = [];

try {
    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        throw new Exception('No se pudo conectar a la base principal.');
    }

    $conexionPrincipal->autocommit(false);

    $planEliminar = null;
    $stPlanEliminar=$conexionPrincipal->prepare('SELECT nombre,estado FROM planes WHERE planes_id=? LIMIT 1');
    if($stPlanEliminar){$stPlanEliminar->bind_param('i',$planId);$stPlanEliminar->execute();$planEliminar=$stPlanEliminar->get_result()->fetch_assoc();$stPlanEliminar->close();}

    if (!PlanesSyncHelper::existePlanPorId($conexionPrincipal, $planId)) {
        $conexionPrincipal->rollback();
        PlanesSyncHelper::respuesta('warning', 'Registro no encontrado', 'El plan que intenta eliminar no existe.', ['estado' => false]);
    }

    $basesClientes = PlanesSyncHelper::obtenerBasesClientesActivas($conexionPrincipal);

    PlanesSyncHelper::eliminarPlan($conexionPrincipal, $planId);

    foreach ($basesClientes as $dbName) {
        try {
            $connCliente = PlanesSyncHelper::conectarCliente($insMainModel, $dbName);

            if (!$connCliente) {
                $erroresClientes[] = "No se pudo conectar o validar la base {$dbName}.";
                continue;
            }

            try {
                $connCliente->autocommit(false);

                if (PlanesSyncHelper::tablaExiste($connCliente, 'planes')) {
                    PlanesSyncHelper::eliminarPlan($connCliente, $planId);
                }

                $connCliente->commit();
                $basesSincronizadas[] = $dbName;
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

    try {
        $svc = new NotificationService();
        $detallePlan = [
            'Plan ID' => $planId,
            'Plan' => $planEliminar['nombre'] ?? ('Plan #' . $planId)
        ];

        foreach ($basesSincronizadas as $dbClienteNotificar) {
            $svc->notifyAdmins(
                $dbClienteNotificar,
                0,
                'IZZY · Plan eliminado',
                'Se eliminó un plan del catálogo disponible para este cliente.',
                $detallePlan,
                [],
                'warning'
            );
        }

        $svc->notifyAdmins(
            $svc->mainDbName(),
            0,
            'IZZY · Plan eliminado',
            'Se eliminó un plan del catálogo principal y de las bases de clientes sincronizadas.',
            $detallePlan + [
                'Bases sincronizadas' => count($basesSincronizadas),
                'Advertencias' => count($erroresClientes)
            ],
            [],
            'audit'
        );
    } catch (Throwable $e) {
        error_log('Planes - notificación eliminación: ' . $e->getMessage());
    }

    $mensaje = empty($erroresClientes)
        ? 'Plan eliminado correctamente.'
        : 'Plan eliminado en la base principal. Algunas bases de clientes no pudieron sincronizarse.';

    PlanesSyncHelper::respuesta('success', 'Éxito', $mensaje, [
        'estado' => true,
        'warnings' => $erroresClientes
    ]);

} catch (Exception $e) {
    if ($conexionPrincipal) {
        $conexionPrincipal->rollback();
    }

    PlanesSyncHelper::respuesta('error', 'Error', 'Error en el servidor: ' . $e->getMessage(), ['estado' => false]);

} finally {
    if ($conexionPrincipal) {
        $conexionPrincipal->autocommit(true);
        $conexionPrincipal->close();
    }
}
