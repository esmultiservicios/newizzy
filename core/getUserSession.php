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

$colaborador_id = $_SESSION['colaborador_id_sd'];

$result = $insMainModel->getUserSession($colaborador_id);

if ($result->num_rows > 0) {
    $consulta2 = $result->fetch_assoc();

    $nombre_completo = ucwords(strtolower(trim($consulta2['nombre'])));
    $partes = explode(" ", $nombre_completo);

    if (count($partes) > 2) {
        // Ej: Edwin Javier Velasquez Cortes → Edwin Velasquez
        $primer_nombre = $partes[0] ?? '';
        $primer_apellido = $partes[2] ?? '';
        $usuario_sistema_nombre = $primer_nombre . " " . $primer_apellido;
    } else {
        // Ej: ES MULTISERVICIOS → se muestra completo
        $usuario_sistema_nombre = $nombre_completo;
    }

    echo $usuario_sistema_nombre;
}
