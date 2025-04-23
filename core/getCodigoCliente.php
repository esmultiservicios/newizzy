<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();
$db = $GLOBALS['db'];

// Respuesta estructurada
$response = [
    'success' => false,
    'codigo_cliente' => null,
    'is_main_db' => ($db === DB_MAIN),
    'error' => null
];

try {
    if ($db !== DB_MAIN) {
        $mysqli = $insMainModel->connectionDBLocal($db);
        
        // Consulta más robusta con JOIN si es necesario
        $query = "SELECT sc.codigo_cliente 
                 FROM server_customers sc
                 WHERE sc.db = ? AND sc.codigo_cliente IS NOT NULL
                 LIMIT 1";
        
        $stmt = $mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $mysqli->error);
        }
        
        $stmt->bind_param("s", $db);
        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar consulta: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $response = [
                'success' => true,
                'codigo_cliente' => $row['codigo_cliente'],
                'is_main_db' => false
            ];
        } else {
            $response['error'] = "No se encontró código de cliente para esta base de datos";
        }
        
        $stmt->close();
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Error en getCodigoCliente: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);