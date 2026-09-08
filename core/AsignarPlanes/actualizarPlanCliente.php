<?php
// core/AsignarPlanes/actualizarPlanCliente.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/AsignarPlanesSyncHelper.php';
require_once __DIR__ . '/../correo/sendEmail.php';
require_once __DIR__ . '/../correo/NotificationService.php';

header('Content-Type: application/json; charset=utf-8');


function apPlanEscapeHtml($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function apPlanObtenerDatosNotificacion(mysqli $conexionPrincipal, $clienteId, $planAnteriorId, $planNuevoId) {
    $datos = [
        'cliente_nombre' => '',
        'cliente_correo' => '',
        'plan_anterior' => 'Sin plan',
        'plan_nuevo' => 'Sin plan',
        'plan_nuevo_config' => []
    ];

    $stmtCliente = $conexionPrincipal->prepare("SELECT nombre, correo FROM clientes WHERE clientes_id = ? LIMIT 1");
    if ($stmtCliente) {
        $stmtCliente->bind_param("i", $clienteId);
        $stmtCliente->execute();
        $rowCliente = $stmtCliente->get_result()->fetch_assoc();
        $stmtCliente->close();

        if ($rowCliente) {
            $datos['cliente_nombre'] = trim((string)($rowCliente['nombre'] ?? ''));
            $datos['cliente_correo'] = trim((string)($rowCliente['correo'] ?? ''));
        }
    }

    $ids = array_values(array_unique(array_filter([(int)$planAnteriorId, (int)$planNuevoId])));
    foreach ($ids as $planIdConsulta) {
        $stmtPlan = $conexionPrincipal->prepare("SELECT planes_id, nombre, configuraciones FROM planes WHERE planes_id = ? LIMIT 1");
        if (!$stmtPlan) {
            continue;
        }

        $stmtPlan->bind_param("i", $planIdConsulta);
        $stmtPlan->execute();
        $rowPlan = $stmtPlan->get_result()->fetch_assoc();
        $stmtPlan->close();

        if (!$rowPlan) {
            continue;
        }

        if ((int)$rowPlan['planes_id'] === (int)$planAnteriorId) {
            $datos['plan_anterior'] = trim((string)$rowPlan['nombre']);
        }

        if ((int)$rowPlan['planes_id'] === (int)$planNuevoId) {
            $datos['plan_nuevo'] = trim((string)$rowPlan['nombre']);

            $config = json_decode((string)($rowPlan['configuraciones'] ?? ''), true);
            if (is_array($config)) {
                $datos['plan_nuevo_config'] = $config;
            }
        }
    }

    return $datos;
}

function apPlanEnviarCorreoActualizacion($datosCorreo, $userExtra, $validar, $estado, $dbCliente) {
    $resultado = [
        'enviado' => false,
        'cliente_enviado' => false,
        'principal_enviado' => false,
        'mensaje' => 'El plan fue actualizado, pero no se pudo confirmar el envío de las notificaciones.'
    ];

    $clienteNombre = trim((string)($datosCorreo['cliente_nombre'] ?? 'Cliente IZZY'));
    $planAnterior = trim((string)($datosCorreo['plan_anterior'] ?? 'Sin plan'));
    $planNuevo = trim((string)($datosCorreo['plan_nuevo'] ?? 'Sin plan'));
    $config = is_array($datosCorreo['plan_nuevo_config'] ?? null) ? $datosCorreo['plan_nuevo_config'] : [];
    $usuariosBase = isset($config['usuarios']) && is_numeric($config['usuarios']) ? max(0, (int)$config['usuarios']) : 0;
    $empresasBase = isset($config['perfiles']) && is_numeric($config['perfiles']) ? max(0, (int)$config['perfiles']) : 0;
    $usuariosExtra = max(0, (int)$userExtra);
    $usuariosTotal = $usuariosBase + $usuariosExtra;
    $validarTexto = ((int)$validar === 1) ? 'Sí' : 'No';
    $estadoTexto = ((int)$estado === 1) ? 'Activo' : 'Inactivo';

    try {
        $service = new NotificationService();
        $cambios = [
            'Plan' => ['anterior'=>$planAnterior, 'nuevo'=>$planNuevo]
        ];
        $detalles = [
            'Usuarios incluidos' => $usuariosBase,
            'Usuarios extra' => $usuariosExtra,
            'Usuarios disponibles' => $usuariosTotal,
            'Empresas / puntos de venta' => $empresasBase,
            'Validación' => $validarTexto,
            'Estado' => $estadoTexto,
            'Fecha del cambio' => date('d/m/Y h:i a')
        ];

        $envios = $service->notifyClientAndMain(
            $dbCliente,
            0,
            $clienteNombre,
            'IZZY · Plan actualizado',
            'Se actualizó el plan asociado al cliente.',
            $detalles,
            $cambios,
            'IZZY · Auditoría · Cambio de plan',
            'Se actualizó el plan de un cliente IZZY.',
            $detalles,
            $cambios,
            'billing'
        );

        $resultado['cliente_enviado'] = !empty($envios['client']['sent']);
        $resultado['principal_enviado'] = !empty($envios['main']['sent']);
        $resultado['enviado'] = $resultado['cliente_enviado'] || $resultado['principal_enviado'];

        if ($resultado['cliente_enviado'] && $resultado['principal_enviado']) {
            $resultado['mensaje'] = 'El plan fue actualizado y se notificó a los correos configurados del cliente y de la base principal.';
        } elseif ($resultado['cliente_enviado']) {
            $resultado['mensaje'] = 'El plan fue actualizado y se notificó al cliente, pero no se pudo confirmar la notificación administrativa principal.';
        } elseif ($resultado['principal_enviado']) {
            $resultado['mensaje'] = 'El plan fue actualizado y se notificó a la base principal, pero no se pudo confirmar la notificación del cliente.';
        }
    } catch (Throwable $e) {
        error_log('Asignación de Planes - error enviando notificaciones: '.$e->getMessage());
    }

    return $resultado;
}

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

    $planAnteriorId = isset($serverCustomer['planes_id']) ? (int)$serverCustomer['planes_id'] : 0;
    $datosNotificacion = apPlanObtenerDatosNotificacion(
        $conexionPrincipal,
        $clienteId,
        $planAnteriorId,
        $planesId
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

    /*
     * IMPORTANTE:
     * menu_plan, submenu_plan y submenu1_plan históricamente son MyISAM.
     * MyISAM no revierte cambios con rollback().
     *
     * Por seguridad se prepara primero el catálogo y los permisos del nuevo
     * plan. Solo cuando esa sincronización termina correctamente se cambia el
     * plan activo del cliente.
     */

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

    AsignarPlanesSyncHelper::actualizarPlanTablaCliente(
        $conexionCliente,
        $planesId,
        $userExtra
    );

    AsignarPlanesSyncHelper::verificarPlanTablaClienteActualizado(
        $conexionCliente,
        $planesId,
        $userExtra
    );

    AsignarPlanesSyncHelper::actualizarServerCustomer(
        $conexionCliente,
        $serverCustomersId,
        $clienteId,
        $planesId,
        $validar,
        $estado
    );

    /*
     * La base principal se actualiza al final.
     * Si algo falla antes, el cliente continúa apuntando al plan anterior.
     */
    AsignarPlanesSyncHelper::actualizarServerCustomer(
        $conexionPrincipal,
        $serverCustomersId,
        $clienteId,
        $planesId,
        $validar,
        $estado,
        true
    );

    $conexionCliente->commit();
    $conexionPrincipal->commit();

    /*
     * El correo se envía únicamente después de confirmar ambos commits.
     * Si el correo falla, NO se revierte el cambio de plan ya confirmado.
     */
    $resultadoCorreo = apPlanEnviarCorreoActualizacion(
        $datosNotificacion,
        $userExtra,
        $validar,
        $estado,
        $dbName
    );

    AsignarPlanesSyncHelper::responder([
        'success' => true,
        'type' => !empty($resultadoCorreo['enviado']) ? 'success' : 'warning',
        'title' => !empty($resultadoCorreo['enviado']) ? 'Plan actualizado y notificado' : 'Plan actualizado',
        'message' => $resultadoCorreo['mensaje'],
        'server_customers_id' => $serverCustomersId,
        'cliente_id' => $clienteId,
        'planes_id' => $planesId,
        'user_extra' => $userExtra,
        'validar' => $validar,
        'estado' => $estado,
        'db_cliente' => $dbName,
        'correo_enviado' => !empty($resultadoCorreo['enviado']),
        'correo_cliente_notificado' => !empty($resultadoCorreo['cliente_enviado']),
        'correo_principal_notificado' => !empty($resultadoCorreo['principal_enviado'])
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
        'message' => 'No se pudo completar el cambio de plan. Detalle: ' . $e->getMessage()
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