<?php
// core/llenarDataTableReporteVentas.php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

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

$datos = [
    'tipo_factura_reporte' => $_POST['tipo_factura_reporte'] ?? 1,
    'fechai'               => $_POST['fechai'] ?? date('Y-m-01'),
    'fechaf'               => $_POST['fechaf'] ?? date('Y-m-d'),
    'facturador'           => $_POST['facturador'] ?? '',
    'vendedor'             => $_POST['vendedor'] ?? '',
    'factura'              => $_POST['factura'] ?? 1,
    'empresa_id_sd'        => $_SESSION['empresa_id_sd'] ?? 0,
];

$result = $insMainModel->consultaVentas($datos);

$data = [];
$cn = $insMainModel->connection();
if (!$cn) {
    echo json_encode(['echo'=>1,'totalrecords'=>0,'totaldisplayrecords'=>0,'data'=>[]]);
    exit;
}

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

/* Fallback: estado de CxC por facturas_id  (1=Pendiente, 2=Pago Realizado) */
$stmtCxC = $cn->prepare("
    SELECT estado
    FROM cobrar_clientes
    WHERE facturas_id = ?
    ORDER BY cobrar_clientes_id DESC
    LIMIT 1
");

while ($row = $result->fetch_assoc()) {
    $facturas_id  = (int)($row['facturas_id'] ?? 0);
    $documento_id = (int)($row['documento_id'] ?? 0); // 4 = Proforma

    // Ganancia
    $ganancia = (double)($row['subtotal'] ?? 0)
              - (double)($row['subCosto'] ?? 0)
              - (double)($row['isv'] ?? 0)
              - (double)($row['descuento'] ?? 0);

    // Pagos
    $monto_pagado = 0.0;
    if ($facturas_id > 0 && $stmtPago) {
        $stmtPago->bind_param("i", $facturas_id);
        $stmtPago->execute();
        $resP = $stmtPago->get_result();
        if ($resP && $pRow = $resP->fetch_assoc()) $monto_pagado = (float)$pRow['pagado'];
        if ($resP) $resP->free();
    }

    // PROFORMA: 1 = ABIERTA, 2 = CERRADA (nunca 0)
    $proforma_estado      = 1; // por defecto ABIERTA
    $facturas_proforma_id = 0;

    if ($documento_id === 4 && $facturas_id > 0) {
        // Intentar tomar de facturas_proforma
        if ($stmtProforma) {
            $stmtProforma->bind_param("i", $facturas_id);
            $stmtProforma->execute();
            $resF = $stmtProforma->get_result();
            if ($resF && $fRow = $resF->fetch_assoc()) {
                $facturas_proforma_id = (int)$fRow['facturas_proforma_id'];
                // Si tu tabla usa 1=Abierta y 2=Cerrada déjalo así:
                $proforma_estado = (int)$fRow['estado'];
                if ($proforma_estado !== 1 && $proforma_estado !== 2) {
                    // valores inesperados => ABIERTA por defecto
                    $proforma_estado = 1;
                }
            } else {
                // Fallback: mirar cobrar_clientes
                if ($stmtCxC) {
                    $stmtCxC->bind_param("i", $facturas_id);
                    $stmtCxC->execute();
                    $resC = $stmtCxC->get_result();
                    if ($resC && $cRow = $resC->fetch_assoc()) {
                        $proforma_estado = ((int)$cRow['estado'] === 2) ? 2 : 1; // 2=>CERRADA
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

        // PROFORMA (regla final: 1 abierta / 2 cerrada)
        'documento_id'         => $documento_id,
        'proforma_estado'      => $proforma_estado,
        'facturas_proforma_id' => $facturas_proforma_id,

        // Pagos info (para estado de facturas normales)
        'estado_pago'          => $row['estado_pago'] ?? null,
        'tipo_factura'         => $row['tipo_factura'] ?? null,
        'tiene_pago'           => ($monto_pagado > 0 ? 1 : 0),
        'monto_pagado'         => $monto_pagado,
    ];
}

if ($stmtPago)     $stmtPago->close();
if ($stmtProforma) $stmtProforma->close();
if ($stmtCxC)      $stmtCxC->close();

echo json_encode([
    'echo'                => 1,
    'totalrecords'        => count($data),
    'totaldisplayrecords' => count($data),
    'data'                => $data
]);
