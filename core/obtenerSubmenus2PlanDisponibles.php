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
                s1.name,
                s1.submenu_id,
                s.name AS submenu_name,
                m.name AS menu_name,
                CASE 
                    WHEN s1p.submenu1_id IS NOT NULL THEN 1 
                    ELSE 0 
                END AS asignado
            FROM 
                submenu1 s1
            JOIN 
                submenu s ON s1.submenu_id = s.submenu_id
            JOIN 
                menu m ON s.menu_id = m.menu_id
            LEFT JOIN 
                submenu1_plan s1p ON s1.submenu1_id = s1p.submenu1_id AND s1p.planes_id = ?
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
                'submenu_name' => $row['submenu_name'],
                'menu_name' => $row['menu_name'],
                'asignado' => (bool)$row['asignado']
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
        'message' => 'Error al obtener los submenús nivel 2: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}