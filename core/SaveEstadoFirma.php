<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

if (!isset($_SESSION['user_sd'])) { 
    session_start(['name' => 'SD']); 
}

// Crear una instancia de la clase MainModel
$insMainModel = new MainModel();

// Crear una conexión mysqli
$mysqli = $insMainModel->connection();

$mostrarFirma = $_POST["estado"];

// Preparar la consulta SQL con parámetros
$sql = "UPDATE empresa SET MostrarFirma = ? WHERE empresa_id = ?";

// Preparar la consulta
$stmt = $mysqli->prepare($sql);

// Verificar si la preparación fue exitosa
if (!$stmt) {
    echo json_encode([
        'alert' => 'simple',
        'title' => 'Error',
        'text' => 'Error en la preparación de la consulta: ' . $mysqli->error,
        'type' => 'error',
        'btn-class' => 'btn-danger'
    ]);
    exit;
}

// Vincular parámetros y ejecutar la consulta
$stmt->bind_param('ii', $mostrarFirma, $_SESSION['empresa_id_sd']);
$stmt->execute();

// Verificar si la ejecución fue exitosa
if ($stmt->affected_rows > 0) {
    echo json_encode([
        'alert' => 'simple',
        'title' => 'Éxito',
        'text' => "Campo 'Mostrar Firma' actualizado exitosamente.",
        'type' => 'success',
        'btn-class' => 'btn-success'
    ]);
} else {
    echo json_encode([
        'alert' => 'simple',
        'title' => 'Atención',
        'text' => 'No se actualizó ningún registro o no hubo cambios.',
        'type' => 'warning',
        'btn-class' => 'btn-warning'
    ]);
}

// Cerrar el statement
$stmt->close();

// Cerrar la conexión
$mysqli->close();