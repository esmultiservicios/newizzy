<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$datos = [
    'id' => intval($_POST['programa_puntos_id']),
    'nombre' => $mainModel->cleanString($_POST['nombre']),
    'tipo_calculo' => $mainModel->cleanString($_POST['tipo_calculo']),
    'monto' => isset($_POST['monto']) ? floatval($_POST['monto']) : 0,
    'porcentaje' => isset($_POST['porcentaje']) ? floatval($_POST['porcentaje']) : 0,
    'estado' => isset($_POST['estado']) ? intval($_POST['estado']) : 1
];

if (empty($datos['nombre'])) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'El nombre del programa es requerido',
        'estado' => false
    ]);
    exit;
}

try {
    $query = "UPDATE programa_puntos SET nombre = ?, tipo_calculo = ?, monto = ?, porcentaje = ?, activo = ? WHERE id = ?";
    
    $types = "ssddii";

    $params = [
        $datos['nombre'],
        $datos['tipo_calculo'],
        $datos['monto'],
        $datos['porcentaje'],
        $datos['estado'],
        $datos['id']
    ]; 
    
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, $types, $params);

    if ($result) {
        echo json_encode([
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Programa actualizado correctamente',
            'estado' => true
        ]);
    } else {
        echo json_encode([
            'type' => 'error',
            'title' => 'Error',
            'message' => 'No se pudo actualizar el programa',
            'estado' => false
        ]);
    }
    
} catch (Exception $e) {
    // Usamos la conexión del mainModel en lugar de $conexion
    if (method_exists($mainModel, 'connection')) {
        $mainModel->connection()->rollback();
    }
    
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ]);
} finally {
    // Cerramos la conexión usando el mainModel
    if (method_exists($mainModel, 'connection')) {
        $mainModel->connection()->autocommit(true);
        $mainModel->connection()->close();
    }
}