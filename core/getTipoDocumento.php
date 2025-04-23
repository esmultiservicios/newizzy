<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

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

$datos = [
    'empresa_id_sd' => $_SESSION['empresa_id_sd']
];

$result = $insMainModel->getTipoDocumento($datos);

// Comprobar si hay resultados
if ($result->num_rows > 0) {
    // Si hay resultados, se obtiene el primer documento_id
    $consulta2 = $result->fetch_assoc();
    $documento_id = $consulta2['documento_id'];
    // Se devuelve solo el documento_id
    echo json_encode($documento_id);
} else {
    // Si no hay resultados, devolver un mensaje
    echo json_encode(['mensaje' => 'No hay datos que mostrar']);
}
