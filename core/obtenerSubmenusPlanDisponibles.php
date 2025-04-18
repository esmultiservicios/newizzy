<?php
//obtenerSubmenusPlanDisponibles.php
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
            s.submenu_id,
            s.menu_id,
            s.name,
            s.descripcion,
            s.icon,
            s.orden,
            m.name AS menu_name, 
            m.descripcion AS descripcion_padre,
            CASE 
                WHEN sp.submenu_id IS NOT NULL AND sp.estado = 1 THEN 1 
                ELSE 0 
            END AS asignado
        FROM 
            submenu s
        LEFT JOIN 
            submenu_plan sp ON s.submenu_id = sp.submenu_id AND sp.planes_id = ?
        LEFT JOIN
            menu m ON s.menu_id = m.menu_id 
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
                'submenu_id' => (int)$row['submenu_id'],
                'name' => $row['name'],
                'menu_id' => (int)$row['menu_id'],
                'menu_name' => $row['menu_name'] ?? '',
                'descripcion_padre' => $row['descripcion_padre'] ?? '',
                'asignado' => (bool)$row['asignado'],
                'descripcion' => $row['descripcion'] ?? '',
                'icon' => $row['icon'] ?? '',
                'orden' => (int)$row['orden'] ?? 0
            ];
        }

        $conexion->commit();
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ID de plan no válido'
        ]);
    }
} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener los submenús: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}