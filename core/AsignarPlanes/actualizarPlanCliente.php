<?php
// core/AsignarPlanes/actualizarPlanCliente.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';
require_once __DIR__ . '/AsignarPlanesSyncHelper.php';
require_once __DIR__ . '/../correo/sendEmail.php';

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

function apPlanEnviarCorreoActualizacion($datosCorreo, $userExtra, $validar, $estado) {
    $resultado = [
        'enviado' => false,
        'mensaje' => 'No se envió correo de notificación.'
    ];

    $correoCliente = trim((string)($datosCorreo['cliente_correo'] ?? ''));
    if ($correoCliente === '' || !filter_var($correoCliente, FILTER_VALIDATE_EMAIL)) {
        $resultado['mensaje'] = 'El plan fue actualizado, pero el cliente no tiene un correo válido registrado.';
        return $resultado;
    }

    $empresaIdCorreo = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
    if ($empresaIdCorreo <= 0) {
        $resultado['mensaje'] = 'El plan fue actualizado, pero no se pudo identificar la empresa emisora para enviar el correo.';
        return $resultado;
    }

    $clienteNombre = trim((string)($datosCorreo['cliente_nombre'] ?? 'Cliente'));
    if ($clienteNombre === '') {
        $clienteNombre = 'Cliente';
    }

    $planAnterior = trim((string)($datosCorreo['plan_anterior'] ?? 'Sin plan'));
    $planNuevo = trim((string)($datosCorreo['plan_nuevo'] ?? 'Sin plan'));
    $config = is_array($datosCorreo['plan_nuevo_config'] ?? null) ? $datosCorreo['plan_nuevo_config'] : [];

    $usuariosBase = isset($config['usuarios']) ? max(0, (int)$config['usuarios']) : 0;
    $empresasBase = isset($config['perfiles']) ? max(0, (int)$config['perfiles']) : 0;
    $usuariosExtra = max(0, (int)$userExtra);
    $usuariosTotal = $usuariosBase + $usuariosExtra;

    $validarTexto = ((int)$validar === 1) ? 'Sí' : 'No';
    $estadoTexto = ((int)$estado === 1) ? 'Activo' : 'Inactivo';
    $fecha = date('d/m/Y H:i');

    $asunto = 'Actualización de plan IZZY - ' . $planNuevo;

    $mensaje = '
        <div style="padding:20px;font-family:Arial,Helvetica,sans-serif;color:#172B4D;">
            <div style="border-bottom:3px solid #0EA5A8;padding-bottom:12px;margin-bottom:18px;">
                <h2 style="margin:0;color:#17324D;font-size:22px;">Actualización de plan</h2>
                <p style="margin:6px 0 0;color:#6B778C;">Su configuración de IZZY fue actualizada correctamente.</p>
            </div>
            <p>Hola <strong>' . apPlanEscapeHtml($clienteNombre) . '</strong>,</p>
            <p>Le informamos que se realizó una actualización en el plan asociado a su cuenta.</p>
            <table cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;margin:18px 0;border:1px solid #DDE3EA;">
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;width:42%;">Plan anterior</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . apPlanEscapeHtml($planAnterior) . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Plan nuevo</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;"><strong>' . apPlanEscapeHtml($planNuevo) . '</strong></td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Usuarios incluidos</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . $usuariosBase . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Usuarios extra</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . $usuariosExtra . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Usuarios disponibles</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;"><strong>' . $usuariosTotal . '</strong></td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Empresas / puntos de venta</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . $empresasBase . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Validación</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . apPlanEscapeHtml($validarTexto) . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;border-bottom:1px solid #DDE3EA;font-weight:700;">Estado</td><td style="padding:11px 14px;border-bottom:1px solid #DDE3EA;">' . apPlanEscapeHtml($estadoTexto) . '</td></tr>
                <tr><td style="padding:11px 14px;background:#F7F9FC;font-weight:700;">Fecha del cambio</td><td style="padding:11px 14px;">' . apPlanEscapeHtml($fecha) . '</td></tr>
            </table>
            <div style="padding:12px 14px;background:#F7F9FC;border-left:4px solid #2F9DDD;margin:18px 0;color:#44546A;">Si tiene alguna consulta sobre este cambio, puede comunicarse con nuestro equipo de soporte.</div>
            <p style="margin-top:24px;">Atentamente,<br><strong>Equipo IZZY</strong></p>
        </div>
    ';

    try {
        $sendEmail = new sendEmail();

        /*
         * sendEmail devuelve 1/0 y algunas ramas imprimen el detalle del error.
         * Capturamos esa salida para no contaminar el JSON del endpoint.
         */
        ob_start();
        $envio = $sendEmail->enviarCorreo(
            [$correoCliente => $clienteNombre],
            [],
            $asunto,
            $mensaje,
            1,
            $empresaIdCorreo,
            []
        );
        $detalleEnvio = trim((string)ob_get_clean());

        if ((int)$envio === 1) {
            $resultado['enviado'] = true;
            $resultado['mensaje'] = 'El plan fue actualizado y el correo de notificación fue enviado al cliente.';
        } else {
            if ($detalleEnvio !== '') {
                error_log('Asignación de Planes - correo no enviado: ' . strip_tags($detalleEnvio));
            }
            $resultado['mensaje'] = 'El plan fue actualizado correctamente, pero no se pudo enviar el correo al cliente.';
        }
    } catch (Throwable $e) {
        if (ob_get_level() > 0) {
            @ob_end_clean();
        }
        error_log('Asignación de Planes - error enviando correo de actualización: ' . $e->getMessage());
        $resultado['mensaje'] = 'El plan fue actualizado correctamente, pero no se pudo enviar el correo al cliente.';
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
        $estado
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
        'correo_destino' => $datosNotificacion['cliente_correo'] ?? ''
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