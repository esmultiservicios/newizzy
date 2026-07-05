<?php
// core/DetallesFacturacion/DetallesFacturacion.php

$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

function responderDetallesFacturacion($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function obtenerValorFiltroDetallesFacturacion($key, $default = '') {
    if (isset($_POST[$key])) {
        return trim((string)$_POST[$key]);
    }

    if (isset($_GET[$key])) {
        return trim((string)$_GET[$key]);
    }

    return $default;
}

try {
    if (method_exists($insMainModel, 'validarSesion')) {
        $validacion = $insMainModel->validarSesion();

        if (!empty($validacion['error'])) {
            responderDetallesFacturacion([
                'echo' => 1,
                'totalrecords' => 0,
                'totaldisplayrecords' => 0,
                'data' => [],
                'type' => 'error',
                'title' => 'Error de sesión',
                'message' => $validacion['mensaje'] ?? 'Sesión inválida'
            ]);
        }
    }

    if (!isset($_SESSION['users_id_sd'])) {
        responderDetallesFacturacion([
            'echo' => 1,
            'totalrecords' => 0,
            'totaldisplayrecords' => 0,
            'data' => [],
            'type' => 'error',
            'title' => 'Error de sesión',
            'message' => 'Usuario no autenticado'
        ]);
    }

    $users_id = (int)$_SESSION['users_id_sd'];

    $conexionPrincipal = $insMainModel->connection();

    if (!$conexionPrincipal) {
        responderDetallesFacturacion([
            'echo' => 1,
            'totalrecords' => 0,
            'totaldisplayrecords' => 0,
            'data' => [],
            'type' => 'error',
            'title' => 'Error de conexión',
            'message' => 'No se pudo conectar a la base principal'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Obtener server_customers_id del usuario
    |--------------------------------------------------------------------------
    */
    $queryUsuario = "SELECT server_customers_id FROM users WHERE users_id = ?";
    $stmtUsuario = $conexionPrincipal->prepare($queryUsuario);

    if (!$stmtUsuario) {
        throw new Exception("Error preparando consulta de usuario: " . $conexionPrincipal->error);
    }

    $stmtUsuario->bind_param("i", $users_id);
    $stmtUsuario->execute();

    $resultUsuario = $stmtUsuario->get_result();

    if ($resultUsuario->num_rows === 0) {
        $stmtUsuario->close();

        responderDetallesFacturacion([
            'echo' => 1,
            'totalrecords' => 0,
            'totaldisplayrecords' => 0,
            'data' => [],
            'type' => 'error',
            'title' => 'Error de usuario',
            'message' => 'Usuario no tiene una base de datos asociada'
        ]);
    }

    $usuarioData = $resultUsuario->fetch_assoc();
    $serverCustomersId = (int)$usuarioData['server_customers_id'];

    $stmtUsuario->close();

    /*
    |--------------------------------------------------------------------------
    | 2. Conectar a la base de datos del cliente
    |--------------------------------------------------------------------------
    | Se mantiene la misma lógica que ya tenías usando DB_MAIN.
    |--------------------------------------------------------------------------
    */
    $configCliente = [
        'host' => SERVER,
        'user' => USER,
        'pass' => PASS,
        'name' => DB_MAIN
    ];

    $conexionCliente = $insMainModel->connectToDatabase($configCliente);

    if (!$conexionCliente) {
        responderDetallesFacturacion([
            'echo' => 1,
            'totalrecords' => 0,
            'totaldisplayrecords' => 0,
            'data' => [],
            'type' => 'error',
            'title' => 'Error de conexión',
            'message' => 'No se pudo conectar a la base de datos del cliente'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Obtener clientes_id
    |--------------------------------------------------------------------------
    */
    $queryCliente = "SELECT clientes_id FROM server_customers WHERE server_customers_id = ?";
    $stmtCliente = $conexionCliente->prepare($queryCliente);

    if (!$stmtCliente) {
        throw new Exception("Error preparando consulta de cliente: " . $conexionCliente->error);
    }

    $stmtCliente->bind_param("i", $serverCustomersId);
    $stmtCliente->execute();

    $resultCliente = $stmtCliente->get_result();

    if ($resultCliente->num_rows === 0) {
        $stmtCliente->close();
        $conexionCliente->close();

        responderDetallesFacturacion([
            'echo' => 1,
            'totalrecords' => 0,
            'totaldisplayrecords' => 0,
            'data' => [],
            'type' => 'error',
            'title' => 'Error de usuario',
            'message' => 'Usuario no tiene un cliente asociado'
        ]);
    }

    $clienteData = $resultCliente->fetch_assoc();
    $clientes_id = (int)$clienteData['clientes_id'];

    $stmtCliente->close();

    /*
    |--------------------------------------------------------------------------
    | 4. Filtros
    |--------------------------------------------------------------------------
    */
    $facturasId = (int)obtenerValorFiltroDetallesFacturacion('facturas_id', 0);
    $fechaInicio = obtenerValorFiltroDetallesFacturacion('fecha_inicio');
    $fechaFin = obtenerValorFiltroDetallesFacturacion('fecha_fin');
    $tipoFactura = obtenerValorFiltroDetallesFacturacion('tipo_factura');
    $estadoFactura = obtenerValorFiltroDetallesFacturacion('estado_factura');
    $numeroFactura = obtenerValorFiltroDetallesFacturacion('numero_factura');

    $where = "f.clientes_id = ?";
    $params = [$clientes_id];
    $paramTypes = "i";

    if ($facturasId > 0) {
        $where .= " AND f.facturas_id = ?";
        $params[] = $facturasId;
        $paramTypes .= "i";
    }

    if ($fechaInicio !== '') {
        $where .= " AND f.fecha >= ?";
        $params[] = $fechaInicio;
        $paramTypes .= "s";
    }

    if ($fechaFin !== '') {
        $where .= " AND f.fecha <= ?";
        $params[] = $fechaFin;
        $paramTypes .= "s";
    }

    if ($tipoFactura !== '' && $tipoFactura !== 'todos') {
        $where .= " AND f.tipo_factura = ?";
        $params[] = (int)$tipoFactura;
        $paramTypes .= "i";
    }

    if ($estadoFactura !== '' && $estadoFactura !== 'todos') {
        $sqlTotalPagadoFiltro = "COALESCE((SELECT SUM(pg.importe) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0)";

        if ($estadoFactura === 'pendiente_pago') {
            /*
             * Solo pendientes de pago:
             * - estado 1: borrador/proforma pendiente.
             * - estado 3: crédito/proforma crédito con saldo pendiente.
             */
            $where .= " AND (
                f.estado = 1
                OR (
                    f.estado = 3
                    AND {$sqlTotalPagadoFiltro} < f.importe
                )
            )";
        } elseif ($estadoFactura === '1') {
            /*
             * Borrador / Pendiente de pago:
             * - estado 1 se muestra siempre.
             * - proforma en crédito estado 3 se muestra aquí si todavía no está pagada.
             */
            $where .= " AND (
                f.estado = 1
                OR (
                    d.documento_id = 4
                    AND f.estado = 3
                    AND {$sqlTotalPagadoFiltro} < f.importe
                )
            )";
        } else {
            $where .= " AND f.estado = ?";
            $params[] = (int)$estadoFactura;
            $paramTypes .= "i";
        }
    }

    if ($numeroFactura !== '') {
        $where .= " AND (
            CONCAT(sf.prefijo, LPAD(f.number, sf.relleno, 0)) LIKE ?
            OR CONCAT('PROFORMA-', sf.prefijo, LPAD(f.number, sf.relleno, 0)) LIKE ?
            OR COALESCE(NULLIF(TRIM(c.nombre), ''), 'Cliente no especificado') LIKE ?
            OR f.importe LIKE ?
            OR CASE
                WHEN f.tipo_factura = 1 THEN 'Contado'
                WHEN f.tipo_factura = 2 THEN 'Crédito'
                ELSE 'Sin tipo'
            END LIKE ?
            OR CASE
                WHEN f.estado = 1 AND d.documento_id = 4 THEN 'Pendiente de pago'
                WHEN f.estado = 1 THEN 'Borrador'
                WHEN d.documento_id = 4 AND f.estado = 3 AND COALESCE((SELECT COUNT(*) FROM cobrar_clientes cc WHERE cc.facturas_id = f.facturas_id AND cc.estado = 2), 0) = 0 THEN 'Pendiente de pago'
                WHEN d.documento_id = 4 AND f.estado = 3 THEN 'Crédito con abono'
                WHEN f.estado = 2 AND f.tipo_factura = 1 THEN 'Pagada al contado'
                WHEN f.estado = 2 AND f.tipo_factura = 2 THEN 'Crédito pagado'
                WHEN f.estado = 2 THEN 'Pagada'
                WHEN f.estado = 3 AND COALESCE((SELECT SUM(pg.importe) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0) > 0 THEN 'Crédito con abono'
                WHEN f.estado = 3 THEN 'Crédito pendiente'
                WHEN f.estado = 4 THEN 'Anulada / Cancelada'
                ELSE 'Sin estado'
            END LIKE ?
        )";

        $busqueda = '%' . $numeroFactura . '%';

        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;
        $params[] = $busqueda;

        $paramTypes .= "ssssss";
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Consulta principal
    |--------------------------------------------------------------------------
    */
    $query = "SELECT 
        f.facturas_id,
        DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
        COALESCE(NULLIF(TRIM(c.nombre), ''), 'Cliente no especificado') AS cliente,
        CASE 
            WHEN d.documento_id = 4 THEN CONCAT('PROFORMA-', sf.prefijo, LPAD(f.number, sf.relleno, 0))
            ELSE CONCAT(sf.prefijo, LPAD(f.number, sf.relleno, 0))
        END AS numero,
        f.importe AS total,
        CASE 
            WHEN f.tipo_factura = 1 THEN 'Contado'
            WHEN f.tipo_factura = 2 THEN 'Crédito'
            ELSE 'Sin tipo'
        END AS tipo_documento,
        f.tipo_factura AS tipo_factura_id,
        co.nombre AS vendedor,
        co1.nombre AS facturador,
        COALESCE((SELECT SUM(fd.cantidad * fd.precio) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id), 0) AS subtotal,
        COALESCE((SELECT SUM(fd.cantidad * p.precio_compra) FROM facturas_detalles AS fd INNER JOIN productos AS p ON fd.productos_id = p.productos_id WHERE fd.facturas_id = f.facturas_id), 0) AS subCosto,
        COALESCE((SELECT SUM(fd.isv_valor) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id), 0) AS isv,
        COALESCE((SELECT SUM(fd.descuento) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id), 0) AS descuento,
        COALESCE((SELECT COUNT(*) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0) AS pagos_realizados,
        COALESCE((SELECT SUM(pg.importe) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0) AS total_pagado,
        f.estado,
        CASE
            WHEN f.estado = 1 AND d.documento_id = 4 THEN 'Pendiente de pago'
            WHEN f.estado = 1 THEN 'Borrador'
            WHEN f.estado = 2 AND f.tipo_factura = 1 THEN 'Pagada al contado'
            WHEN f.estado = 2 AND f.tipo_factura = 2 THEN 'Crédito pagado'
            WHEN f.estado = 2 THEN 'Pagada'
            WHEN f.estado = 3 AND COALESCE((SELECT SUM(pg.importe) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0) >= f.importe AND f.importe > 0 THEN 'Crédito pagado'
            WHEN f.estado = 3 AND COALESCE((SELECT SUM(pg.importe) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1), 0) > 0 THEN 'Crédito con abono'
            WHEN f.estado = 3 AND d.documento_id = 4 THEN 'Pendiente de pago'
            WHEN f.estado = 3 THEN 'Crédito pendiente'
            WHEN f.estado = 4 THEN 'Anulada / Cancelada'
            ELSE 'Sin estado'
        END AS estado_texto,
        d.documento_id,
        COALESCE((SELECT COUNT(*) FROM cobrar_clientes WHERE facturas_id = f.facturas_id AND estado = 1), 0) AS tiene_pendiente,
        f.notas,
        ? AS db_name
    FROM 
        facturas AS f
        INNER JOIN clientes AS c ON f.clientes_id = c.clientes_id
        INNER JOIN colaboradores AS co ON f.colaboradores_id = co.colaboradores_id
        INNER JOIN colaboradores AS co1 ON f.usuario = co1.colaboradores_id
        INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
        INNER JOIN documento AS d ON sf.documento_id = d.documento_id
    WHERE $where
    ORDER BY f.fecha DESC, f.facturas_id DESC";

    array_unshift($params, DB_MAIN);
    $paramTypes = "s" . $paramTypes;

    $stmt = $conexionCliente->prepare($query);

    if (!$stmt) {
        throw new Exception("Error preparando consulta de facturas: " . $conexionCliente->error);
    }

    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $subtotal = isset($row['subtotal']) ? (float)$row['subtotal'] : 0.00;
        $subCosto = isset($row['subCosto']) ? (float)$row['subCosto'] : 0.00;
        $isv = isset($row['isv']) ? (float)$row['isv'] : 0.00;
        $descuento = isset($row['descuento']) ? (float)$row['descuento'] : 0.00;

        $ganancia = $subtotal - $subCosto - $isv - $descuento;

        $data[] = [
            'facturas_id' => (int)$row['facturas_id'],
            'fecha' => $row['fecha'],
            'tipo_documento' => $row['tipo_documento'],
            'tipo_factura_id' => (int)$row['tipo_factura_id'],
            'cliente' => $row['cliente'],
            'numero' => $row['numero'],
            'subtotal' => $subtotal,
            'ganancia' => $ganancia,
            'isv' => $isv,
            'descuento' => $descuento,
            'total' => isset($row['total']) ? (float)$row['total'] : 0.00,
            'vendedor' => $row['vendedor'],
            'facturador' => $row['facturador'],
            'estado' => (int)$row['estado'],
            'estado_texto' => $row['estado_texto'],
            'pagos_realizados' => (int)$row['pagos_realizados'],
            'total_pagado' => isset($row['total_pagado']) ? (float)$row['total_pagado'] : 0.00,
            'documento_id' => (int)$row['documento_id'],
            'tiene_pendiente' => (int)$row['tiene_pendiente'],
            'notas' => $row['notas'],
            'db_name' => $row['db_name']
        ];
    }

    $stmt->close();
    $conexionCliente->close();

    responderDetallesFacturacion([
        'echo' => 1,
        'totalrecords' => count($data),
        'totaldisplayrecords' => count($data),
        'data' => $data,
        'type' => 'success',
        'title' => 'Éxito',
        'message' => 'Datos cargados correctamente'
    ]);

} catch (Throwable $e) {
    responderDetallesFacturacion([
        'echo' => 1,
        'totalrecords' => 0,
        'totaldisplayrecords' => 0,
        'data' => [],
        'type' => 'error',
        'title' => 'Error del sistema',
        'message' => 'Ocurrió un error inesperado: ' . $e->getMessage()
    ]);
}