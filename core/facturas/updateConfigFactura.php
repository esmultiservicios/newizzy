<?php
// core/facturas/updateConfigFactura.php

header('Content-Type: application/json; charset=utf-8');

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

function responderUpdateConfigFactura($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $mainModel = new mainModel();

    if (empty($_SESSION['users_id_sd'])) {
        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'Sesión no válida.'
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);

        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'Método no permitido.'
        ]);
    }

    $token = isset($_POST['token']) ? trim((string)$_POST['token']) : '';

    if ($token === '') {
        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'Token no recibido.'
        ]);
    }

    if (
        empty($_SESSION['admin_config_token']) ||
        empty($_SESSION['admin_config_token_expira']) ||
        $_SESSION['admin_config_token'] !== $token ||
        time() > (int)$_SESSION['admin_config_token_expira']
    ) {
        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'Debe validar un administrador para guardar cambios.'
        ]);
    }

    $configsJson = isset($_POST['configs']) ? (string)$_POST['configs'] : '';
    $configs = json_decode($configsJson, true);

    if (!is_array($configs) || count($configs) === 0) {
        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'No se recibieron configuraciones para actualizar.'
        ]);
    }

    $permitidos = [1, 2, 3, 4, 5, 6];

    $db = $mainModel->connection();

    if (!$db) {
        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'No se pudo conectar a la base de datos.'
        ]);
    }

    $db->begin_transaction();

    $stmt = $db->prepare("
        UPDATE config
        SET activar = ?
        WHERE config_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        $db->rollback();

        responderUpdateConfigFactura([
            'success' => false,
            'message' => 'No se pudo preparar la actualización.',
            'error' => $db->error
        ]);
    }

    $actualizados = 0;

    foreach ($configs as $item) {
        $configId = isset($item['config_id']) ? (int)$item['config_id'] : 0;
        $activar = isset($item['activar']) ? (int)$item['activar'] : 2;

        if (!in_array($configId, $permitidos, true)) {
            continue;
        }

        $activar = ($activar === 1) ? 1 : 2;

        $stmt->bind_param('ii', $activar, $configId);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            $db->rollback();

            responderUpdateConfigFactura([
                'success' => false,
                'message' => 'No se pudo actualizar la configuración #' . $configId,
                'error' => $error
            ]);
        }

        $actualizados++;
    }

    $stmt->close();
    $db->commit();

    if (isset($_SESSION['admin_config_users_id'])) {
        try {
            $mainModel->guardar_historial_accesos(
                'Actualización de configuración de facturación por usuario administrador ID: ' . (int)$_SESSION['admin_config_users_id']
            );
        } catch (Throwable $e) {
        }
    }

    responderUpdateConfigFactura([
        'success' => true,
        'message' => 'Configuración actualizada correctamente.',
        'actualizados' => $actualizados
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db) {
        try {
            $db->rollback();
        } catch (Throwable $rollbackError) {
        }
    }

    responderUpdateConfigFactura([
        'success' => false,
        'message' => 'Error al actualizar configuración.',
        'error' => $e->getMessage()
    ]);
}