<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();

// 1. Obtener el plan del sistema (solo hay 1 registro según tu estructura)
$plan = $insMainModel->ejecutar_consulta_simple("SELECT planes_id FROM plan LIMIT 1");
$planes_id = ($plan->num_rows > 0) ? (int)$plan->fetch_assoc()['planes_id'] : 0;

// 2. ID fijo del programa de puntos (según tus tablas)
$submenu_id = 38; 

// 3. Verificar permisos (EXACTAMENTE como lo pides)
$tiene_acceso = false;
$estado = 0;

if($planes_id > 0) {
    $consulta = $insMainModel->ejecutar_consulta_simple_preparada(
        "SELECT estado FROM submenu_plan WHERE planes_id = ? AND submenu_id = ?", 
        "ii", 
        [$planes_id, $submenu_id]
    );
    
    // Si existe registro, verificar estado
    if($consulta->num_rows > 0) {
        $estado = (int)$consulta->fetch_assoc()['estado'];
        $tiene_acceso = ($estado === 1);
    }
    // Si no existe registro, $tiene_acceso queda en false
}

echo json_encode([
    'mostrar_puntos' => $tiene_acceso,
    'detalles' => [
        'planes_id' => $planes_id,
        'submenu_id' => $submenu_id,
        'estado_encontrado' => $estado,
        'existe_registro' => ($consulta->num_rows > 0)
    ]
]);