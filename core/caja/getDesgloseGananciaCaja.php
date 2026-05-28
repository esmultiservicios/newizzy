<?php
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

$insMainModel = new mainModel();

if (method_exists($insMainModel, 'validarSesion')) {
    $validacion = $insMainModel->validarSesion();

    if (!empty($validacion['error'])) {
        echo json_encode([
            'success' => false,
            'message' => $validacion['mensaje'] ?? 'Sesión inválida',
            'resumen' => [],
            'detalles' => []
        ]);
        exit;
    }
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

$modo = isset($_POST['modo']) ? trim($_POST['modo']) : 'caja';
$apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : 0;
$fechai = isset($_POST['fechai']) ? trim($_POST['fechai']) : date('Y-m-d');
$fechaf = isset($_POST['fechaf']) ? trim($_POST['fechaf']) : date('Y-m-d');

if ($modo !== 'caja' && $modo !== 'periodo') {
    $modo = 'caja';
}

if ($modo === 'caja' && $apertura_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió una apertura de caja válida.',
        'resumen' => [],
        'detalles' => []
    ]);
    exit;
}

if ($fechai === '') {
    $fechai = date('Y-m-d');
}

if ($fechaf === '') {
    $fechaf = $fechai;
}

$whereFacturas = " f.estado IN (2,3) ";

if ($modo === 'caja') {
    $whereFacturas .= " AND f.apertura_id = '$apertura_id' ";
} else {
    $whereFacturas .= " AND f.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

$whereRetiros = " cr.empresa_id = '$empresa_id' AND cr.estado = 1 ";

if ($modo === 'caja') {
    $whereRetiros .= " AND cr.apertura_id = '$apertura_id' ";
} else {
    $whereRetiros .= " AND cr.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

$sqlApertura = "
    SELECT COALESCE(SUM(a.apertura), 0) AS monto_apertura
    FROM apertura a
    WHERE a.empresa_id = '$empresa_id'
";

if ($modo === 'caja') {
    $sqlApertura .= " AND a.apertura_id = '$apertura_id' ";
} else {
    $sqlApertura .= " AND a.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

$monto_apertura = 0;

if ($resApertura && $resApertura->num_rows > 0) {
    $rowApertura = $resApertura->fetch_assoc();
    $monto_apertura = (float)$rowApertura['monto_apertura'];
}

$sqlTotalFacturado = "
    SELECT 
        COALESCE(SUM(f.importe), 0) AS total_facturado
    FROM facturas f
    WHERE $whereFacturas
";

$resTotalFacturado = $insMainModel->ejecutar_consulta_simple($sqlTotalFacturado);

$total_facturado = 0;

if ($resTotalFacturado && $resTotalFacturado->num_rows > 0) {
    $rowTotalFacturado = $resTotalFacturado->fetch_assoc();
    $total_facturado = (float)$rowTotalFacturado['total_facturado'];
}

$sqlResumenInventario = "
    SELECT
        COALESCE(SUM(fd.cantidad * fd.costo_unitario), 0) AS costo_productos_vendidos,
        COALESCE(SUM(fd.cantidad * fd.precio), 0) AS total_vendido_detalle,
        COALESCE(SUM((fd.cantidad * fd.precio) - (fd.cantidad * fd.costo_unitario)), 0) AS ganancia_bruta
    FROM facturas f
    INNER JOIN facturas_detalles fd
        ON fd.facturas_id = f.facturas_id
    WHERE $whereFacturas
";

$resResumenInventario = $insMainModel->ejecutar_consulta_simple($sqlResumenInventario);

$costo_productos_vendidos = 0;
$total_vendido_detalle = 0;
$ganancia_bruta = 0;

if ($resResumenInventario && $resResumenInventario->num_rows > 0) {
    $rowResumenInventario = $resResumenInventario->fetch_assoc();

    $costo_productos_vendidos = (float)$rowResumenInventario['costo_productos_vendidos'];
    $total_vendido_detalle = (float)$rowResumenInventario['total_vendido_detalle'];
    $ganancia_bruta = (float)$rowResumenInventario['ganancia_bruta'];
}

$sqlPagos = "
    SELECT
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 1 THEN pd.efectivo ELSE 0 END), 0) AS efectivo,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 2 THEN pd.efectivo ELSE 0 END), 0) AS tarjeta,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 3 THEN pd.efectivo ELSE 0 END), 0) AS transferencia,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 4 THEN pd.efectivo ELSE 0 END), 0) AS cheque,
        COALESCE(SUM(pd.efectivo), 0) AS total_cobrado
    FROM pagos pg
    INNER JOIN facturas f
        ON f.facturas_id = pg.facturas_id
    INNER JOIN pagos_detalles pd
        ON pd.pagos_id = pg.pagos_id
    WHERE $whereFacturas
      AND pg.estado = 1
";

$resPagos = $insMainModel->ejecutar_consulta_simple($sqlPagos);

$efectivo = 0;
$tarjeta = 0;
$transferencia = 0;
$cheque = 0;
$total_cobrado = 0;

if ($resPagos && $resPagos->num_rows > 0) {
    $rowPagos = $resPagos->fetch_assoc();

    $efectivo = (float)$rowPagos['efectivo'];
    $tarjeta = (float)$rowPagos['tarjeta'];
    $transferencia = (float)$rowPagos['transferencia'];
    $cheque = (float)$rowPagos['cheque'];
    $total_cobrado = (float)$rowPagos['total_cobrado'];
}

$sqlRetiros = "
    SELECT COALESCE(SUM(cr.monto), 0) AS retiro_caja
    FROM caja_retiros cr
    WHERE $whereRetiros
";

$resRetiros = $insMainModel->ejecutar_consulta_simple($sqlRetiros);

$retiro_caja = 0;

if ($resRetiros && $resRetiros->num_rows > 0) {
    $rowRetiros = $resRetiros->fetch_assoc();
    $retiro_caja = (float)$rowRetiros['retiro_caja'];
}

$pendiente_cobro = $total_facturado - $total_cobrado;

if ($pendiente_cobro < 0) {
    $pendiente_cobro = 0;
}

$efectivo_esperado_caja = ($monto_apertura + $efectivo) - $retiro_caja;

if ($efectivo_esperado_caja < 0) {
    $efectivo_esperado_caja = 0;
}

$dinero_despues_reponer = $total_cobrado - $costo_productos_vendidos;

if ($dinero_despues_reponer < 0) {
    $dinero_despues_reponer = 0;
}

$efectivo_despues_reponer = $efectivo - $retiro_caja - $costo_productos_vendidos;

$diferencia_conciliacion = $total_facturado - $total_vendido_detalle;

$sqlDetalles = "
    SELECT
        f.number AS factura,
        p.nombre AS producto,
        fd.cantidad,
        fd.costo_unitario,
        fd.precio AS precio_venta,
        (fd.cantidad * fd.costo_unitario) AS total_costo,
        (fd.cantidad * fd.precio) AS total_venta,
        ((fd.cantidad * fd.precio) - (fd.cantidad * fd.costo_unitario)) AS ganancia
    FROM facturas f
    INNER JOIN facturas_detalles fd
        ON fd.facturas_id = f.facturas_id
    INNER JOIN productos p
        ON p.productos_id = fd.productos_id
    WHERE $whereFacturas
    ORDER BY f.facturas_id ASC, p.nombre ASC
";

$resDetalles = $insMainModel->ejecutar_consulta_simple($sqlDetalles);

$detalles = [];

if ($resDetalles) {
    while ($row = $resDetalles->fetch_assoc()) {
        $detalles[] = [
            'factura' => $row['factura'],
            'producto' => $row['producto'],
            'cantidad' => (float)$row['cantidad'],
            'costo_unitario' => (float)$row['costo_unitario'],
            'precio_venta' => (float)$row['precio_venta'],
            'total_costo' => (float)$row['total_costo'],
            'total_venta' => (float)$row['total_venta'],
            'ganancia' => (float)$row['ganancia']
        ];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Desglose cargado correctamente.',
    'resumen' => [
        'modo' => $modo,
        'monto_apertura' => $monto_apertura,
        'total_facturado' => $total_facturado,
        'total_cobrado' => $total_cobrado,
        'pendiente_cobro' => $pendiente_cobro,
        'efectivo' => $efectivo,
        'tarjeta' => $tarjeta,
        'transferencia' => $transferencia,
        'cheque' => $cheque,
        'retiro_caja' => $retiro_caja,
        'efectivo_esperado_caja' => $efectivo_esperado_caja,
        'costo_productos_vendidos' => $costo_productos_vendidos,
        'total_vendido_detalle' => $total_vendido_detalle,
        'ganancia_bruta' => $ganancia_bruta,
        'dinero_recomendado_guardar' => $costo_productos_vendidos,
        'dinero_despues_reponer' => $dinero_despues_reponer,
        'efectivo_despues_reponer' => $efectivo_despues_reponer,
        'diferencia_conciliacion' => $diferencia_conciliacion
    ],
    'detalles' => $detalles
]);