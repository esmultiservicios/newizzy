<?php
// cerrarSesionInactiva.php
require_once __DIR__ . '/configGenerales.php';
require_once __DIR__ . '/mainModel.php';

// Iniciar sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start(['name' => 'SD']);
}

// Verificar si tenemos datos del beacon
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['codigo_bitacora'])) {
    $mainModel = new mainModel();
    
    // Registrar hora de salida en bitácora
    $hora_salida = date("H:i:s");
    $mainModel->actualizar_hora_salida_bitacora($data['codigo_bitacora'], $hora_salida);
    
    // Registrar en el historial de accesos
    $mainModel->guardarHistorial([
        'modulo' => 'Login',
        'colaboradores_id' => isset($data['colaborador_id']) ? $data['colaborador_id'] : 0,
        'status' => 1,
        'observacion' => 'Cierre por inactividad'
    ]);
    
    // Responder con éxito
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Si no hay datos válidos, responder con error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
exit;