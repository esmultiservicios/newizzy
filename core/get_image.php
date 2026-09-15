<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";
require_once "Database.php";

$database = new Database();
$insMainModel = new mainModel();

// Validar sesión primero.
$validacion = $insMainModel->validarSesion();
if ($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int) $_SESSION['empresa_id_sd'] : 0;

// Sin empresa válida no hay logo que consultar.
if ($empresa_id <= 0) {
    echo "ERROR";
    return;
}

$tablaEmpresa = "empresa";
$camposEmpresa = ["logotipo"];
$condiciones = ["empresa_id" => $empresa_id];
$orderBy = "";
$resultadoClientes = $database->consultarTabla($tablaEmpresa, $camposEmpresa, $condiciones, $orderBy);

// Si la empresa no tiene logo, no se intenta consultar /files/ como si fuera una imagen.
$image = '';
if (!empty($resultadoClientes) && isset($resultadoClientes[0]['logotipo'])) {
    $image = trim((string) $resultadoClientes[0]['logotipo']);
}

if ($image === '') {
    echo "ERROR";
    return;
}

// Conserva el comportamiento actual: el campo logotipo contiene el nombre/ruta del archivo.
$image = ltrim($image, "/\\");
$imageUrl = "https://wi.fastsolutionhn.com/files/" . $image;

if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
    echo "ERROR";
    return;
}

// Evita warnings en el log si el archivo remoto no existe, responde 403 o no está disponible.
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 8,
        'ignore_errors' => false,
        'header' => "User-Agent: IZZY/1.0\r\nAccept: image/*\r\n"
    ],
    'https' => [
        'method' => 'GET',
        'timeout' => 8,
        'ignore_errors' => false,
        'header' => "User-Agent: IZZY/1.0\r\nAccept: image/*\r\n"
    ]
]);

$imagenData = @file_get_contents($imageUrl, false, $context);

if ($imagenData === false || $imagenData === '') {
    echo "ERROR";
    return;
}

// Detectar el MIME real cuando sea posible; mantiene PNG como respaldo.
$mime = 'image/png';
if (function_exists('getimagesizefromstring')) {
    $info = @getimagesizefromstring($imagenData);
    if (is_array($info) && !empty($info['mime']) && strpos($info['mime'], 'image/') === 0) {
        $mime = $info['mime'];
    }
}

echo 'data:' . $mime . ';base64,' . base64_encode($imagenData);
