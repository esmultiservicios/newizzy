<?php
//addAsistenciaMarcajeAjax.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Validar y limpiar datos
$colaborador_id = $mainModel->cleanString($_POST['asistencia_empleado']);
$fecha = date('Y-m-d'); // Fecha actual automática
$hora_actual = date('H:i:s'); // Hora actual con segundos
$marcar_asistencia = isset($_POST['marcarAsistencia_id']) ? intval($_POST['marcarAsistencia_id']) : 0;

// Validaciones básicas
if (empty($colaborador_id)) {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "Debe seleccionar un colaborador",
        "Tipo" => "error"
    ];
    echo json_encode($alerta);
    exit();
}

// Verificar si ya existe un registro para este colaborador en la fecha actual
$query_check = "SELECT asistencia_id FROM asistencia 
                WHERE colaboradores_id = ? AND fecha = ?";
$result_check = $mainModel->ejecutar_consulta_simple_preparada($query_check, "is", [$colaborador_id, $fecha]);

if ($result_check && $result_check->num_rows > 0) {
    // Si existe registro, actualizar hora de salida
    $row = $result_check->fetch_assoc();
    $query = "UPDATE asistencia SET horaf = ? WHERE asistencia_id = ?";
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "si", [$hora_actual, $row['asistencia_id']]);
    
    if ($result) {
        $alerta = [
            "Alerta" => "recargar",
            "Titulo" => "Éxito",
            "Texto" => "Marcaje de salida registrado correctamente",
            "Tipo" => "success"
        ];
    } else {
        $alerta = [
            "Alerta" => "simple",
            "Titulo" => "Error",
            "Texto" => "Error al registrar el marcaje de salida",
            "Tipo" => "error"
        ];
    }
} else {
    // Si no existe registro, crear nuevo con hora de entrada
    $query = "INSERT INTO asistencia 
              (colaboradores_id, fecha, horai, estado, fecha_registro) 
              VALUES (?, ?, ?, 0, NOW())";

    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "iss", [$colaborador_id, $fecha, $hora_actual]);

    if ($result) {
        $alerta = [
            "Alerta" => "recargar",
            "Titulo" => "Éxito",
            "Texto" => "Marcaje de entrada registrado correctamente",
            "Tipo" => "success"
        ];
    } else {
        $alerta = [
            "Alerta" => "simple",
            "Titulo" => "Error",
            "Texto" => "Error al registrar el marcaje de entrada",
            "Tipo" => "error"
        ];
    }
}

echo json_encode($alerta);