<?php
// Ubicación: core/secuencias/guardarDocumentoSecuenciaAjax.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

function respuestaDocumento($status, $title, $message){
    echo json_encode([
        'status' => $status,
        'title' => $title,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

try{
    $validacion = $mainModel->validarSesion();
    if($validacion['error']){
        respuestaDocumento('error', 'Error de sesión', $validacion['mensaje']);
    }

    $documento_id = (int)($_POST['documento_id'] ?? 0);
    $nombre = trim($mainModel->cleanString($_POST['nombre'] ?? ''));

    if($nombre === ''){
        respuestaDocumento('error', 'Dato requerido', 'Ingrese el nombre del documento.');
    }

    if(mb_strlen($nombre, 'UTF-8') > 30){
        respuestaDocumento('error', 'Nombre demasiado largo', 'El nombre del documento no puede superar los 30 caracteres.');
    }

    $conexion = $mainModel->connection();

    $sqlDuplicado = "SELECT documento_id FROM documento WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))";
    if($documento_id > 0){
        $sqlDuplicado .= " AND documento_id <> ?";
    }
    $sqlDuplicado .= " LIMIT 1";

    $stmtDup = $conexion->prepare($sqlDuplicado);
    if(!$stmtDup){
        throw new Exception($conexion->error);
    }

    if($documento_id > 0){
        $stmtDup->bind_param('si', $nombre, $documento_id);
    }else{
        $stmtDup->bind_param('s', $nombre);
    }

    $stmtDup->execute();
    $duplicado = $stmtDup->get_result();
    $stmtDup->close();

    if($duplicado && $duplicado->num_rows > 0){
        respuestaDocumento('error', 'Documento duplicado', 'Ya existe un documento con ese nombre.');
    }

    if($documento_id > 0){
        $stmt = $conexion->prepare("UPDATE documento SET nombre = ? WHERE documento_id = ?");
        if(!$stmt){
            throw new Exception($conexion->error);
        }
        $stmt->bind_param('si', $nombre, $documento_id);
        if(!$stmt->execute()){
            throw new Exception($stmt->error);
        }
        $stmt->close();
        respuestaDocumento('success', 'Documento actualizado', 'El documento se actualizó correctamente.');
    }

    if(!$conexion->query("LOCK TABLES documento WRITE")){
        throw new Exception($conexion->error);
    }

    try{
        $resultadoId = $conexion->query("SELECT COALESCE(MAX(documento_id), 0) + 1 AS nuevo_id FROM documento");
        if(!$resultadoId){
            throw new Exception($conexion->error);
        }
        $nuevo_id = (int)$resultadoId->fetch_assoc()['nuevo_id'];
        $estado = 1;

        $stmt = $conexion->prepare("INSERT INTO documento (documento_id, nombre, estado) VALUES (?, ?, ?)");
        if(!$stmt){
            throw new Exception($conexion->error);
        }
        $stmt->bind_param('isi', $nuevo_id, $nombre, $estado);
        if(!$stmt->execute()){
            throw new Exception($stmt->error);
        }
        $stmt->close();
    }finally{
        $conexion->query("UNLOCK TABLES");
    }

    respuestaDocumento('success', 'Documento registrado', 'El documento se registró y quedó activo para crear secuencias.');
}catch(Throwable $e){
    error_log('Error guardarDocumentoSecuenciaAjax: '.$e->getMessage());
    respuestaDocumento('error', 'Error', 'No se pudo guardar el documento.');
}
