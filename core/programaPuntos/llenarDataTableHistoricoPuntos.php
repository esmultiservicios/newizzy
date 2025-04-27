<?php
//llenarDataTableHistoricoPuntos.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();
$programa_id = intval($_POST['programa_puntos_id']);

$query = "SELECT 
            c.nombre as cliente,
            CASE 
                WHEN hp.tipo_movimiento = 'acumulacion' THEN 'Acumulación'
                ELSE 'Redención'
            END as tipo_movimiento,
            hp.puntos,
            hp.descripcion,
            DATE_FORMAT(hp.fecha, '%d/%m/%Y %h:%i %p') as fecha
          FROM historial_puntos hp
          JOIN clientes c ON hp.cliente_id = c.clientes_id
          WHERE hp.programa_puntos_id = ?
          ORDER BY hp.fecha DESC";

try {
    $result = $insMainModel->ejecutar_consulta_simple_preparada($query, "i", [$programa_id]);
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    // Obtener última actualización
    $query_last = "SELECT MAX(fecha) as ultima FROM historial_puntos WHERE programa_puntos_id = ?";
    $result_last = $insMainModel->ejecutar_consulta_simple_preparada($query_last, "i", [$programa_id]);
    $last = $result_last->fetch_assoc();
    
    echo json_encode([
        "data" => $data,
        "ultima_actualizacion" => isset($last['ultima']) ? 
            date('d/m/Y H:i', strtotime($last['ultima'])) : 'No disponible'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}