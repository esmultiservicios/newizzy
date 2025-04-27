<?php
// core/programaPuntos/llenarDataTableProgramaPuntos.php
$peticionAjax = true;

// Usar __DIR__ para rutas absolutas
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$insMainModel = new mainModel();
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

$query = "SELECT * FROM programa_puntos";
$params = array();
$types = "";

if ($estado !== '') {
    $query .= " WHERE activo = ?";
    $params[] = intval($estado);
    $types .= "i";
}

$query .= " ORDER BY fecha_creacion DESC";

try {
    $result = $insMainModel->ejecutar_consulta_simple_preparada($query, $types, $params);
    
    $data = array();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $arreglo = array(
        "echo" => 1,
        "totalrecords" => count($data),
        "totaldisplayrecords" => count($data),
        "data" => $data
    );
    
    echo json_encode($arreglo);
    
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}