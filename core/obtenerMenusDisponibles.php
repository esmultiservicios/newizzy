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
            SELECT m.menu_id, m.name, 
            CASE WHEN mp.menu_id IS NOT NULL THEN 1 ELSE 0 END as asignado
            FROM menu m
            LEFT JOIN menu_plan mp ON m.menu_id = mp.menu_id AND mp.planes_id = ?;
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
                'menu_id' => $row['menu_id'],
                'name' => $row['name'],
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