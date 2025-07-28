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
    if(!isset($_SESSION['user_sd'])) { 
        session_start(['name'=>'SD']); 
    }
    
    // Validar sesión
    $validacion = mainModel::validarSesion();
    if ($validacion['error']) {
        return $response = [
            'type' => 'error',
            'title' => "Error de sesión",
            'message' => $validacion['mensaje'],
            'factura_id' => null,
            'total' => 0,
            'puntos_generados' => 0,
            'estado' => false
        ];
    }

    $usuario = $_SESSION['colaborador_id_sd'];

    // Validar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos');
    }
    
    // Validar datos requeridos
    $requiredFields = ['clienteId', 'vendedorId', 'tipoFactura', 'productos'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Campo requerido faltante: $field");
        }
    }
    
    if (empty($data['productos']) || !is_array($data['productos'])) {
        throw new Exception('No hay productos para facturar');
    }
    
    // Obtener número de factura (aquí ya se actualiza la secuencia)
    $empresa_id = 1;
    $documento_id = $data['tipoFactura'] == 1 ? 1 : 2;
    $numeroFactura = $mainModel->obtenerNumeroFacturaSecuencia($empresa_id, $documento_id);
    
    if ($numeroFactura['error']) {
        throw new Exception($numeroFactura['mensaje']);
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
    
    // 1. Obtener el próximo facturas_id disponible
    $queryMaxId = "SELECT MAX(facturas_id) as max_id FROM facturas";
    $resultMax = $mainModel->ejecutar_consulta_simple($queryMaxId);
    $nextId = 1;
    
    if ($resultMax && $resultMax->num_rows > 0) {
        $row = $resultMax->fetch_assoc();
        $nextId = $row['max_id'] + 1;
    }

    // 2. Insertar la factura con el ID manual
    $query = "INSERT INTO facturas (
        facturas_id,
        clientes_id, 
        colaboradores_id, 
        secuencia_facturacion_id, 
        number, 
        fecha, 
        importe, 
        notas, 
        tipo_factura, 
        estado,
        usuario,
        empresa_id,
        fecha_registro,
        fecha_dolar
    ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, 1, ?, ?, NOW(), NOW())";
    
    $params = [
        $nextId,
        $data['clienteId'],
        $data['vendedorId'],
        $numeroFactura['data']['secuencia_facturacion_id'],
        $mainModel->cleanString($numeroFactura['data']['numero']),
        $total,
        isset($data['notas']) ? $mainModel->cleanString($data['notas']) : '',
        $mainModel->cleanString($data['tipoFactura']),
        $usuario,
        $empresa_id
    ];
    
    $result = $mainModel->ejecutar_consulta_simple_preparada($query, "iiiisdssii", $params);
    
    if (!$result) {
        throw new Exception('Error al insertar la factura: ' . $mainModel->connection()->error);
    }
    
    $facturaId = $nextId;
    
    // 3. Insertar los productos de la factura
    $queryDetalle = "INSERT INTO facturas_detalles (
        facturas_id, 
        productos_id, 
        cantidad, 
        precio, 
        descuento, 
        isv_valor, 
        medida
    ) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    foreach ($data['productos'] as $producto) {
        $paramsDetalle = [
            $facturaId,
            $producto['productoId'],
            $producto['cantidad'],
            $producto['precio'],
            $producto['descuento'] ?? 0,
            $producto['isv'] ?? 0,
            'UN'
        ];
        
        $resultDetalle = $mainModel->ejecutar_consulta_simple_preparada($queryDetalle, "iiiddds", $paramsDetalle);
        
        if (!$resultDetalle) {
            throw new Exception('Error al insertar el detalle de factura: ' . $mainModel->connection()->error);
        }
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
                throw new Exception('Error al registrar los puntos: ' . $mainModel->connection()->error);
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
        
        // Registrar factura fallida si tenemos el número
        if (isset($numeroFactura['data']['numero'])) {
            $queryFallida = "INSERT INTO secuencia_factura_fallida (empresa_id, documento_id, numero) VALUES (?, ?, ?)";
            $mainModel->ejecutar_consulta_simple_preparada($queryFallida, "iii", [
                $empresa_id,
                $documento_id,
                $numeroFactura['data']['numero']
            ]);
        }
    }
    
    $response = [
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ];
}

echo json_encode($response);