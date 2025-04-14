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
                s.submenu_id, 
                s.name, 
                m.name as menu_name,
                m.menu_id,
                CASE WHEN sp.submenu_id IS NOT NULL THEN 1 ELSE 0 END as asignado
              FROM submenu s
              JOIN menu m ON s.menu_id = m.menu_id
              LEFT JOIN submenu_plan sp ON s.submenu_id = sp.submenu_id AND sp.planes_id = ?;
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
                'submenu_id' => $row['submenu_id'],
                'name' => $row['name'],
                'menu_name' => $row['menu_name'],
                'menu_id' => $row['menu_id'],
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