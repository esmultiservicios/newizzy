<?php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

$response = [
    'type' => 'error',
    'title' => 'Error',
    'message' => 'Error desconocido',
    'factura_id' => null,
    'total' => 0,
    'puntos_generados' => 0,
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
    $requiredFields = ['clienteId', 'vendedorId', 'tipoFactura', 'productos', 'secuenciaId', 'prefijo', 'numero'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    if (empty($data['productos']) || !is_array($data['productos'])) {
        throw new Exception('No hay productos para facturar');
    }
    
    // Iniciar transacción
    $mainModel->connection()->begin_transaction();
    
    // Calcular totales
    $subtotal = 0;
    $totalDescuento = 0;
    $totalIsv = 0;
    
    foreach ($data['productos'] as $producto) {
        $subtotal += $producto['precio'] * $producto['cantidad'];
        $totalDescuento += $producto['descuento'] ?? 0;
        $totalIsv += ($producto['isv'] ?? 0) * $producto['cantidad'];
    }
    
    $total = ($subtotal - $totalDescuento) + $totalIsv;
    
    // 1. Insertar la factura
    $query = "INSERT INTO facturas (
        clientes_id, 
        colaboradores_id, 
        secuencia_facturacion_id, 
        prefijo, 
        numero_factura, 
        fecha, 
        subtotal, 
        descuento, 
        isv, 
        total, 
        tipo_factura, 
        notas, 
        estado,
        estado_pago
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 1, 0)";
    
    $params = [
        $data['clienteId'],
        $data['vendedorId'],
        $data['secuenciaId'],
        $mainModel->cleanString($data['prefijo']),
        $mainModel->cleanString($data['numero']),
        $subtotal - $totalDescuento,
        $totalDescuento,
        $totalIsv,
        $total,
        $mainModel->cleanString($data['tipoFactura']),
        isset($data['notas']) ? $mainModel->cleanString($data['notas']) : null
    ];
    
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "iisssddddss", $params);
    
    if (!$result) {
        throw new Exception('Error al insertar la factura');
    }
    
    $facturaId = $mainModel->connection()->insert_id;
    
    // 2. Insertar los productos de la factura
    $queryDetalle = "INSERT INTO facturas_detalle (
        facturas_id, 
        productos_id, 
        cantidad, 
        precio_unitario, 
        descuento, 
        isv, 
        subtotal
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    foreach ($data['productos'] as $producto) {
        $productoSubtotal = ($producto['precio'] * $producto['cantidad']) - ($producto['descuento'] ?? 0);
        
        $paramsDetalle = [
            $facturaId,
            $producto['productoId'],
            $producto['cantidad'],
            $producto['precio'],
            $producto['descuento'] ?? 0,
            $producto['isv'] ?? 0,
            $productoSubtotal
        ];
        
        $resultDetalle = $mainModel->ejecutar_consulta_simple_preparada($queryDetalle, "iiidddd", $paramsDetalle);
        
        if (!$resultDetalle) {
            throw new Exception('Error al insertar el detalle de factura');
        }
    }
    
    // 3. Actualizar la secuencia de facturación
    $querySecuencia = "UPDATE secuencia_facturacion 
                      SET siguiente = siguiente + 1 
                      WHERE secuencia_facturacion_id = ?";
    
    $resultSecuencia = $mainModel->ejecutar_consulta_simple_preparada($querySecuencia, "i", [$data['secuenciaId']]);
    
    if (!$resultSecuencia) {
        throw new Exception('Error al actualizar la secuencia');
    }
    
    // 4. Calcular puntos si el programa de puntos está activo
    $puntosGenerados = 0;
    $queryPrograma = "SELECT tipo_calculo, monto, porcentaje FROM programa_puntos WHERE activo = 1 LIMIT 1";
    $programa = $mainModel->ejecutar_consulta_simple($queryPrograma);
    
    if ($programa && $programa->num_rows > 0) {
        $row = $programa->fetch_assoc();
        if ($row['tipo_calculo'] == 'monto_fijo') {
            $puntosGenerados = floor($total / $row['monto']);
        } else {
            $puntosGenerados = floor(($total * $row['porcentaje']) / 100);
        }
        
        if ($puntosGenerados > 0) {
            $queryPuntos = "INSERT INTO cliente_puntos (cliente_id, factura_id, puntos, fecha_creacion, fecha_expiracion, estado) 
                           VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 1)";
            $resultPuntos = $mainModel->ejecutar_consulta_simple_preparada($queryPuntos, "iii", [$data['clienteId'], $facturaId, $puntosGenerados]);
            
            if (!$resultPuntos) {
                throw new Exception('Error al registrar los puntos');
            }
        }
    }
    
    // Confirmar transacción
    $mainModel->connection()->commit();
    
    $response = [
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Factura procesada correctamente',
        'factura_id' => $facturaId,
        'total' => $total,
        'puntos_generados' => $puntosGenerados,
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