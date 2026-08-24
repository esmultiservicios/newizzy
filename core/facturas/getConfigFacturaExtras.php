<?php
// core/facturas/getConfigFacturaExtras.php
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

    $conexion = mainModel::staticConnection();

    $config = [
        '7' => 1,
        '8' => 2
    ];

    $stmt = $conexion->prepare(
        "SELECT config_id, activar
         FROM config
         WHERE config_id IN (7,8)
         ORDER BY config_id"
    );

    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta de configuración.');
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $id = (int)($fila['config_id'] ?? 0);
        $activar = (int)($fila['activar'] ?? 2);

        if ($id === 7 || $id === 8) {
            $config[(string)$id] = $activar === 1 ? 1 : 2;
        }
    }

    $stmt->close();
    $conexion->close();

    echo json_encode([
        'success' => true,
        'config' => $config,
        'convertir_proforma_pagada_factura' => $config['7'],
        'seguridad_descuento_precio' => $config['8']
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'No se pudo consultar la configuración adicional.'
    ], JSON_UNESCAPED_UNICODE);
}
