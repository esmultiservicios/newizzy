<?php
//getHistorialPuntos.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Datos recibidos por POST
$cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;

// Primero verificar si el cliente tiene acceso al programa de puntos
$query_acceso = "SELECT sp.estado 
                FROM plan p 
                JOIN submenu_plan sp ON p.planes_id = sp.planes_id 
                WHERE p.plan_id = ? 
                AND sp.submenu_id = 38"; // 38 es programaPuntos

$params_acceso = [$cliente_id];
$result_acceso = $mainModel->ejecutar_consulta_simple_preparada($query_acceso, "i", $params_acceso);

if (!$result_acceso) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar acceso a puntos',
        'estado' => false
    ]);
    exit;
}

$tiene_acceso = false;
if($result_acceso->num_rows > 0) {
    $data_acceso = $result_acceso->fetch_assoc();
    $tiene_acceso = $data_acceso['estado'] == 1;
}

if(!$tiene_acceso) {
    echo json_encode([
        'success' => false,
        'message' => 'Este cliente no tiene acceso al programa de puntos',
        'estado' => false
    ]);
    exit;
}

// Consulta para obtener el historial de puntos
$query_historial = "SELECT hp.*, pp.nombre AS programa_puntos,
                   DATE_FORMAT(hp.fecha, '%d/%m/%Y %H:%i') AS fecha_formateada
                   FROM historial_puntos hp
                   LEFT JOIN programa_puntos pp ON hp.programa_puntos_id = pp.id
                   WHERE hp.cliente_id = ?
                   ORDER BY hp.fecha DESC";

$params_historial = [$cliente_id];
$result_historial = $mainModel->ejecutar_consulta_simple_preparada($query_historial, "i", $params_historial);

if (!$result_historial) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener historial de puntos',
        'estado' => false
    ]);
    exit;
}

$historial = array();
$total_puntos = 0;

while($row = $result_historial->fetch_assoc()) {
    $historial[] = [
        "fecha" => $row['fecha_formateada'],
        "tipo_movimiento" => $row['tipo_movimiento'],
        "puntos" => $row['puntos'],
        "descripcion" => $row['descripcion'] ?: $row['programa_puntos']
    ];
    
    // Calcular total de puntos
    $total_puntos += ($row['tipo_movimiento'] == 'acumulacion') ? $row['puntos'] : -$row['puntos'];
}

// Verificar si el cliente existe
$query_cliente = "SELECT nombre FROM clientes WHERE clientes_id = ?";
$params_cliente = [$cliente_id];
$result_cliente = $mainModel->ejecutar_consulta_simple_preparada($query_cliente, "i", $params_cliente);

if (!$result_cliente || $result_cliente->num_rows == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado',
        'estado' => false
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'historial' => $historial,
    'total_puntos' => $total_puntos,
    'cliente_id' => $cliente_id,
    'estado' => true
]);