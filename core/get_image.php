<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "Database.php";

$database = new Database();
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

$empresa_id = $_SESSION['empresa_id_sd'];

$tablaEmpresa = "empresa";
$camposEpresa = ["logotipo"];
$condiciones = ["empresa_id" => $empresa_id];
$orderBy = "";
$resultadoClientes = $database->consultarTabla($tablaEmpresa, $camposEpresa, $condiciones, $orderBy);

if (!empty($resultadoClientes)) {
    // Obtiene el nombre de la imagen de la base de datos
    $image = $resultadoClientes[0]['logotipo'];
} else {
    $image = "logo.png";  // Imagen predeterminada si no se encuentra en la base de datos
}

// Construye la URL completa para la imagen (puedes usar la URL externa aquí)
$imageUrl = "https://wi.fastsolutionhn.com/files/" . $image;

if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    // Si la URL es válida, obtenemos el contenido de la imagen desde la URL externa
    $imagenData = file_get_contents($imageUrl);  // Usamos file_get_contents para obtener los datos de la imagen
    
    if ($imagenData !== false) {
        // Codificamos la imagen en Base64
        $base64 = base64_encode($imagenData);
        echo "data:image/png;base64," . $base64;  // Devolvemos el Base64 para usarlo en JavaScript
    } else {
        echo "ERROR";  // Si no se puede obtener la imagen, devolvemos un error
    }
} else {
    echo "ERROR";  // Si la URL no es válida, devolvemos un error
}