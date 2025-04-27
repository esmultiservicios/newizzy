<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();
$id = intval($_POST['programa_puntos_id']);

try {
    // 1. Verificar si tiene puntos asociados
    $query_check = "SELECT COUNT(*) as total FROM puntos_cliente WHERE programa_puntos_id = ?";
    $result_check = $mainModel->ejecutar_consulta_simple_preparada($query_check, "i", [$id]);
    
    if ($result_check) {
        $row = $result_check->fetch_assoc();
        if ($row['total'] > 0) {
            throw new Exception("No se puede eliminar el programa porque tiene puntos asociados a clientes");
        }
    }

    // 2. Eliminar programa
    $query_delete = "DELETE FROM programa_puntos WHERE id = ?";
    $result_delete = $mainModel->ejecutar_consulta_simple_preparada($query_delete, "i", [$id]);
    
    if ($result_delete) {
        echo json_encode([
            'type' => 'success',
            'title' => 'Éxito',
            'message' => 'Programa eliminado correctamente',
            'estado' => true
        ]);
    } else {
        throw new Exception("Error al eliminar el programa");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => $e->getMessage(),
        'estado' => false
    ]);
}