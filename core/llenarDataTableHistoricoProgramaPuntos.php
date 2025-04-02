<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Obtenemos el ID del programa de puntos
$programa_puntos_id = isset($_POST['programa_puntos_id']) ? $_POST['programa_puntos_id'] : '';

if(empty($programa_puntos_id)) {
    echo json_encode(array("error" => "ID de programa no proporcionado"));
    exit;
}

// Consulta para obtener el historial
$query = "SELECT 
            h.*, 
            c.nombre as cliente_nombre,
            c.apellido as cliente_apellido
          FROM historial_puntos h
          JOIN clientes c ON h.cliente_id = c.clientes_id
          WHERE h.programa_puntos_id = ?
          ORDER BY h.fecha DESC";

$stmt = $insMainModel->connection()->prepare($query);
$stmt->bind_param('i', $programa_puntos_id);
$stmt->execute();
$result = $stmt->get_result();

$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = array(
        "cliente" => $row['cliente_nombre'] . ' ' . $row['cliente_apellido'],
        "tipo_movimiento" => $row['tipo_movimiento'] == 'acumulacion' ? 'Acumulación' : 'Redención',
        "puntos" => number_format($row['puntos'], 2),
        "descripcion" => $row['descripcion'],
        "fecha" => date('d/m/Y H:i', strtotime($row['fecha']))
    );
}

// Obtenemos la última fecha de actualización
$query_last_update = "SELECT MAX(fecha) as ultima_actualizacion FROM historial_puntos WHERE programa_puntos_id = ?";
$stmt_last = $insMainModel->connection()->prepare($query_last_update);
$stmt_last->bind_param('i', $programa_puntos_id);
$stmt_last->execute();
$result_last = $stmt_last->get_result();
$last_update = $result_last->fetch_assoc();

$arreglo = array(
    "data" => $data,
    "ultima_actualizacion" => $last_update['ultima_actualizacion'] ? date('d/m/Y H:i', strtotime($last_update['ultima_actualizacion'])) : 'No disponible'
);

echo json_encode($arreglo);