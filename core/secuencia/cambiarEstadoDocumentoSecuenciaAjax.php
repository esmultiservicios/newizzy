<?php
// Ubicación: core/secuencias/cambiarEstadoDocumentoSecuenciaAjax.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

function respuestaEstadoDocumento($status, $title, $message){
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
        respuestaEstadoDocumento('error', 'Error de sesión', $validacion['mensaje']);
    }

    $documento_id = (int)($_POST['documento_id'] ?? 0);
    $estado = (int)($_POST['estado'] ?? 0);

    if($documento_id <= 0 || !in_array($estado, [1, 2], true)){
        respuestaEstadoDocumento('error', 'Datos no válidos', 'No se pudo identificar el documento o el estado solicitado.');
    }

    $conexion = $mainModel->connection();

    $stmtDoc = $conexion->prepare("SELECT documento_id, nombre, estado FROM documento WHERE documento_id = ? LIMIT 1");
    if(!$stmtDoc){
        throw new Exception($conexion->error);
    }
    $stmtDoc->bind_param('i', $documento_id);
    $stmtDoc->execute();
    $doc = $stmtDoc->get_result()->fetch_assoc();
    $stmtDoc->close();

    if(!$doc){
        respuestaEstadoDocumento('error', 'Documento no encontrado', 'El documento seleccionado ya no existe.');
    }

    if($estado === 2){
        $stmtActivas = $conexion->prepare("SELECT COUNT(*) AS total FROM secuencia_facturacion WHERE documento_id = ? AND activo = 1");
        if(!$stmtActivas){
            throw new Exception($conexion->error);
        }
        $stmtActivas->bind_param('i', $documento_id);
        $stmtActivas->execute();
        $rowActivas = $stmtActivas->get_result()->fetch_assoc();
        $stmtActivas->close();

        if((int)$rowActivas['total'] > 0){
            respuestaEstadoDocumento(
                'error',
                'Documento en uso',
                'No puede desactivar este documento porque tiene una secuencia activa. Desactive primero la secuencia asociada.'
            );
        }
    }

    $stmt = $conexion->prepare("UPDATE documento SET estado = ? WHERE documento_id = ?");
    if(!$stmt){
        throw new Exception($conexion->error);
    }
    $stmt->bind_param('ii', $estado, $documento_id);
    if(!$stmt->execute()){
        throw new Exception($stmt->error);
    }
    $stmt->close();

    respuestaEstadoDocumento(
        'success',
        $estado === 1 ? 'Documento activado' : 'Documento desactivado',
        $estado === 1 ? 'El documento ya está disponible para crear secuencias.' : 'El documento fue desactivado correctamente.'
    );
}catch(Throwable $e){
    error_log('Error cambiarEstadoDocumentoSecuenciaAjax: '.$e->getMessage());
    respuestaEstadoDocumento('error', 'Error', 'No se pudo actualizar el estado del documento.');
}
