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
    $conexion = $mainModel->connection();
    $conexion->begin_transaction();
    
    // 1. Obtener total de la factura
    $queryFactura = "SELECT total, estado_pago FROM facturas WHERE facturas_id = ? FOR UPDATE";
    $stmtFactura = $conexion->prepare($queryFactura);
    $stmtFactura->bind_param("i", $data['facturaId']);
    
    if (!$stmtFactura->execute()) {
        throw new Exception('Error al consultar la factura: ' . $stmtFactura->error);
    }
    
    $result = $stmtFactura->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Factura no encontrada');
    }
    
    $row = $result->fetch_assoc();
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
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iddd", $data['facturaId'], $efectivo, $tarjeta, $cambio);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al registrar el pago: ' . $stmt->error);
    }
    
    $pagoId = $conexion->insert_id;
    $stmt->close();
    
    // 3. Registrar detalles de pago si es con tarjeta
    if ($tarjeta > 0) {
        $queryDetalle = "INSERT INTO pagos_detalles (
            pagos_id, 
            tipo_pago_id, 
            banco_id, 
            efectivo, 
            descripcion1
        ) VALUES (?, 2, 0, ?, 'Pago con tarjeta')";
        
        $stmtDetalle = $conexion->prepare($queryDetalle);
        $stmtDetalle->bind_param("id", $pagoId, $tarjeta);
        
        if (!$stmtDetalle->execute()) {
            throw new Exception('Error al registrar detalle de pago: ' . $stmtDetalle->error);
        }
        $stmtDetalle->close();
    }
    
    // 4. Actualizar estado de la factura a pagada
    $queryUpdateFactura = "UPDATE facturas 
                          SET estado_pago = 1, 
                              fecha_pago = NOW() 
                          WHERE facturas_id = ?";
    
    $stmtUpdate = $conexion->prepare($queryUpdateFactura);
    $stmtUpdate->bind_param("i", $data['facturaId']);
    
    if (!$stmtUpdate->execute()) {
        throw new Exception('Error al actualizar la factura: ' . $stmtUpdate->error);
    }
    $stmtUpdate->close();
    
    // Confirmar transacción
    $conexion->commit();
    
    $response = [
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Pago registrado correctamente',
        'pago_id' => $pagoId,
        'estado' => true
    ];
    
} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    
    $response = [
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ];
}

echo json_encode($response);