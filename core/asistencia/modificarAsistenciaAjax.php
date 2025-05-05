<?php
//modificarAsistenciaAjax.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Validar y limpiar datos
$asistencia_id = $mainModel->cleanString($_POST['asistencia_id']);
$colaborador_id = $mainModel->cleanString($_POST['asistencia_empleado']);
$fecha = $mainModel->cleanString($_POST['fecha']);
$hora_entrada = $mainModel->cleanString($_POST['hora']);
$hora_salida = isset($_POST['horaf']) ? $mainModel->cleanString($_POST['horaf']) : null;
$comentario = isset($_POST['comentario']) ? $mainModel->cleanString($_POST['comentario']) : '';

// Validaciones básicas
if (empty($asistencia_id) || empty($colaborador_id) || empty($fecha) || empty($hora_entrada)) {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "Todos los campos obligatorios deben ser completados",
        "Tipo" => "error"
    ];
    echo json_encode($alerta);
    exit();
}

// Verificar si el registro existe
$query_check = "SELECT asistencia_id FROM asistencia WHERE asistencia_id = ?";
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

// Actualizar registro
$query = "UPDATE asistencia SET 
          colaboradores_id = ?, 
          fecha = ?, 
          horai = ?, 
          horaf = ?, 
          comentario = ?
          WHERE asistencia_id = ?";

$params = [
    $colaborador_id,
    $fecha,
    $hora_entrada . ":00", // Agregar segundos
    $hora_salida ? $hora_salida . ":00" : null,
    $comentario,
    $asistencia_id
];

$types = "issssi";

$result = $mainModel->ejecutar_consulta_simple_preparada($query, $types, $params);

if ($result) {
    $alerta = [
        "Alerta" => "recargar",
        "Titulo" => "Éxito",
        "Texto" => "Asistencia actualizada correctamente",
        "Tipo" => "success"
    ];
} else {
    $alerta = [
        "Alerta" => "simple",
        "Titulo" => "Error",
        "Texto" => "Error al actualizar la asistencia",
        "Tipo" => "error"
    ];
}

echo json_encode($alerta);