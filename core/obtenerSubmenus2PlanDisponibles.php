<?php
//obtenerSubmenus2PlanDisponibles.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
$conexion = $insMainModel->connection();

try {    
    $conexion->autocommit(false);
    $planes_id = $insMainModel->cleanString($_POST['plan_id']);
    $planes_id = (int) $planes_id;

    if ($planes_id !== null) {
        $query = "
        SELECT 
            s1.submenu1_id,
            s1.submenu_id,
            s1.name,
            s1.descripcion,
            s1.icon,
            s1.orden,
            s.name AS submenu_name,
            s.descripcion AS descripcion_padre,
            m.name AS menu_name,
            m.descripcion AS descripcion_menu,
            CASE 
                WHEN sp.submenu1_id IS NOT NULL AND sp.estado = 1 THEN 1 
                ELSE 0 
            END AS asignado
        FROM 
            submenu1 s1
        LEFT JOIN 
            submenu1_plan sp ON s1.submenu1_id = sp.submenu1_id AND sp.planes_id = ?
        LEFT JOIN
            submenu s ON s1.submenu_id = s.submenu_id
        LEFT JOIN
            menu m ON s.menu_id = m.menu_id
        ORDER BY
            m.orden ASC,
            s.orden ASC,
            s1.orden ASC
        ";

        $stmt = $conexion->prepare($query);
        if ($stmt === false) {
            throw new Exception('Error al preparar la consulta: ' . $conexion->error);
        }

        $stmt->bind_param('i', $planes_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'submenu1_id' => (int)$row['submenu1_id'],
                'name' => $row['name'],
                'submenu_id' => (int)$row['submenu_id'],
                'submenu_name' => $row['submenu_name'] ?? 'Sin submenú',
                'menu_name' => $row['menu_name'] ?? 'Sin menú',
                'asignado' => (bool)$row['asignado'],
                'descripcion' => $row['descripcion'] ?? '',
                'descripcion_padre' => $row['descripcion_padre'] ?? '',
                'descripcion_menu' => $row['descripcion_menu'] ?? '',
                'icon' => $row['icon'] ?? '',
                'orden' => (int)($row['orden'] ?? 0)
            ];
        }

        $conexion->commit();
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID de plan no válido'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los submenús nivel 2: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}