<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$response = [
    'type' => 'error',
    'title' => 'Error',
    'message' => 'Error desconocido',
    'pago_id' => null,
    'estado' => false
];

try {
    // Validar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar datos requeridos
    $requiredFields = ['facturaId', 'efectivo', 'tarjeta', 'cambio'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    // Validar montos
    $efectivo = floatval($data['efectivo']);
    $tarjeta = floatval($data['tarjeta']);
    $cambio = floatval($data['cambio']);
    
    if ($efectivo < 0 || $tarjeta < 0 || $cambio < 0) {
        throw new Exception('Los montos no pueden ser negativos');
    }
    
    // Iniciar transacción
    $mainModel->connection()->begin_transaction();
    
    // 1. Obtener total de la factura
    $queryFactura = "SELECT total, estado_pago FROM facturas WHERE facturas_id = ?";
    $factura = $mainModel->ejecutar_consulta_simple_preparada($queryFactura, "i", [$data['facturaId']]);
    
    if (!$factura || $factura->num_rows === 0) {
        throw new Exception('Factura no encontrada');
    }
    
    $row = $factura->fetch_assoc();
    $totalFactura = $row['total'];
    
    if ($row['estado_pago'] == 1) {
        throw new Exception('La factura ya está pagada');
    }
    
    // Validar que el pago cubra el total
    $totalPago = $efectivo + $tarjeta;
    if ($totalPago < $totalFactura) {
        throw new Exception('El pago no cubre el total de la factura');
    }
    
    // 2. Registrar el pago
    $query = "INSERT INTO pagos (
        facturas_id, 
        fecha, 
        efectivo, 
        tarjeta, 
        cambio, 
        estado
    ) VALUES (?, NOW(), ?, ?, ?, 1)";
    
    $params = [
        $data['facturaId'],
        $efectivo,
        $tarjeta,
        $cambio
    ];
    
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "iddd", $params);
    
    if (!$result) {
        throw new Exception('Error al registrar el pago');
    }
    
    $pagoId = $mainModel->connection()->insert_id;
    
    // 3. Actualizar estado de la factura a pagada
    $queryUpdateFactura = "UPDATE facturas 
                          SET estado_pago = 1, 
                              fecha_pago = NOW() 
                          WHERE facturas_id = ?";
    
    $resultUpdate = $mainModel->ejecutar_consulta_simple_preparada($queryUpdateFactura, "i", [$data['facturaId']]);
    
    if (!$resultUpdate) {
        throw new Exception('Error al actualizar la factura');
    }
    
    // Confirmar transacción
    $mainModel->connection()->commit();
    
    $response = [
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Pago registrado correctamente',
        'pago_id' => $pagoId,
        'estado' => true
    ];
    
} catch (Exception $e) {
    if ($mainModel->connection()) {
        $mainModel->connection()->rollback();
    }
    
    $response = [
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ];
}

echo json_encode($response);