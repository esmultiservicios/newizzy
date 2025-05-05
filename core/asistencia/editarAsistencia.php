<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$asistencia_id = isset($_POST['asistencia_id']) ? intval($_POST['asistencia_id']) : 0;

$query = "SELECT a.colaboradores_id, a.fecha, a.horai, a.horaf, a.comentario, a.estado,
          c.nombre as nombre_colaborador
          FROM asistencia a
          JOIN colaboradores c ON a.colaboradores_id = c.colaboradores_id
          WHERE a.asistencia_id = ?";

$result = $mainModel->ejecutar_consulta_simple_preparada($query, "i", [$asistencia_id]);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Formatear datos para el formulario
    $response = [
        $row['colaboradores_id'], // Valor para el select
        date("Y-m-d", strtotime($row['fecha'])), // Fecha en formato Y-m-d
        $row['horai'] ? substr($row['horai'], 0, 5) : '', // Hora de entrada (HH:MM)
        $row['horaf'] ? substr($row['horaf'], 0, 5) : '', // Hora de salida (HH:MM)
        $row['comentario'],
        $row['estado']
    ];
    
    echo json_encode($response);
} else {
    echo json_encode([]);
}