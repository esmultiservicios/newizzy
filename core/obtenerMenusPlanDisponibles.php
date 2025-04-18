<?php
//obtenerMenusPlanDisponibles.php
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
                m.menu_id,
                m.name,
                m.descripcion,
                CASE 
                    WHEN mp.menu_id IS NOT NULL AND mp.estado = 1 THEN 1 
                    ELSE 0 
                END AS asignado
            FROM 
                menu m
            LEFT JOIN 
                menu_plan mp ON m.menu_id = mp.menu_id AND mp.planes_id = ?
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
                'menu_id' => (int)$row['menu_id'],
                'name' => $row['descripcion'] ?? '',
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
        'message' => 'Error al obtener los menús: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conexion)) {
        $conexion->autocommit(true);
        $conexion->close();
    }
}