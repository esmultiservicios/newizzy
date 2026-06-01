<?php
// core/planes/obtenerPlan.php

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

if ($planId <= 0) {
    PlanesSyncHelper::responder([
        'success' => false,
        'message' => 'ID de plan no válido.'
    ]);
}

$conexion = null;
$stmt = null;

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    $sql = "
        SELECT
            planes_id,
            nombre,
            estado,
            configuraciones,
            fecha_registro
        FROM planes
        WHERE planes_id = ?
        LIMIT 1
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param('i', $planId);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if (!$resultado || $resultado->num_rows <= 0) {
        PlanesSyncHelper::responder([
            'success' => false,
            'message' => 'Plan no encontrado.'
        ]);
    }

    $row = $resultado->fetch_assoc();
    $configs = [];

    if (!empty($row['configuraciones'])) {
        $configs = json_decode($row['configuraciones'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($configs)) {
            $configs = [];
        }
    }

    $row['planes_id'] = (int)$row['planes_id'];
    $row['estado'] = (int)$row['estado'];
    $row['configuraciones_json'] = $configs;

    PlanesSyncHelper::responder([
        'success' => true,
        'data' => $row
    ]);

} catch (Exception $e) {
    PlanesSyncHelper::responder([
        'success' => false,
        'message' => 'Error en el servidor: ' . $e->getMessage()
    ]);

} finally {
    if ($stmt) {
        $stmt->close();
    }
    if ($conexion) {
        $conexion->close();
    }
}
