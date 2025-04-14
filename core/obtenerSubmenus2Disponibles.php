<?php
//obtenerSubmenusDisponibles.php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
$conexion = $insMainModel->connection(); // Asignamos a $conexion

try {    
    // Desactivar autocommit para la transacción
    $conexion->autocommit(false);

    $planes_id = $insMainModel->cleanString($_POST['plan_id']);
    $planes_id = (int) $planes_id;

    if ($planes_id !== null) {
        $queryMenu = "
            SELECT 
                s1.submenu1_id, 
                s1.name,
                m.name as menu_name,
                s.name as submenu_name,
                CASE WHEN s1p.submenu1_id IS NOT NULL THEN 1 ELSE 0 END as asignado
              FROM submenu1 s1
              JOIN submenu s ON s1.submenu_id = s.submenu_id
              JOIN menu m ON s.menu_id = m.menu_id
              LEFT JOIN submenu1_plan s1p ON s1.submenu1_id = s1p.submenu1_id AND s1p.planes_id = ?;
        ";

        $stmt = $conexion->prepare($queryMenu);
        if ($stmt === false) {
            throw new Exception('Error al preparar la consulta: ' . $conexion->error);
        }

        $stmt->bind_param('i', $planes_id);
        $stmt->execute();
        $resultMenu = $stmt->get_result();
        $data = [];
        
        while ($row = $resultMenu->fetch_assoc()) {
            $data[] = array(
                'submenu1_id' => $row['submenu1_id'],
                'name' => $row['name'],
                'menu_name' => $row['menu_name'],
                'submenu_name' => $row['submenu_name'],                
                'asignado' => (bool)$row['asignado']
            );
        }

        $conexion->commit();
        echo json_encode(["data" => $data]);
    } else {
        echo json_encode(["data" => []]);
    }
} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    echo json_encode(["error" => "Error al obtener los menús: " . $e->getMessage()]);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}