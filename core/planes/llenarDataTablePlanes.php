<?php
// core/planes/llenarDataTablePlanes.php

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
            'data' => [],
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
    }
}

$conexion = null;
$stmt = null;
$data = [];

try {
    $conexion = $insMainModel->connection();

    if (!$conexion) {
        throw new Exception('No se pudo conectar a la base de datos.');
    }

    $sql = "
        SELECT
            p.planes_id,
            p.nombre,
            p.estado,
            p.configuraciones,
            COALESCE(mp.total, 0) AS menus_asignados,
            COALESCE(sp.total, 0) AS submenus_asignados,
            COALESCE(s1p.total, 0) AS submenus2_asignados
        FROM planes p
        LEFT JOIN (
            SELECT planes_id, COUNT(*) AS total
            FROM menu_plan
            WHERE estado = 1
            GROUP BY planes_id
        ) mp ON p.planes_id = mp.planes_id
        LEFT JOIN (
            SELECT planes_id, COUNT(*) AS total
            FROM submenu_plan
            WHERE estado = 1
            GROUP BY planes_id
        ) sp ON p.planes_id = sp.planes_id
        LEFT JOIN (
            SELECT planes_id, COUNT(*) AS total
            FROM submenu1_plan
            WHERE estado = 1
            GROUP BY planes_id
        ) s1p ON p.planes_id = s1p.planes_id
        ORDER BY p.nombre ASC
    ";

    $stmt = $conexion->prepare($sql);

    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta: ' . $conexion->error);
    }

    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado) {
        while ($row = $resultado->fetch_assoc()) {
            $configDisplay = 'Sin configuraciones';
            $configsArray = [];

            if (!empty($row['configuraciones'])) {
                $decoded = json_decode($row['configuraciones'], true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
                    $configsArray = $decoded;
                    $configDisplay = "<ul class='list-unstyled mb-0'>";

                    foreach ($configsArray as $key => $value) {
                        $keySafe = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
                        $valueSafe = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                        $configDisplay .= "<li><strong>{$keySafe}:</strong> {$valueSafe}</li>";
                    }

                    $configDisplay .= '</ul>';
                }
            }

            $data[] = [
                'planes_id' => (int)$row['planes_id'],
                'nombre' => htmlspecialchars((string)$row['nombre'], ENT_QUOTES, 'UTF-8'),
                'estado' => (int)$row['estado'],
                'configuraciones' => $configDisplay,
                'configuraciones_json' => $configsArray,
                'menus_asignados' => (int)$row['menus_asignados'],
                'submenus_asignados' => (int)$row['submenus_asignados'],
                'submenus2_asignados' => (int)$row['submenus2_asignados']
            ];
        }
    }

    PlanesSyncHelper::responder([
        'data' => $data,
        'success' => true
    ]);

} catch (Exception $e) {
    PlanesSyncHelper::responder([
        'data' => [],
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
