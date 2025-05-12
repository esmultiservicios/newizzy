<?php
// llenarDataTableHistoricoPuntos.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();

// Obtener parámetros
$cliente_id = isset($_POST['cliente_id']) ? intval($_POST['cliente_id']) : 0;
$programa_id = isset($_POST['programa_puntos_id']) ? intval($_POST['programa_puntos_id']) : 0;

if($cliente_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "ID de cliente no válido",
        "nombre_cliente" => "",
        "total_puntos" => 0,
        "historial" => []
    ]);
    exit();
}

try {
    // 1. Obtener información del cliente
    $query_cliente = "SELECT nombre FROM clientes WHERE clientes_id = ?";
    $result_cliente = $insMainModel->ejecutar_consulta_simple_preparada($query_cliente, "i", [$cliente_id]);
    $cliente = $result_cliente->fetch_assoc();
    
    $nombre_cliente = $cliente ? $cliente['nombre'] : 'Cliente #'.$cliente_id;

    // 2. Obtener historial de puntos
    $query_historial = "SELECT 
                            CASE 
                                WHEN hp.tipo_movimiento = 'acumulacion' THEN 'Acumulación'
                                ELSE 'Redención'
                            END as tipo,
                            hp.puntos,
                            hp.descripcion,
                            DATE_FORMAT(hp.fecha, '%d/%m/%Y %h:%i %p') as fecha
                        FROM historial_puntos hp
                        WHERE hp.cliente_id = ?";
    
    // Si se especifica programa_id, filtrar por él
    if($programa_id > 0) {
        $query_historial .= " AND hp.programa_puntos_id = ?";
        $params = [$cliente_id, $programa_id];
        $types = "ii";
    } else {
        $params = [$cliente_id];
        $types = "i";
    }
    
    $query_historial .= " ORDER BY hp.fecha DESC";
    
    $result_historial = $insMainModel->ejecutar_consulta_simple_preparada($query_historial, $types, $params);
    
    $historial = array();
    while ($row = $result_historial->fetch_assoc()) {
        $historial[] = $row;
    }

    // 3. Calcular total de puntos
    $query_total = "SELECT 
                        SUM(CASE 
                            WHEN tipo_movimiento = 'acumulacion' THEN puntos
                            ELSE -puntos
                        END) as total
                    FROM historial_puntos
                    WHERE cliente_id = ?";
    
    if($programa_id > 0) {
        $query_total .= " AND programa_puntos_id = ?";
    }
    
    $result_total = $insMainModel->ejecutar_consulta_simple_preparada($query_total, $types, $params);
    $total = $result_total->fetch_assoc();
    $total_puntos = $total ? floatval($total['total']) : 0.00;

    // 4. Obtener última actualización
    $query_last = "SELECT MAX(fecha) as ultima FROM historial_puntos WHERE cliente_id = ?";
    if($programa_id > 0) {
        $query_last .= " AND programa_puntos_id = ?";
    }
    
    $result_last = $insMainModel->ejecutar_consulta_simple_preparada($query_last, $types, $params);
    $last = $result_last->fetch_assoc();
    
    echo json_encode([
        "success" => true,
        "message" => "Datos obtenidos correctamente",
        "nombre_cliente" => $nombre_cliente,
        "total_puntos" => $total_puntos,
        "historial" => $historial,
        "ultima_actualizacion" => isset($last['ultima']) ? 
            date('d/m/Y H:i', strtotime($last['ultima'])) : 'No disponible'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "nombre_cliente" => "",
        "total_puntos" => 0,
        "historial" => []
    ]);
}