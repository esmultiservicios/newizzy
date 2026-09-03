<?php
// Ubicación: core/secuencias/listarDocumentosSecuencia.php
$peticionAjax = true;
require_once __DIR__.'/../configGenerales.php';
require_once __DIR__.'/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$mainModel = new mainModel();

try{
    $validacion = $mainModel->validarSesion();
    if($validacion['error']){
        echo json_encode([
            'success' => false,
            'message' => $validacion['mensaje']
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $conexion = $mainModel->connection();
    $sql = "SELECT
                d.documento_id,
                d.nombre,
                d.estado,
                COUNT(s.secuencia_facturacion_id) AS secuencias_total,
                SUM(CASE WHEN s.activo = 1 THEN 1 ELSE 0 END) AS secuencias_activas
            FROM documento d
            LEFT JOIN secuencia_facturacion s ON s.documento_id = d.documento_id
            GROUP BY d.documento_id, d.nombre, d.estado
            ORDER BY d.nombre ASC";

    $resultado = $conexion->query($sql);
    if(!$resultado){
        throw new Exception($conexion->error);
    }

    $data = [];
    while($row = $resultado->fetch_assoc()){
        $row['documento_id'] = (int)$row['documento_id'];
        $row['estado'] = (int)$row['estado'];
        $row['secuencias_total'] = (int)$row['secuencias_total'];
        $row['secuencias_activas'] = (int)$row['secuencias_activas'];
        $data[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){
    error_log('Error listarDocumentosSecuencia: '.$e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo cargar el catálogo de documentos.'
    ], JSON_UNESCAPED_UNICODE);
}
