<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

// Crear una conexión mysqli
$mysqli = $insMainModel->connection();

// Preparar la consulta SQL con parámetros
$sql = "SELECT MostrarFirma FROM empresa WHERE empresa_id = ?";

// Preparar la consulta
$stmt = $mysqli->prepare($sql);

// Verificar si la preparación fue exitosa
if (!$stmt) {
    echo json_encode(['error' => 'Error en la preparación de la consulta: ' . $mysqli->error]);
    exit;
}

// Asignar valores a los parámetros
$empresa_id = $_SESSION['empresa_id_sd'];
$stmt->bind_param('i', $empresa_id);

// Ejecutar la consulta
$stmt->execute();

// Obtener el resultado
$result = $stmt->get_result();

// Verificar si se encontró un registro
if ($row = $result->fetch_assoc()) {
    $mostrarFirma = $row['MostrarFirma'];
    // Enviar el estado como JSON
    echo json_encode(['estado' => $mostrarFirma ? 'visible' : 'oculto']);
} else {
    echo json_encode(['error' => 'No se encontró el registro.']);
}

// Cerrar el statement y la conexión
$stmt->close();
$mysqli->close();