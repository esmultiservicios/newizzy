<?php
//facturaMovil.php
$peticionAjax = true;
require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

$mainModel = new mainModel();

// Función para obtener clientes
if (isset($_GET['getClientes'])) {
    $query = "SELECT clientes_id, nombre, rtn FROM clientes WHERE estado = 1 ORDER BY nombre";
    $result = $mainModel->ejecutar_consulta_simple($query);
    
    $clientes = [];
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
    
    echo json_encode($clientes);
    exit;
}

// Función para obtener vendedores
if (isset($_GET['getVendedores'])) {
    $query = "SELECT colaboradores_id, nombre FROM colaboradores WHERE estado = 1 ORDER BY nombre";
    $result = $mainModel->ejecutar_consulta_simple($query);
    
    $vendedores = [];
    while ($row = $result->fetch_assoc()) {
        $vendedores[] = $row;
    }
    
    echo json_encode($vendedores);
    exit;
}

// Función para obtener productos
if (isset($_GET['getProductos'])) {
    $query = "SELECT productos_id, nombre, precio_venta, isv_venta FROM productos WHERE estado = 1 ORDER BY nombre";
    $result = $mainModel->ejecutar_consulta_simple($query);
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    
    echo json_encode($productos);
    exit;
}

// Función para obtener secuencia de factura
if (isset($_GET['getSecuenciaFactura'])) {
    $query = "SELECT * FROM secuencia_facturacion WHERE activo = 1 AND documento_id = 1 LIMIT 1";
    $result = $mainModel->ejecutar_consulta_simple($query);
    
    if ($result->num_rows > 0) {
        $secuencia = $result->fetch_assoc();
        echo json_encode($secuencia);
    } else {
        echo json_encode(['error' => 'No hay secuencia de facturación activa']);
    }
    exit;
}

// Función para procesar factura
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Validar datos
    if (empty($data['clienteId'])) {
        echo json_encode(['success' => false, 'message' => 'Cliente es requerido']);
        exit;
    }
    
    if (empty($data['vendedorId'])) {
        echo json_encode(['success' => false, 'message' => 'Vendedor es requerido']);
        exit;
    }
    
    if (empty($data['productos']) || !is_array($data['productos']) || count($data['productos']) === 0) {
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos un producto']);
        exit;
    }
    
    // Obtener apertura actual
    $query_apertura = "SELECT apertura_id FROM apertura_cierre WHERE estado = 1 AND empresa_id = 1 LIMIT 1";
    $result_apertura = $mainModel->ejecutar_consulta_simple($query_apertura);
    
    if ($result_apertura->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'No hay caja aperturada']);
        exit;
    }
    
    $apertura = $result_apertura->fetch_assoc();
    $apertura_id = $apertura['apertura_id'];
    
    // Calcular totales
    $subtotal = 0;
    $total_isv = 0;
    $total_descuento = 0;
    
    foreach ($data['productos'] as $producto) {
        $precio = floatval($producto['precio']);
        $cantidad = intval($producto['cantidad']);
        $descuento = floatval($producto['descuento']);
        $isv = floatval($producto['isv']);
        
        $subtotal += ($precio * $cantidad) - $descuento;
        $total_isv += $isv * $cantidad;
        $total_descuento += $descuento;
    }
    
    $total = $subtotal + $total_isv;
    
    // Registrar factura
    $fecha_actual = date('Y-m-d');
    $fecha_registro = date('Y-m-d H:i:s');
    
    $query_factura = "INSERT INTO facturas (
        clientes_id, secuencia_facturacion_id, apertura_id, number, tipo_factura, 
        colaboradores_id, importe, notas, fecha, estado, usuario, empresa_id, 
        fecha_registro, fecha_dolar
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params_factura = [
        $data['clienteId'],
        $data['secuenciaId'],
        $apertura_id,
        $data['numero'],
        $data['tipoFactura'],
        $data['vendedorId'],
        $total,
        $data['notas'] ?? '',
        $fecha_actual,
        $data['tipoFactura'] == 1 ? 2 : 3, // 2=Pagada, 3=Crédito
        1, // usuario
        1, // empresa_id
        $fecha_registro,
        $fecha_actual
    ];
    
    $types_factura = "iiiisisdssiiss";
    
    try {
        $mainModel->connection()->begin_transaction();
        
        // Insertar factura
        $result_factura = $mainModel->ejecutar_consulta_simple_preparada($query_factura, $types_factura, $params_factura);
        
        if (!$result_factura) {
            throw new Exception('Error al registrar la factura');
        }
        
        $factura_id = $mainModel->connection()->insert_id;
        
        // Insertar detalles de factura
        foreach ($data['productos'] as $producto) {
            $query_detalle = "INSERT INTO facturas_detalles (
                facturas_id, productos_id, cantidad, precio, isv_valor, descuento, medida
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params_detalle = [
                $factura_id,
                $producto['productoId'],
                $producto['cantidad'],
                $producto['precio'],
                $producto['isv'],
                $producto['descuento'],
                'Und' // medida
            ];
            
            $types_detalle = "iiiddds";
            
            $result_detalle = $mainModel->ejecutar_consulta_simple_preparada($query_detalle, $types_detalle, $params_detalle);
            
            if (!$result_detalle) {
                throw new Exception('Error al registrar el detalle de factura');
            }
        }
        
        // Actualizar secuencia de facturación
        $query_update_secuencia = "UPDATE secuencia_facturacion SET siguiente = siguiente + incremento WHERE secuencia_facturacion_id = ?";
        $result_update = $mainModel->ejecutar_consulta_simple_preparada($query_update_secuencia, "i", [$data['secuenciaId']]);
        
        if (!$result_update) {
            throw new Exception('Error al actualizar la secuencia de facturación');
        }
        
        $mainModel->connection()->commit();
        
        echo json_encode([
            'success' => true,
            'factura_id' => $factura_id,
            'numero_factura' => $data['prefijo'] . str_pad($data['numero'], $data['relleno'], '0', STR_PAD_LEFT),
            'total' => $total
        ]);
    } catch (Exception $e) {
        $mainModel->connection()->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Función para registrar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrarPago'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validar datos
    if (empty($data['facturaId'])) {
        echo json_encode(['success' => false, 'message' => 'ID de factura es requerido']);
        exit;
    }
    
    if (!isset($data['efectivo']) || !isset($data['tarjeta']) || !isset($data['cambio'])) {
        echo json_encode(['success' => false, 'message' => 'Datos de pago incompletos']);
        exit;
    }
    
    $efectivo = floatval($data['efectivo']);
    $tarjeta = floatval($data['tarjeta']);
    $cambio = floatval($data['cambio']);
    $total_pago = $efectivo + $tarjeta - $cambio;
    
    // Obtener total de factura
    $query_factura = "SELECT importe FROM facturas WHERE facturas_id = ?";
    $result_factura = $mainModel->ejecutar_consulta_simple_preparada($query_factura, "i", [$data['facturaId']]);
    
    if ($result_factura->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }
    
    $factura = $result_factura->fetch_assoc();
    $total_factura = floatval($factura['importe']);
    
    if (abs($total_pago - $total_factura) > 0.01) {
        echo json_encode(['success' => false, 'message' => 'El pago no coincide con el total de la factura']);
        exit;
    }
    
    // Registrar pago
    $fecha_actual = date('Y-m-d');
    $fecha_registro = date('Y-m-d H:i:s');
    
    $query_pago = "INSERT INTO pagos (
        facturas_id, tipo_pago, fecha, importe, efectivo, cambio, tarjeta, 
        usuario, estado, empresa_id, fecha_registro
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params_pago = [
        $data['facturaId'],
        1, // tipo_pago: 1=Contado
        $fecha_actual,
        $total_factura,
        $efectivo,
        $cambio,
        $tarjeta,
        1, // usuario
        1, // estado: 1=Pagado
        1, // empresa_id
        $fecha_registro
    ];
    
    $types_pago = "isdddddiiss";
    
    try {
        $mainModel->connection()->begin_transaction();
        
        // Insertar pago
        $result_pago = $mainModel->ejecutar_consulta_simple_preparada($query_pago, $types_pago, $params_pago);
        
        if (!$result_pago) {
            throw new Exception('Error al registrar el pago');
        }
        
        $pago_id = $mainModel->connection()->insert_id;
        
        // Insertar detalles de pago (si es necesario)
        // Aquí podrías agregar más detalles sobre el pago si es necesario
        
        // Actualizar estado de factura
        $query_update_factura = "UPDATE facturas SET estado = 2 WHERE facturas_id = ?";
        $result_update = $mainModel->ejecutar_consulta_simple_preparada($query_update_factura, "i", [$data['facturaId']]);
        
        if (!$result_update) {
            throw new Exception('Error al actualizar el estado de la factura');
        }
        
        $mainModel->connection()->commit();
        
        echo json_encode(['success' => true, 'pago_id' => $pago_id]);
    } catch (Exception $e) {
        $mainModel->connection()->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Si no es ninguna petición válida
echo json_encode(['success' => false, 'message' => 'Petición no válida']);