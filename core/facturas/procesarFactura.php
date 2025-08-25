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
        echo json_encode([
            'type' => 'error',
            'title' => "Error de sesión",
            'message' => $validacion['mensaje'],
            'factura_id' => null,
            'total' => 0,
            'puntos_generados' => 0,
            'estado' => false
        ]);
        exit;
    }

    $usuario = $_SESSION['colaborador_id_sd'];

    // Validar método
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

    $empresa_id = 1;
    $documento_id = ($data['tipoFactura'] == 1) ? 1 : 2; // 1=Contado, 2=Crédito

    $cn = $mainModel->connection();
    $cn->begin_transaction();

    // Obtener número de factura
    $numData = $mainModel->obtenerNumeroFacturaSecuencia($empresa_id, $documento_id);
    if ($numData['error']) {
        throw new Exception($numData['mensaje']);
    }

    $secuencia_id = $numData['data']['secuencia_facturacion_id'];
    $numero = $numData['data']['numero'];
    $prefijo = $numData['data']['prefijo'] ?? '';
    $relleno = intval($numData['data']['relleno'] ?? 0);

    // Calcular totales
    $subtotal = 0;
    $totalDescuento = 0;
    $totalIsv = 0;
    foreach ($data['productos'] as $producto) {
        $p = floatval($producto['precio']);
        $c = intval($producto['cantidad']);
        $d = floatval($producto['descuento'] ?? 0);
        $i = floatval($producto['isv'] ?? 0);

        $subtotal += $p * $c;
        $totalDescuento += $d;
        $totalIsv += $i * $c;
    }
    $total = ($subtotal - $totalDescuento) + $totalIsv;

    // Obtener apertura de caja activa
    $sqlApertura = "SELECT apertura_id 
                    FROM apertura 
                    WHERE colaboradores_id = ? 
                    AND empresa_id = ? 
                    AND estado = 1 
                    LIMIT 1";
    $stmt = $cn->prepare($sqlApertura);
    $stmt->bind_param("ii", $usuario, $empresa_id);
    $stmt->execute();
    $resApertura = $stmt->get_result();

    if ($resApertura->num_rows > 0) {
        $rowApertura = $resApertura->fetch_assoc();
        $apertura_id = intval($rowApertura['apertura_id']);
    } else {
        throw new Exception('No hay apertura de caja activa para registrar la factura.');
    }

    // Obtener correlativo para facturas_id
    $sqlCorrelativo = "SELECT IFNULL(MAX(facturas_id), 0) + 1 AS nuevo_id FROM facturas";
    $resCorrelativo = $cn->query($sqlCorrelativo);
    $rowCorrelativo = $resCorrelativo->fetch_assoc();
    $nuevo_facturas_id = intval($rowCorrelativo['nuevo_id']);

    // Insertar factura con correlativo manual - CORREGIDO
    $query = "INSERT INTO facturas (
        facturas_id,
        clientes_id,
        colaboradores_id,
        secuencia_facturacion_id,
        apertura_id,
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
    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, 1, ?, ?, NOW(), NOW())";

    $numeroStr = $numero; // Ajusta si quieres mostrar con prefijo/ceros

    $params = [
        $nuevo_facturas_id,                          // i → facturas_id
        $data['clienteId'],                          // i
        $data['vendedorId'],                         // i
        $secuencia_id,                               // i
        $apertura_id,                                // i
        intval($numeroStr),                          // i
        floatval($total),                            // d
        isset($data['notas']) ? $mainModel->cleanString($data['notas']) : '', // s
        intval($data['tipoFactura']),                // i
        intval($usuario),                            // i
        intval($empresa_id)                          // i
    ];

    // Cadena de tipos: 11 parámetros => iiiiiidsiiii
    $ok = $mainModel->ejecutar_consulta_simple_preparada($query, "iiiiidsiiii", $params);
    if (!$ok) {
        throw new Exception('Error al insertar la factura: ' . $cn->error);
    }

    $facturaId = $nuevo_facturas_id; // Usamos el que generamos arriba

    // Insertar detalles
    $queryDetalle = "INSERT INTO facturas_detalles (
        facturas_detalle_id,
        facturas_id,
        productos_id,
        cantidad,
        precio,
        isv_valor,
        descuento,
        medida
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    foreach ($data['productos'] as $producto) {
        $sqlDetalle = "SELECT IFNULL(MAX(facturas_detalle_id), 0) + 1 AS nuevo_id FROM facturas_detalles";
        $resDetalle = $cn->query($sqlDetalle);
        $rowDetalle = $resDetalle->fetch_assoc();
        $nuevo_detalle_id = intval($rowDetalle['nuevo_id']);

        // Obtener la medida del producto desde la base de datos
        $sqlMedida = "SELECT m.nombre 
                      FROM productos p 
                      INNER JOIN medida m ON p.medida_id = m.medida_id 
                      WHERE p.productos_id = ?";
        $stmtMedida = $cn->prepare($sqlMedida);
        $stmtMedida->bind_param("i", $producto['productoId']);
        $stmtMedida->execute();
        $resMedida = $stmtMedida->get_result();
        
        $medida = 'UN'; // Valor por defecto
        if ($resMedida->num_rows > 0) {
            $rowMedida = $resMedida->fetch_assoc();
            $medida = $rowMedida['nombre'];
        }

        $paramsDetalle = [
            $nuevo_detalle_id,                        // i
            intval($facturaId),                       // i
            intval($producto['productoId']),          // i
            intval($producto['cantidad']),            // i
            floatval($producto['precio']),            // d
            floatval($producto['isv'] ?? 0),          // d
            floatval($producto['descuento'] ?? 0),    // d
            $medida                                   // s (obtenida de la BD)
        ];

        // Cadena de tipos: 8 parámetros => iiiiddds
        $okDetalle = $mainModel->ejecutar_consulta_simple_preparada($queryDetalle, "iiiiddds", $paramsDetalle);
        if (!$okDetalle) {
            throw new Exception('Error al insertar el detalle de factura: ' . $cn->error);
        }
    }

    // Programa de puntos
    $puntosGenerados = 0;
    $qProg = "SELECT tipo_calculo, monto, porcentaje FROM programa_puntos WHERE activo = 1 LIMIT 1";
    $programa = $mainModel->ejecutar_consulta_simple($qProg);
    if ($programa && $programa->num_rows > 0) {
        $row = $programa->fetch_assoc();
        if ($row['tipo_calculo'] == 'monto_fijo') {
            $puntosGenerados = ($row['monto'] > 0) ? floor($total / $row['monto']) : 0;
        } else {
            $puntosGenerados = floor(($total * $row['porcentaje']) / 100);
        }
        if ($puntosGenerados > 0) {
            $qPts = "INSERT INTO cliente_puntos (cliente_id, factura_id, puntos, fecha_creacion, fecha_expiracion, estado) 
                     VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 1)";
            $okPts = $mainModel->ejecutar_consulta_simple_preparada($qPts, "iii", [$data['clienteId'], $facturaId, $puntosGenerados]);
            if (!$okPts) {
                throw new Exception('Error al registrar los puntos: ' . $cn->error);
            }
        }
    }

    // Commit
    $cn->commit();

    echo json_encode([
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Factura procesada correctamente',
        'factura_id' => $facturaId,
        'total' => $total,
        'puntos_generados' => $puntosGenerados,
        'estado' => true,
        'numero_visible' => $prefijo . str_pad($numero, $relleno, '0', STR_PAD_LEFT)
    ]);
    exit;

} catch (Exception $e) {
    if ($mainModel->connection()) {
        $mainModel->connection()->rollback();

        // Registrar número fallido si lo teníamos
        if (isset($numData['data']['numero'])) {
            $empresa_id = $empresa_id ?? 1;
            $documento_id = $documento_id ?? 1;
            $qFallida = "INSERT INTO secuencia_factura_fallida (empresa_id, documento_id, numero) VALUES (?, ?, ?)";
            $mainModel->ejecutar_consulta_simple_preparada($qFallida, "iii", [
                $empresa_id,
                $documento_id,
                $numData['data']['numero']
            ]);
        }
    }

    echo json_encode([
        'type' => 'error',
        'title' => 'Error',
        'message' => 'Error: ' . $e->getMessage(),
        'estado' => false
    ]);
    exit;
}