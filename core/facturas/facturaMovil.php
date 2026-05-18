<?php
// facturaMovil.php
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (!isset($_SESSION['user_sd'])) {
        session_start(['name' => 'SD']);
    } else {
        session_start();
    }
}

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mainModel = new mainModel();
$conn = $mainModel->connection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'estado' => false,
        'message' => 'No se pudo conectar a la base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getJsonInput() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!is_array($data)) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'JSON inválido'
        ]);
    }

    return $data;
}

function stmtFetchAll($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}

function stmtFetchOne($conn, $sql, $types = '', $params = []) {
    $rows = stmtFetchAll($conn, $sql, $types, $params);
    return count($rows) > 0 ? $rows[0] : null;
}

function stmtExec($conn, $sql, $types = '', $params = []) {
    $stmt = $conn->prepare($sql);

    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

function moneyValue($value) {
    return round(floatval($value), 2);
}

function intValue($value) {
    return intval($value);
}

function obtenerConfigActivar($conn, $accion, $default = 0) {
    $config = stmtFetchOne(
        $conn,
        "SELECT activar
         FROM config
         WHERE accion = ?
         LIMIT 1",
        "s",
        [$accion]
    );

    if (!$config) {
        return intval($default);
    }

    return intval($config['activar']);
}

function obtenerSiguienteIdManual($conn, $table, $column) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    if ($table === '' || $column === '') {
        throw new Exception('Tabla o columna inválida para generar ID');
    }

    $row = stmtFetchOne(
        $conn,
        "SELECT COALESCE(MAX(`$column`), 0) + 1 AS siguiente_id
         FROM `$table`"
    );

    return intval($row['siguiente_id'] ?? 1);
}

function obtenerSecuenciaActiva($conn, $documento_id, $forUpdate = false) {
    $sql = "SELECT *
            FROM secuencia_facturacion
            WHERE activo = 1
              AND documento_id = ?
            LIMIT 1";

    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }

    return stmtFetchOne($conn, $sql, "i", [$documento_id]);
}

function validarSecuencia($secuencia, $documento_id) {
    if (!$secuencia) {
        if ($documento_id == 4) {
            throw new Exception('No hay secuencia activa configurada para Proforma');
        }

        throw new Exception('No hay secuencia activa configurada para Factura');
    }

    $siguiente = intval($secuencia['siguiente'] ?? 0);
    $rangoFinal = intval($secuencia['rango_final'] ?? 0);

    if ($siguiente <= 0) {
        throw new Exception('La secuencia no tiene un número siguiente válido');
    }

    if ($rangoFinal > 0 && $siguiente > $rangoFinal) {
        throw new Exception('La secuencia llegó al rango final permitido');
    }

    if (!empty($secuencia['fecha_limite']) && $secuencia['fecha_limite'] !== '0000-00-00') {
        $hoy = date('Y-m-d');

        if ($secuencia['fecha_limite'] < $hoy) {
            throw new Exception('La fecha límite de la secuencia ya venció');
        }
    }
}

function numeroDocumentoFormateado($secuencia, $numero) {
    $prefijo = $secuencia['prefijo'] ?? '';
    $relleno = intval($secuencia['relleno'] ?? 0);

    if ($relleno > 0) {
        return $prefijo . str_pad($numero, $relleno, '0', STR_PAD_LEFT);
    }

    return $prefijo . $numero;
}

function obtenerSaldoActualProducto($conn, $producto_id, $empresa_id) {
    return stmtFetchOne(
        $conn,
        "SELECT movimientos_id, saldo, almacen_id, lote_id
         FROM movimientos
         WHERE productos_id = ?
           AND empresa_id = ?
         ORDER BY movimientos_id DESC
         LIMIT 1
         FOR UPDATE",
        "ii",
        [$producto_id, $empresa_id]
    );
}

function registrarMovimientoSalida($conn, $producto, $documento, $empresa_id, $cliente_id, $comentario) {
    $producto_id = intval($producto['productoId'] ?? $producto['productos_id'] ?? 0);
    $cantidad = intval($producto['cantidad'] ?? 0);

    if ($producto_id <= 0 || $cantidad <= 0) {
        throw new Exception('Producto o cantidad inválida para movimiento de inventario');
    }

    // Validar el tipo de producto desde BD.
    // tipo_producto_id = 2 se maneja como servicio y NO debe rebajar inventario.
    $productoBD = stmtFetchOne(
        $conn,
        "SELECT productos_id, nombre, tipo_producto_id
         FROM productos
         WHERE productos_id = ?
           AND empresa_id = ?
         LIMIT 1",
        "ii",
        [$producto_id, $empresa_id]
    );

    if (!$productoBD) {
        throw new Exception('Producto no encontrado para movimiento de inventario ID ' . $producto_id);
    }

    $tipo_producto_id = intval($productoBD['tipo_producto_id'] ?? 0);

    if ($tipo_producto_id === 2) {
        // Servicio: no tiene inventario, se omite movimiento.
        return;
    }

    $saldoInfo = obtenerSaldoActualProducto($conn, $producto_id, $empresa_id);

    if (!$saldoInfo) {
        throw new Exception('No hay saldo inicial en movimientos para el producto: ' . ($productoBD['nombre'] ?? $producto_id));
    }

    $saldoAnterior = intval($saldoInfo['saldo']);
    $saldoNuevo = $saldoAnterior - $cantidad;

    if ($saldoNuevo < 0) {
        throw new Exception(
            'Inventario insuficiente para el producto: ' .
            ($productoBD['nombre'] ?? $producto_id) .
            '. Saldo actual: ' . $saldoAnterior
        );
    }

    $almacen_id = intval($producto['almacenId'] ?? $producto['almacen_id'] ?? $saldoInfo['almacen_id'] ?? 0);
    $lote_id = intval($producto['loteId'] ?? $producto['lote_id'] ?? $saldoInfo['lote_id'] ?? 0);

    $movimientos_id = obtenerSiguienteIdManual($conn, 'movimientos', 'movimientos_id');
    $fecha_registro = date('Y-m-d H:i:s');

    stmtExec(
        $conn,
        "INSERT INTO movimientos (
            movimientos_id,
            productos_id,
            documento,
            cantidad_entrada,
            cantidad_salida,
            saldo,
            empresa_id,
            fecha_registro,
            clientes_id,
            comentario,
            almacen_id,
            lote_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "iisiiiisisii",
        [
            $movimientos_id,
            $producto_id,
            $documento,
            0,
            $cantidad,
            $saldoNuevo,
            $empresa_id,
            $fecha_registro,
            $cliente_id,
            $comentario,
            $almacen_id,
            $lote_id
        ]
    );
}

function obtenerIsvProductoDesdeBD($conn, $producto_id) {
    $producto = stmtFetchOne(
        $conn,
        "SELECT productos_id, isv_venta, isv1, isv2
         FROM productos
         WHERE productos_id = ?
         LIMIT 1",
        "i",
        [$producto_id]
    );

    if (!$producto) {
        throw new Exception('Producto no encontrado en base de datos ID ' . $producto_id);
    }

    $isvVenta = intval($producto['isv_venta'] ?? 0);
    $isv1 = intval($producto['isv1'] ?? 0);
    $isv2 = intval($producto['isv2'] ?? 0);

    if ($isvVenta === 1) {
        if ($isv2 === 1) {
            return [
                'isv_id_aplicado' => 2,
                'isv_rate_decimal' => 0.18,
                'isv_label' => 'ISV 18.00%'
            ];
        }

        if ($isv1 === 1) {
            return [
                'isv_id_aplicado' => 1,
                'isv_rate_decimal' => 0.15,
                'isv_label' => 'ISV 15.00%'
            ];
        }

        return [
            'isv_id_aplicado' => 1,
            'isv_rate_decimal' => 0.15,
            'isv_label' => 'ISV 15.00%'
        ];
    }

    return [
        'isv_id_aplicado' => 0,
        'isv_rate_decimal' => 0,
        'isv_label' => ''
    ];
}

function normalizarProductoDetalle($conn, $producto) {
    $producto_id = intValue($producto['productoId'] ?? $producto['productos_id'] ?? 0);
    $precio = moneyValue($producto['precio'] ?? 0);
    $cantidad = intValue($producto['cantidad'] ?? 0);
    $descuento = moneyValue($producto['descuento'] ?? 0);
    $medida = trim($producto['medida'] ?? 'Und');

    if ($medida === '') {
        $medida = 'Und';
    }

    if ($producto_id <= 0) {
        throw new Exception('Hay un producto inválido en el detalle');
    }

    if ($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a cero');
    }

    if ($precio <= 0) {
        throw new Exception('El precio debe ser mayor a cero');
    }

    if ($descuento < 0) {
        throw new Exception('El descuento no puede ser negativo');
    }

    $linea_bruta = moneyValue($precio * $cantidad);

    if ($descuento > $linea_bruta) {
        throw new Exception('El descuento no puede ser mayor al total de la línea');
    }

    $base_isv = moneyValue($linea_bruta - $descuento);
    if ($base_isv < 0) {
        $base_isv = 0;
    }

    $isvBD = obtenerIsvProductoDesdeBD($conn, $producto_id);
    $isv_id_aplicado = intval($isvBD['isv_id_aplicado'] ?? 0);
    $isv_rate = floatval($producto['isvRate'] ?? $producto['isv_rate_decimal'] ?? $isvBD['isv_rate_decimal']);

    if ($isv_rate < 0) {
        $isv_rate = 0;
    }

    if ($isv_rate > 1) {
        $isv_rate = $isv_rate / 100;
    }

    if ($isv_id_aplicado <= 0 || $isv_rate <= 0) {
        $isv_rate = 0;
        $isv_total_linea = 0;
        $isv_valor_unitario = 0;
    } else {
        // El ISV debe calcularse sobre la línea completa:
        // (precio * cantidad) - descuento.
        // Antes se calculaba por unidad y luego se guardaba solo una unidad en el detalle.
        $isv_total_linea = moneyValue($base_isv * $isv_rate);
        $isv_valor_unitario = $cantidad > 0 ? moneyValue($isv_total_linea / $cantidad) : 0;
    }

    $isv_valor = ($isv_id_aplicado === 1) ? $isv_total_linea : 0;
    $isv_valor1 = ($isv_id_aplicado === 2) ? $isv_total_linea : 0;

    return [
        'producto_id' => $producto_id,
        'precio' => $precio,
        'cantidad' => $cantidad,
        'descuento' => $descuento,
        'linea_bruta' => $linea_bruta,
        'base_isv' => $base_isv,
        'isv_id_aplicado' => $isv_id_aplicado,
        'isv_rate' => $isv_rate,
        'isv_valor_unitario' => $isv_valor_unitario,
        'isv_total_linea' => $isv_total_linea,
        'isv_valor' => $isv_valor,
        'isv_valor1' => $isv_valor1,
        'medida' => $medida,
        'producto_original' => $producto
    ];
}

function insertarPagoDetalle($conn, $pagos_id, $tipo_pago_id, $monto, $descripcion1 = '', $descripcion2 = '', $descripcion3 = '') {
    if ($monto <= 0) {
        return;
    }

    $pagos_detalles_id = obtenerSiguienteIdManual($conn, 'pagos_detalles', 'pagos_detalles_id');

    stmtExec(
        $conn,
        "INSERT INTO pagos_detalles (
            pagos_detalles_id,
            pagos_id,
            tipo_pago_id,
            banco_id,
            efectivo,
            descripcion1,
            descripcion2,
            descripcion3
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        "iiiidsss",
        [
            $pagos_detalles_id,
            $pagos_id,
            $tipo_pago_id,
            0,
            $monto,
            $descripcion1,
            $descripcion2,
            $descripcion3
        ]
    );
}

function registrarCuentaPorCobrarCliente($conn, $cliente_id, $factura_id, $fecha, $saldo, $estado, $tipo_factura, $usuario, $empresa_id, $fecha_registro) {
    $existe = stmtFetchOne(
        $conn,
        "SELECT cobrar_clientes_id
         FROM cobrar_clientes
         WHERE facturas_id = ?
           AND empresa_id = ?
         LIMIT 1",
        "ii",
        [$factura_id, $empresa_id]
    );

    if ($existe) {
        stmtExec(
            $conn,
            "UPDATE cobrar_clientes
             SET clientes_id = ?,
                 fecha = ?,
                 saldo = ?,
                 estado = ?,
                 tipo_factura = ?,
                 usuario = ?,
                 fecha_registro = ?
             WHERE cobrar_clientes_id = ?",
            "isdiiisi",
            [
                $cliente_id,
                $fecha,
                $saldo,
                $estado,
                $tipo_factura,
                $usuario,
                $fecha_registro,
                intval($existe['cobrar_clientes_id'])
            ]
        );

        return intval($existe['cobrar_clientes_id']);
    }

    $cobrar_clientes_id = obtenerSiguienteIdManual($conn, 'cobrar_clientes', 'cobrar_clientes_id');

    stmtExec(
        $conn,
        "INSERT INTO cobrar_clientes (
            cobrar_clientes_id,
            clientes_id,
            facturas_id,
            fecha,
            saldo,
            estado,
            tipo_factura,
            usuario,
            empresa_id,
            fecha_registro
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        "iiisdiiiis",
        [
            $cobrar_clientes_id,
            $cliente_id,
            $factura_id,
            $fecha,
            $saldo,
            $estado,
            $tipo_factura,
            $usuario,
            $empresa_id,
            $fecha_registro
        ]
    );

    return $cobrar_clientes_id;
}

function marcarCuentaPorCobrarComoPagada($conn, $factura_id, $empresa_id) {
    stmtExec(
        $conn,
        "UPDATE cobrar_clientes
         SET saldo = 0,
             estado = 2
         WHERE facturas_id = ?
           AND empresa_id = ?",
        "ii",
        [
            $factura_id,
            $empresa_id
        ]
    );
}

// ===============================
// GET: CLIENTES
// ===============================
if (isset($_GET['getClientes'])) {
    $clientes = stmtFetchAll(
        $conn,
        "SELECT clientes_id, nombre, rtn
         FROM clientes
         WHERE estado = 1
         ORDER BY nombre"
    );

    jsonResponse($clientes);
}

// ===============================
// GET: VENDEDORES
// ===============================
if (isset($_GET['getVendedores'])) {
    $vendedores = stmtFetchAll(
        $conn,
        "SELECT colaboradores_id, nombre
         FROM colaboradores
         WHERE estado = 1
         ORDER BY nombre"
    );

    jsonResponse($vendedores);
}

// ===============================
// GET: PRODUCTOS
// ===============================
if (isset($_GET['getProductos'])) {
    $productos = stmtFetchAll(
        $conn,
        "SELECT
            productos_id,
            nombre,
            precio_venta,
            isv_venta,
            CASE
                WHEN isv_venta = 1 AND isv2 = 1 THEN 2
                WHEN isv_venta = 1 AND isv1 = 1 THEN 1
                WHEN isv_venta = 1 THEN 1
                ELSE 0
            END AS isv_id_aplicado,
            CASE
                WHEN isv_venta = 1 AND isv2 = 1 THEN 0.18
                WHEN isv_venta = 1 AND isv1 = 1 THEN 0.15
                WHEN isv_venta = 1 THEN 0.15
                ELSE 0
            END AS isv_rate_decimal,
            CASE
                WHEN isv_venta = 1 AND isv2 = 1 THEN 'ISV 18.00%'
                WHEN isv_venta = 1 AND isv1 = 1 THEN 'ISV 15.00%'
                WHEN isv_venta = 1 THEN 'ISV 15.00%'
                ELSE ''
            END AS isv_label
         FROM productos
         WHERE estado = 1
         ORDER BY nombre"
    );

    jsonResponse($productos);
}

// ===============================
// GET: CONFIG FACTURA MÓVIL
// ===============================
if (isset($_GET['getConfigFacturaMovil'])) {
    $proformaActiva = obtenerConfigActivar($conn, 'Activar Proforma', 0);
    $proformaRebajarInventario = obtenerConfigActivar($conn, 'Activar Rebajar Inventario Proforma', 0);

    jsonResponse([
        'success' => true,
        'proforma_activa' => $proformaActiva,
        'proforma_rebajar_inventario' => $proformaRebajarInventario
    ]);
}

// ===============================
// GET: SECUENCIA FACTURA / PROFORMA
// ===============================
if (isset($_GET['getSecuenciaFactura'])) {
    $documento_id = isset($_GET['documento_id']) ? intval($_GET['documento_id']) : 1;
    $secuencia = obtenerSecuenciaActiva($conn, $documento_id, false);

    if ($secuencia) {
        jsonResponse($secuencia);
    }

    jsonResponse([
        'success' => false,
        'error' => $documento_id == 4
            ? 'No hay secuencia de proforma activa'
            : 'No hay secuencia de facturación activa'
    ]);
}

// ===============================
// POST JSON: REGISTRAR PAGO
// ===============================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
    && isset($_GET['registrarPago'])
) {
    $data = getJsonInput();

    $factura_id = intValue($data['facturaId'] ?? 0);
    $efectivo = moneyValue($data['efectivo'] ?? 0);
    $transferencia = moneyValue($data['transferencia'] ?? 0);
    $tarjeta = moneyValue($data['tarjeta'] ?? 0);
    $cambio = moneyValue($data['cambio'] ?? 0);

    $empresa_id = intValue($_SESSION['empresa_id_sd'] ?? 1);
    if ($empresa_id <= 0) {
        $empresa_id = 1;
    }

    $usuario = intValue($_SESSION['colaborador_id_sd'] ?? 1);

    if ($usuario <= 0) {
        $usuario = 1;
    }

    if ($factura_id <= 0) {
        jsonResponse([
            'success' => false,
            'message' => 'ID de factura es requerido'
        ]);
    }

    try {
        $conn->begin_transaction();

        $factura = stmtFetchOne(
            $conn,
            "SELECT facturas_id, clientes_id, importe, tipo_factura
             FROM facturas
             WHERE facturas_id = ?
             LIMIT 1
             FOR UPDATE",
            "i",
            [$factura_id]
        );

        if (!$factura) {
            throw new Exception('Factura no encontrada');
        }

        if (intval($factura['tipo_factura']) === 3) {
            throw new Exception('Una proforma no debe registrar pago');
        }

        $total_factura = moneyValue($factura['importe']);
        $total_pago = moneyValue($efectivo + $transferencia + $tarjeta - $cambio);

        if (abs($total_pago - $total_factura) > 0.01) {
            throw new Exception('El pago no coincide con el total de la factura');
        }

        $fecha_actual = date('Y-m-d');
        $fecha_registro = date('Y-m-d H:i:s');

        $pagos_id = obtenerSiguienteIdManual($conn, 'pagos', 'pagos_id');

        stmtExec(
            $conn,
            "INSERT INTO pagos (
                pagos_id,
                facturas_id,
                tipo_pago,
                fecha,
                importe,
                efectivo,
                cambio,
                tarjeta,
                usuario,
                estado,
                empresa_id,
                fecha_registro
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "iiisddddiiis",
            [
                $pagos_id,
                $factura_id,
                1,
                $fecha_actual,
                $total_factura,
                $efectivo,
                $cambio,
                $tarjeta,
                $usuario,
                1,
                $empresa_id,
                $fecha_registro
            ]
        );

        insertarPagoDetalle($conn, $pagos_id, 1, $efectivo, 'EFECTIVO', '', '');
        insertarPagoDetalle($conn, $pagos_id, 2, $tarjeta, 'TARJETA', '', '');
        insertarPagoDetalle($conn, $pagos_id, 3, $transferencia, 'TRANSFERENCIA', '', '');

        stmtExec(
            $conn,
            "UPDATE facturas
             SET estado = 2
             WHERE facturas_id = ?",
            "i",
            [$factura_id]
        );

        $cobrarCliente = stmtFetchOne(
            $conn,
            "SELECT cobrar_clientes_id
             FROM cobrar_clientes
             WHERE facturas_id = ?
               AND empresa_id = ?
             LIMIT 1",
            "ii",
            [
                $factura_id,
                $empresa_id
            ]
        );

        if ($cobrarCliente) {
            marcarCuentaPorCobrarComoPagada($conn, $factura_id, $empresa_id);
        } else {
            registrarCuentaPorCobrarCliente(
                $conn,
                intval($factura['clientes_id']),
                $factura_id,
                $fecha_actual,
                0,
                2,
                intval($factura['tipo_factura']),
                $usuario,
                $empresa_id,
                $fecha_registro
            );
        }

        $conn->commit();

        jsonResponse([
            'success' => true,
            'pago_id' => $pagos_id,
            'factura_id' => $factura_id,
            'imprimir' => true
        ]);
    } catch (Exception $e) {
        $conn->rollback();

        jsonResponse([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ===============================
// POST JSON: PROCESAR FACTURA / PROFORMA
// ===============================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
    && (isset($_GET['procesarFactura']) || !isset($_GET['registrarPago']))
) {
    $data = getJsonInput();

    $cliente_id = intValue($data['clienteId'] ?? 0);

    $vendedor_id = intValue($data['vendedorId'] ?? 0);

    if ($vendedor_id <= 0) {
        $vendedor_id = intValue($_SESSION['colaborador_id_sd'] ?? 0);
    }

    if ($vendedor_id <= 0) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'No se pudo obtener el vendedor. Seleccione un vendedor o verifique la sesión del usuario.'
        ]);
    }

    $tipo_factura = intValue($data['tipoFactura'] ?? 1);
    $documento_id = intValue($data['documentoId'] ?? ($tipo_factura == 3 ? 4 : 1));
    $bajar_inventario = intValue($data['bajarInventario'] ?? ($tipo_factura == 3 ? 0 : 1));
    $productos = $data['productos'] ?? [];
    $notas = trim($data['notas'] ?? '');

    $empresa_id = intValue($_SESSION['empresa_id_sd'] ?? 1);
    if ($empresa_id <= 0) {
        $empresa_id = 1;
    }

    $usuario = intValue($_SESSION['colaborador_id_sd'] ?? 1);

    if ($usuario <= 0) {
        $usuario = 1;
    }

    if ($cliente_id <= 0) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'Cliente es requerido'
        ]);
    }

    if (!is_array($productos) || count($productos) === 0) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'Debe agregar al menos un producto'
        ]);
    }

    if (!in_array($tipo_factura, [1, 2, 3], true)) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'Tipo de factura inválido'
        ]);
    }

    if ($tipo_factura == 3) {
        $documento_id = 4;
        $bajar_inventario = $bajar_inventario === 1 ? 1 : 0;
    } else {
        $documento_id = 1;
        $bajar_inventario = 1;
    }

    $configProformaActiva = obtenerConfigActivar($conn, 'Activar Proforma', 0);

    if ($tipo_factura == 3 && $configProformaActiva !== 1) {
        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => 'La opción de Proforma no está activa en configuración'
        ]);
    }

    try {
        $conn->begin_transaction();

        $apertura = stmtFetchOne(
            $conn,
            "SELECT apertura_id
             FROM apertura
             WHERE estado = 1
               AND empresa_id = ?
             ORDER BY apertura_id DESC
             LIMIT 1",
            "i",
            [$empresa_id]
        );

        if (!$apertura) {
            throw new Exception('No hay caja aperturada');
        }

        $apertura_id = intval($apertura['apertura_id']);

        $secuencia = obtenerSecuenciaActiva($conn, $documento_id, true);
        validarSecuencia($secuencia, $documento_id);

        $secuencia_id = intval($secuencia['secuencia_facturacion_id']);
        $numero = intval($secuencia['siguiente']);

        $incremento = intval($secuencia['incremento'] ?? 1);
        if ($incremento <= 0) {
            $incremento = 1;
        }

        $numero_documento = numeroDocumentoFormateado($secuencia, $numero);

        $productosNormalizados = [];
        $subtotal_bruto = 0;
        $total_isv = 0;
        $total_descuento = 0;

        foreach ($productos as $producto) {
            $detalle = normalizarProductoDetalle($conn, $producto);

            $productosNormalizados[] = $detalle;
            $subtotal_bruto += $detalle['linea_bruta'];
            $total_descuento += $detalle['descuento'];
            $total_isv += $detalle['isv_total_linea'];
        }

        $subtotal_bruto = moneyValue($subtotal_bruto);
        $total_descuento = moneyValue($total_descuento);
        $total_isv = moneyValue($total_isv);
        $subtotal_neto = moneyValue($subtotal_bruto - $total_descuento);
        $total = moneyValue($subtotal_neto + $total_isv);

        $fecha_actual = date('Y-m-d');
        $fecha_registro = date('Y-m-d H:i:s');

        $estado_factura = ($tipo_factura == 1) ? 2 : (($tipo_factura == 2) ? 3 : 4);

        $factura_id = obtenerSiguienteIdManual($conn, 'facturas', 'facturas_id');

        stmtExec(
            $conn,
            "INSERT INTO facturas (
                facturas_id,
                clientes_id,
                secuencia_facturacion_id,
                apertura_id,
                number,
                tipo_factura,
                colaboradores_id,
                importe,
                notas,
                fecha,
                estado,
                usuario,
                empresa_id,
                fecha_registro,
                fecha_dolar
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "iiiiiiidssiiiss",
            [
                $factura_id,
                $cliente_id,
                $secuencia_id,
                $apertura_id,
                $numero,
                $tipo_factura,
                $vendedor_id,
                $total,
                $notas,
                $fecha_actual,
                $estado_factura,
                $usuario,
                $empresa_id,
                $fecha_registro,
                $fecha_actual
            ]
        );

        foreach ($productosNormalizados as $detalle) {
            $facturas_detalle_id = obtenerSiguienteIdManual($conn, 'facturas_detalles', 'facturas_detalle_id');

            stmtExec(
                $conn,
                "INSERT INTO facturas_detalles (
                    facturas_detalle_id,
                    facturas_id,
                    productos_id,
                    cantidad,
                    precio,
                    isv_valor,
                    isv_valor1,
                    descuento,
                    medida
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "iiiidddds",
                [
                    $facturas_detalle_id,
                    $factura_id,
                    $detalle['producto_id'],
                    $detalle['cantidad'],
                    $detalle['precio'],
                    $detalle['isv_valor'],
                    $detalle['isv_valor1'],
                    $detalle['descuento'],
                    $detalle['medida']
                ]
            );
        }

        if ($tipo_factura == 1 || $tipo_factura == 2) {
            registrarCuentaPorCobrarCliente(
                $conn,
                $cliente_id,
                $factura_id,
                $fecha_actual,
                $total,
                1,
                $tipo_factura,
                $usuario,
                $empresa_id,
                $fecha_registro
            );
        }

        if ($bajar_inventario === 1) {
            $comentario = ($tipo_factura == 3)
                ? 'Salida inventario por proforma'
                : 'Salida inventario por facturación';

            foreach ($productosNormalizados as $detalle) {
                $productoMovimiento = $detalle['producto_original'];
                $productoMovimiento['productoId'] = $detalle['producto_id'];
                $productoMovimiento['cantidad'] = $detalle['cantidad'];

                registrarMovimientoSalida(
                    $conn,
                    $productoMovimiento,
                    $numero_documento,
                    $empresa_id,
                    $cliente_id,
                    $comentario
                );
            }
        }

        stmtExec(
            $conn,
            "UPDATE secuencia_facturacion
             SET siguiente = siguiente + ?
             WHERE secuencia_facturacion_id = ?",
            "ii",
            [$incremento, $secuencia_id]
        );

        $conn->commit();

        jsonResponse([
            'success' => true,
            'estado' => true,
            'factura_id' => $factura_id,
            'numero_factura' => $numero_documento,
            'total' => $total,
            'tipo_factura' => $tipo_factura,
            'documento_id' => $documento_id,
            'bajo_inventario' => $bajar_inventario
        ]);
    } catch (Exception $e) {
        $conn->rollback();

        jsonResponse([
            'success' => false,
            'estado' => false,
            'message' => $e->getMessage()
        ]);
    }
}

jsonResponse([
    'success' => false,
    'estado' => false,
    'message' => 'Petición no válida'
]);