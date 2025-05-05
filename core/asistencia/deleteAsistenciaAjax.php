<?php
//deleteAsistenciaAjax.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Obtener ID de asistencia
$asistencia_id = isset($_POST['asistencia_id']) ? intval($_POST['asistencia_id']) : 0;

// 1. Verificar si existe el registro y obtener datos
$query_check = "SELECT a.asistencia_id, a.colaboradores_id, a.fecha, a.horai, a.horaf, c.nombre 
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

// 2. Obtener el último historial_id
$query_last_id = "SELECT MAX(historial_id) as last_id FROM historial";
$result_last_id = $mainModel->ejecutar_consulta_simple($query_last_id);
$last_id = 1; // Valor por defecto si no hay registros

if ($result_last_id && $result_last_id->num_rows > 0) {
    $row_last = $result_last_id->fetch_assoc();
    $last_id = $row_last['last_id'] ? $row_last['last_id'] + 1 : 1;
}

// 3. Guardar información en el historial
$horarios = "Entrada: " . ($row['horai'] ?? 'N/A');
if (!empty($row['horaf'])) {
    $horarios .= ", Salida: " . $row['horaf'];
}

$observacion = "Se eliminó la asistencia completa del colaborador " . $row['nombre'] . 
               " para la fecha " . $row['fecha'] . " (" . $horarios . ")";
    
$query_historial = "INSERT INTO historial 
                   (historial_id, modulo, colaboradores_id, status, observacion, fecha_registro) 
                   VALUES (?, 'Asistencia', ?, 'Eliminación', ?, NOW())";
    
$historial_result = $mainModel->ejecutar_consulta_simple_preparada($query_historial, "iis", [
    $last_id,
    $row['colaboradores_id'],
    $observacion
]);

// 4. Eliminar el registro completo
$query_delete = "DELETE FROM asistencia WHERE asistencia_id = ?";
$result_delete = $mainModel->ejecutar_consulta_simple_preparada($query_delete, "i", [$asistencia_id]);

if ($result_delete) {
    $alerta = [
        "Alerta" => "recargar",
        "Titulo" => "Éxito",
        "Texto" => "Asistencia eliminada correctamente",
        "Tipo" => "success"
    ];
} else {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "Error al eliminar la asistencia",
        "Tipo" => "error"
    ];
}

echo json_encode($alerta);