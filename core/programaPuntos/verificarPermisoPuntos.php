<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();

// Obtener planes_id del usuario actual (debes adaptar esto según tu sistema)
$planes_id = $insMainModel->obtenerPlanUsuario(); // Implementa esta función según tu lógica

// Obtener submenu_id para 'programaPuntos'
$submenu_id = $insMainModel->obtenerSubmenuIdPorNombre('programaPuntos');

// Verificar en submenu_plan
$permiso = 0; // Por defecto ocultar
if($planes_id && $submenu_id) {
    $consulta = "SELECT estado FROM submenu_plan 
                WHERE planes_id = ? AND submenu_id = ?";
    $resultado = $insMainModel->ejecutar_consulta_simple_preparada($consulta, "ii", [$planes_id, $submenu_id]);
    
    if($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        $permiso = (int)$fila['estado'];
    }
}

echo json_encode([
    'permiso' => $permiso,
    'planes_id' => $planes_id,
    'submenu_id' => $submenu_id
]);