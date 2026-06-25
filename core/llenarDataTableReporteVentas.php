<?php
// core/llenarDataTableReporteVentas.php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

header('Content-Type: application/json; charset=utf-8');

try {
    // Validar sesión
    $validacion = $insMainModel->validarSesion();
    if ($validacion['error']) {
        echo $insMainModel->showNotification([
            "title"   => "Error de sesión",
            "text"    => $validacion['mensaje'],
            "type"    => "error",
            "funcion" => "window.location.href = '".$validacion['redireccion']."'"
        ]);
        exit;
    }

    $tipo_factura_reporte = isset($_POST['tipo_factura_reporte']) ? (int)$_POST['tipo_factura_reporte'] : 1; // 1=Activas, 2=Anuladas
    $fechai = $_POST['fechai'] ?? date('Y-m-01');
    $fechaf = $_POST['fechaf'] ?? date('Y-m-d');
    $facturador = $_POST['facturador'] ?? '';
    $vendedor = $_POST['vendedor'] ?? '';
    $factura = isset($_POST['factura']) ? (int)$_POST['factura'] : 1; // 1=Factura, 4=Proforma
    $empresa_id_sd = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

    $cn = $insMainModel->connection();
    if (!$cn) {
        echo json_encode(['echo'=>1,'totalrecords'=>0,'totaldisplayrecords'=>0,'data'=>[]]);
        exit;
    }

    /*
     * IMPORTANTE:
     * No se usa consultaVentas() de mainModel porque ahí proforma (documento_id=4)
     * no estaba filtrando f.estado. Por eso una proforma anulada con f.estado=4
     * seguía apareciendo en el filtro de Activas.
     *
     * Regla final:
     * - Activas: f.estado IN (2,3)
     * - Anuladas: f.estado = 4
     * Aplica igual para facturas normales y proformas.
     */
    $where = [];
    $types = '';
    $params = [];

    $where[] = 'f.empresa_id = ?';
    $types .= 'i';
    $params[] = $empresa_id_sd;

    $where[] = 'f.fecha BETWEEN ? AND ?';
    $types .= 'ss';
    $params[] = $fechai;
    $params[] = $fechaf;

    if ($factura > 0) {
        $where[] = 'sf.documento_id = ?';
        $types .= 'i';
        $params[] = $factura;
    }

    if ($tipo_factura_reporte === 1) {
        $where[] = 'f.estado IN (2,3)';
    } elseif ($tipo_factura_reporte === 2) {
        $where[] = 'f.estado = 4';
    }

    if ($facturador !== '' && (int)$facturador > 0) {
        $where[] = 'f.usuario = ?';
        $types .= 'i';
        $params[] = (int)$facturador;
    }

    if ($vendedor !== '' && (int)$vendedor > 0) {
        $where[] = 'f.colaboradores_id = ?';
        $types .= 'i';
        $params[] = (int)$vendedor;
    }

    $whereSql = implode("\n            AND ", $where);

    $sql = "
        SELECT
            f.facturas_id AS facturas_id,
            DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
            c.nombre AS cliente,
            CASE
                WHEN d.documento_id = 4 THEN CONCAT('PROFORMA-', sf.prefijo, LPAD(f.number, sf.relleno, '0'))
                ELSE CONCAT(sf.prefijo, LPAD(f.number, sf.relleno, '0'))
            END AS numero,
            f.number AS number,
            f.fecha AS fecha_orden,
            f.importe AS total,
            CASE
                WHEN f.tipo_factura = 1 THEN 'Contado'
                ELSE 'Crédito'
            END AS tipo_documento,
            co.nombre AS vendedor,
            co1.nombre AS facturador,

            (SELECT COALESCE(SUM(fd.cantidad * fd.precio), 0)
             FROM facturas_detalles fd
             WHERE fd.facturas_id = f.facturas_id) AS subtotal,

            (SELECT COALESCE(SUM(fd.cantidad * p.precio_compra), 0)
             FROM facturas_detalles fd
             INNER JOIN productos p ON p.productos_id = fd.productos_id
             WHERE fd.facturas_id = f.facturas_id) AS subCosto,

            (SELECT COALESCE(SUM(fd.isv_valor + fd.isv_valor1), 0)
             FROM facturas_detalles fd
             WHERE fd.facturas_id = f.facturas_id) AS isv,

            (SELECT COALESCE(SUM(fd.descuento), 0)
             FROM facturas_detalles fd
             WHERE fd.facturas_id = f.facturas_id) AS descuento,

            CASE
                WHEN d.documento_id = 4 THEN NULL
                WHEN f.tipo_factura = 1 THEN 'Pagado'
                WHEN (SELECT COUNT(*) FROM pagos pg WHERE pg.facturas_id = f.facturas_id AND pg.estado = 1) > 0 THEN 'Pagado'
                ELSE 'Pendiente'
            END AS estado_pago,

            f.tipo_factura AS tipo_factura,
            f.estado AS facturas_estado,
            d.documento_id AS documento_id,
            fp.estado AS proforma_estado_bd,
            fp.facturas_proforma_id AS facturas_proforma_id_bd
        FROM facturas f
        INNER JOIN clientes c ON f.clientes_id = c.clientes_id
        INNER JOIN colaboradores co ON f.colaboradores_id = co.colaboradores_id
        INNER JOIN colaboradores co1 ON f.usuario = co1.colaboradores_id
        INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
        INNER JOIN documento d ON sf.documento_id = d.documento_id
        LEFT JOIN facturas_proforma fp ON fp.facturas_id = f.facturas_id AND fp.empresa_id = f.empresa_id
        WHERE
            {$whereSql}
        ORDER BY
            f.number DESC,
            f.fecha DESC,
            f.facturas_id DESC
    ";

    $stmt = $cn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar reporte de ventas: '.$cn->error);
    }

    if ($types !== '') {
        $bindParams = [];
        $bindParams[] = $types;
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar reporte de ventas: '.$stmt->error);
    }

    $result = $stmt->get_result();

    $data = [];

    /* SUMA DE PAGOS (pagos.estado=1) */
    $stmtPago = $cn->prepare("
        SELECT COALESCE(SUM(p.importe),0) AS pagado
        FROM pagos p
        WHERE p.facturas_id = ? AND p.estado = 1
    ");

    /* PROFORMA por facturas_id */
    $stmtProforma = $cn->prepare("
        SELECT facturas_proforma_id, estado
        FROM facturas_proforma
        WHERE facturas_id = ?
        ORDER BY facturas_proforma_id DESC
        LIMIT 1
    ");

    /* Fallback: estado de CxC por facturas_id (1=Pendiente, 2=Pago Realizado) */
    $stmtCxC = $cn->prepare("
        SELECT estado
        FROM cobrar_clientes
        WHERE facturas_id = ?
        ORDER BY cobrar_clientes_id DESC
        LIMIT 1
    ");

    while ($row = $result->fetch_assoc()) {
        $facturas_id = (int)($row['facturas_id'] ?? 0);
        $documento_id = (int)($row['documento_id'] ?? 0); // 4 = Proforma
        $facturas_estado = (int)($row['facturas_estado'] ?? 0);

        // Ganancia
        $ganancia = (double)($row['subtotal'] ?? 0)
                  - (double)($row['subCosto'] ?? 0)
                  - (double)($row['isv'] ?? 0)
                  - (double)($row['descuento'] ?? 0);

        // Pagos activos
        $monto_pagado = 0.0;
        if ($facturas_id > 0 && $stmtPago) {
            $stmtPago->bind_param('i', $facturas_id);
            $stmtPago->execute();
            $resP = $stmtPago->get_result();
            if ($resP && $pRow = $resP->fetch_assoc()) {
                $monto_pagado = (float)$pRow['pagado'];
            }
            if ($resP) $resP->free();
        }

        // PROFORMA: 1=ABIERTA, 2=CERRADA. Si factura está anulada, se manda 4 también como dato extra.
        $proforma_estado = 1;
        $facturas_proforma_id = 0;

        if ($documento_id === 4 && $facturas_id > 0) {
            if ($stmtProforma) {
                $stmtProforma->bind_param('i', $facturas_id);
                $stmtProforma->execute();
                $resF = $stmtProforma->get_result();

                if ($resF && $fRow = $resF->fetch_assoc()) {
                    $facturas_proforma_id = (int)$fRow['facturas_proforma_id'];
                    $proforma_estado = (int)$fRow['estado'];

                    if ($proforma_estado !== 1 && $proforma_estado !== 2) {
                        $proforma_estado = 1;
                    }
                } else {
                    if ($stmtCxC) {
                        $stmtCxC->bind_param('i', $facturas_id);
                        $stmtCxC->execute();
                        $resC = $stmtCxC->get_result();

                        if ($resC && $cRow = $resC->fetch_assoc()) {
                            $proforma_estado = ((int)$cRow['estado'] === 2) ? 2 : 1;
                        }

                        if ($resC) $resC->free();
                    }
                }

                if ($resF) $resF->free();
            }
        }

        $data[] = [
            'facturas_id'          => $facturas_id,
            'fecha'                => $row['fecha'],
            'tipo_documento'       => $row['tipo_documento'],
            'cliente'              => $row['cliente'],
            'numero'               => $row['numero'],
            'numero_sort'          => (int)($row['number'] ?? 0),
            'number'               => (int)($row['number'] ?? 0),

            'subtotal'             => (float)($row['subtotal'] ?? 0),
            'isv'                  => (float)($row['isv'] ?? 0),
            'descuento'            => (float)($row['descuento'] ?? 0),
            'total'                => (float)($row['total'] ?? 0),
            'ganancia'             => (float)$ganancia,

            'vendedor'             => $row['vendedor'],
            'facturador'           => $row['facturador'],

            // PROFORMA
            'documento_id'         => $documento_id,
            'proforma_estado'      => $proforma_estado,
            'facturas_proforma_id' => $facturas_proforma_id,

            // Estado real de la factura: 2/3 activa, 4 anulada
            'facturas_estado'      => $facturas_estado,

            // Pagos info
            'estado_pago'          => $row['estado_pago'] ?? null,
            'tipo_factura'         => $row['tipo_factura'] ?? null,
            'tiene_pago'           => ($monto_pagado > 0 ? 1 : 0),
            'monto_pagado'         => $monto_pagado,
        ];
    }

    if ($stmtPago) $stmtPago->close();
    if ($stmtProforma) $stmtProforma->close();
    if ($stmtCxC) $stmtCxC->close();
    if ($result) $result->free();
    if ($stmt) $stmt->close();
    if ($cn) $cn->close();

    echo json_encode([
        'echo'                => 1,
        'totalrecords'        => count($data),
        'totaldisplayrecords' => count($data),
        'data'                => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('Error llenarDataTableReporteVentas.php: '.$e->getMessage());

    echo json_encode([
        'echo'                => 1,
        'totalrecords'        => 0,
        'totaldisplayrecords' => 0,
        'data'                => [],
        'error'               => 'Error al cargar el reporte de ventas: '.$e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
