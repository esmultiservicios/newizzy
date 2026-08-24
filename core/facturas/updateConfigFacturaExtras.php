<?php
// core/facturas/updateConfigFacturaExtras.php
declare(strict_types=1);

$peticionAjax = true;
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_once dirname(__DIR__) . '/mainModel.php';

    $sesion = mainModel::validarSesion();

    if (is_array($sesion) && !empty($sesion['error'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => $sesion['mensaje'] ?? 'Sesión no válida.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $token = trim((string)($_POST['token'] ?? ''));
    $configId = (int)($_POST['config_id'] ?? 0);
    $activar = (int)($_POST['activar'] ?? 2);

    if ($token === '') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Se requiere validación administrativa para modificar esta configuración.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($configId !== 8) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Configuración no permitida en este endpoint.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $activar = $activar === 1 ? 1 : 2;

    $conexion = mainModel::staticConnection();
    $conexion->begin_transaction();

    try {
        // ID 8 debe existir por el SQL de instalación.
        // Si por algún motivo no existe, se crea con el nombre corto
        // compatible con la longitud actual de la columna accion.
        $stmt = $conexion->prepare(
            "INSERT INTO config (config_id, accion, activar)
             VALUES (8, 'Clave Descuento y Precio', ?)
             ON DUPLICATE KEY UPDATE activar = VALUES(activar)"
        );

        if (!$stmt) {
            throw new RuntimeException('No se pudo preparar la actualización.');
        }

        $stmt->bind_param('i', $activar);
        $stmt->execute();
        $stmt->close();

        // Verificación inmediata en la misma conexión/transacción.
        $stmtCheck = $conexion->prepare(
            "SELECT activar
             FROM config
             WHERE config_id = 8
             LIMIT 1"
        );

        if (!$stmtCheck) {
            throw new RuntimeException('No se pudo verificar la actualización.');
        }

        $stmtCheck->execute();
        $resultado = $stmtCheck->get_result();
        $fila = $resultado->fetch_assoc();
        $stmtCheck->close();

        $guardado = isset($fila['activar']) ? (int)$fila['activar'] : 0;

        if ($guardado !== $activar) {
            throw new RuntimeException('El valor no pudo verificarse después de guardar.');
        }

        $conexion->commit();
        $conexion->close();

        echo json_encode([
            'success' => true,
            'message' => 'Seguridad de descuentos y precios actualizada correctamente.',
            'config_id' => 8,
            'activar' => $guardado
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        $conexion->close();
        throw $e;
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo guardar la seguridad de descuentos y precios.'
    ], JSON_UNESCAPED_UNICODE);
}
