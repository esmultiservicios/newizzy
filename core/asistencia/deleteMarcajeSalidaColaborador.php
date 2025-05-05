<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Obtener ID de asistencia
$asistencia_id = isset($_POST['asistencia_id']) ? intval($_POST['asistencia_id']) : 0;

// 1. Verificar si existe el registro y obtener datos
$query_check = "SELECT a.asistencia_id, a.colaboradores_id, a.horaf, c.nombre, a.fecha, a.horai
               FROM asistencia a
               JOIN colaboradores c ON a.colaboradores_id = c.colaboradores_id
               WHERE a.asistencia_id = ?";
$result_check = $mainModel->ejecutar_consulta_simple_preparada($query_check, "i", [$asistencia_id]);

if (!$result_check || $result_check->num_rows == 0) {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "El registro de asistencia no existe",
        "Tipo" => "error"
    ];
    echo json_encode($alerta);
    exit();
}

$row = $result_check->fetch_assoc();

// Validar si ya no tiene hora de salida
if (empty($row['horaf'])) {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Advertencia",
        "Texto" => "El registro ya no tiene hora de salida",
        "Tipo" => "warning"
    ];
    echo json_encode($alerta);
    exit();
}

// 2. Obtener el último historial_id
$query_last_id = "SELECT MAX(historial_id) as last_id FROM historial";
$result_last_id = $mainModel->ejecutar_consulta_simple($query_last_id);
$last_id = 1;

if ($result_last_id && $result_last_id->num_rows > 0) {
    $row_last = $result_last_id->fetch_assoc();
    $last_id = $row_last['last_id'] ? $row_last['last_id'] + 1 : 1;
}

// 3. Guardar información en el historial
$observacion = "Se eliminó el marcaje de salida (" . $row['horaf'] . ") del colaborador " . $row['nombre'] . 
               " para la fecha " . $row['fecha'] . " (Hora entrada: " . $row['horai'] . ")";
    
$query_historial = "INSERT INTO historial 
                   (historial_id, modulo, colaboradores_id, status, observacion, fecha_registro) 
                   VALUES (?, 'Asistencia', ?, 'Eliminación', ?, NOW())";
    
$historial_result = $mainModel->ejecutar_consulta_simple_preparada($query_historial, "iis", [
    $last_id,
    $row['colaboradores_id'],
    $observacion
]);

// 4. Actualizar solo el campo horaf (no eliminar el registro completo)
$query_update = "UPDATE asistencia SET horaf = NULL WHERE asistencia_id = ?";
$result_update = $mainModel->ejecutar_consulta_simple_preparada($query_update, "i", [$asistencia_id]);

if ($result_update) {
    $alerta = [
        "Alerta" => "recargar",
        "Titulo" => "Éxito",
        "Texto" => "Marcaje de salida eliminado correctamente",
        "Tipo" => "success"
    ];
} else {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "Error al eliminar el marcaje de salida",
        "Tipo" => "error"
    ];
}

echo json_encode($alerta);