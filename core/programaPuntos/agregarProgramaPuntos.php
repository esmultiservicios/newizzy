<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$datos = [
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

// Validar que no exista otro programa activo
try {
    $query_check = "SELECT COUNT(*) as total FROM programa_puntos WHERE activo = 1";
    $result_check = $mainModel->ejecutar_consulta_simple_preparada($query_check, "", []);
    
    if ($result_check) {
        $row = $result_check->fetch_assoc();
        if ($row['total'] > 0 && $datos['estado'] == 1) {
            echo json_encode([
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Ya existe un programa de puntos activo. Solo puede haber un programa activo a la vez.',
                'estado' => false
            ]);
            exit;
        }
    }

    // Continuar con el registro
    $query = "INSERT INTO programa_puntos (nombre, tipo_calculo, monto, porcentaje, activo) VALUES (?, ?, ?, ?, ?)";
    $types = "ssddi";
    $params = [
        $datos['nombre'],
        $datos['tipo_calculo'],
        $datos['monto'],
        $datos['porcentaje'],
        $datos['estado']
    ];    

    $result = $mainModel->ejecutar_consulta_simple_preparada($query, $types, $params);
    
    if ($result) {
        $programa_id = $mainModel->connection()->insert_id;
        echo json_encode([
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Programa creado correctamente',
            'id' => $programa_id,
            'estado' => true
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ]);
}