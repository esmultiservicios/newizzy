<?php
// core/caja/getDesgloseGananciaCaja.php
$peticionAjax = true;

require_once __DIR__ . '/../configGenerales.php';
require_once __DIR__ . '/../mainModel.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION)) {
    session_start(['name' => 'SD']);
}

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

function fechaGananciaValida($fecha) {
    return is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$modo = isset($_POST['modo']) ? trim($_POST['modo']) : 'caja';
$apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : 0;
$fechai = isset($_POST['fechai']) ? trim($_POST['fechai']) : date('Y-m-d');
$fechaf = isset($_POST['fechaf']) ? trim($_POST['fechaf']) : date('Y-m-d');

$solo_mi_caja = isset($_POST['solo_mi_caja']) ? (int)$_POST['solo_mi_caja'] : 0;
$origen = isset($_POST['origen']) ? trim($_POST['origen']) : '';

if ($modo !== 'caja' && $modo !== 'periodo') {
    $modo = 'caja';
}

if (!fechaGananciaValida($fechai)) {
    $fechai = date('Y-m-d');
}

if (!fechaGananciaValida($fechaf)) {
    $fechaf = $fechai;
}

if ($fechai > $fechaf) {
    $tmp = $fechai;
    $fechai = $fechaf;
    $fechaf = $tmp;
}

if ($empresa_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar la empresa de la sesión.',
        'resumen' => [],
        'detalles' => []
    ]);
    exit;
}

if ($solo_mi_caja === 1 && $origen === 'facturacion' && $colaboradores_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar el usuario de la sesión.',
        'resumen' => [],
        'detalles' => []
    ]);
    exit;
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

$fecha_caja = $fechai;
$estado_caja = 0;
$texto_estado_caja = 'Período';

$whereApertura = " a.empresa_id = '$empresa_id' ";
$whereFacturas = " f.empresa_id = '$empresa_id' AND f.estado IN (2,3) ";
$whereRetiros = " cr.empresa_id = '$empresa_id' AND cr.estado = 1 ";
$whereRetirosPendientes = " cr.empresa_id = '$empresa_id' AND cr.estado = 1 AND IFNULL(cr.egresos_id, 0) = 0 ";
$whereEgresosGastos = " e.empresa_id = '$empresa_id' AND e.estado = 1 AND e.tipo_egreso = 2 ";
$whereOtrosIngresos = " i.empresa_id = '$empresa_id' AND i.estado = 1 AND i.tipo_ingreso = 2 ";

if ($modo === 'caja') {
    $sqlValidarApertura = "
        SELECT apertura_id, fecha, estado, apertura
        FROM apertura
        WHERE apertura_id = '$apertura_id'
          AND empresa_id = '$empresa_id'
    ";

    if ($solo_mi_caja === 1 && $origen === 'facturacion') {
        $sqlValidarApertura .= " AND colaboradores_id = '$colaboradores_id' ";
    }

    $sqlValidarApertura .= " LIMIT 1 ";

    $resValidarApertura = $insMainModel->ejecutar_consulta_simple($sqlValidarApertura);

    if (!$resValidarApertura || $resValidarApertura->num_rows <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'La apertura de caja no existe, no pertenece a la empresa actual o no pertenece al usuario de la sesión.',
            'resumen' => [],
            'detalles' => []
        ]);
        exit;
    }

    $rowAperturaCaja = $resValidarApertura->fetch_assoc();
    $fecha_caja = $rowAperturaCaja['fecha'];
    $estado_caja = (int)$rowAperturaCaja['estado'];
    $texto_estado_caja = ($estado_caja === 1) ? 'Abierta' : 'Cerrada';

    $whereApertura .= " AND a.apertura_id = '$apertura_id' ";
    $whereFacturas .= " AND f.apertura_id = '$apertura_id' ";
    $whereRetiros .= " AND cr.apertura_id = '$apertura_id' ";
    $whereRetirosPendientes .= " AND cr.apertura_id = '$apertura_id' ";
    $whereEgresosGastos .= " AND e.fecha = '$fecha_caja' ";
    $whereOtrosIngresos .= " AND i.fecha = '$fecha_caja' ";
} else {
    $whereApertura .= " AND a.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereFacturas .= " AND f.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereRetiros .= " AND cr.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereRetirosPendientes .= " AND cr.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereEgresosGastos .= " AND e.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereOtrosIngresos .= " AND i.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

if ($solo_mi_caja === 1 && $origen === 'facturacion') {
    $whereApertura .= " AND a.colaboradores_id = '$colaboradores_id' ";

    $whereFacturas .= "
        AND EXISTS (
            SELECT 1
            FROM apertura ax
            WHERE ax.apertura_id = f.apertura_id
              AND ax.empresa_id = f.empresa_id
              AND ax.colaboradores_id = '$colaboradores_id'
        )
    ";

    $whereRetiros .= "
        AND EXISTS (
            SELECT 1
            FROM apertura ar
            WHERE ar.apertura_id = cr.apertura_id
              AND ar.empresa_id = cr.empresa_id
              AND ar.colaboradores_id = '$colaboradores_id'
        )
    ";

    $whereRetirosPendientes .= "
        AND EXISTS (
            SELECT 1
            FROM apertura arp
            WHERE arp.apertura_id = cr.apertura_id
              AND arp.empresa_id = cr.empresa_id
              AND arp.colaboradores_id = '$colaboradores_id'
        )
    ";

    $whereEgresosGastos .= " AND e.colaboradores_id = '$colaboradores_id' ";
    $whereOtrosIngresos .= " AND i.colaboradores_id = '$colaboradores_id' ";
}

$sqlApertura = "
    SELECT COALESCE(SUM(a.apertura), 0) AS monto_apertura
    FROM apertura a
    WHERE $whereApertura
";

$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

$monto_apertura = 0;

if ($resApertura && $resApertura->num_rows > 0) {
    $rowApertura = $resApertura->fetch_assoc();
    $monto_apertura = (float)$rowApertura['monto_apertura'];
}

$sqlTotalFacturado = "
    SELECT COALESCE(SUM(f.importe), 0) AS total_facturado
    FROM facturas f
    WHERE $whereFacturas
";

$resTotalFacturado = $insMainModel->ejecutar_consulta_simple($sqlTotalFacturado);

$total_facturado = 0;

if ($resTotalFacturado && $resTotalFacturado->num_rows > 0) {
    $rowTotalFacturado = $resTotalFacturado->fetch_assoc();
    $total_facturado = (float)$rowTotalFacturado['total_facturado'];
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

$sqlOtrosIngresos = "
    SELECT
        COALESCE(SUM(i.total), 0) AS total_ingresos_registrados,

        COALESCE(SUM(
            CASE
                WHEN IFNULL(c.es_inversion, 0) = 1
                THEN i.total
                ELSE 0
            END
        ), 0) AS ingreso_inversion,

        COALESCE(SUM(
            CASE
                WHEN IFNULL(c.es_inversion, 0) = 1
                  OR UPPER(IFNULL(c.nombre, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(c.nombre, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(c.nombre, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(c.nombre, '')) LIKE '%REPOSICIÓN%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%REPOSICIÓN%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%CIERRE DE CAJA%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%INGRESOS POR VENTA%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%CUENTA DE INVERSION%'
                  OR UPPER(IFNULL(i.observacion, '')) LIKE '%CUENTA DE INVERSIÓN%'
                THEN 0
                ELSE i.total
            END
        ), 0) AS otros_ingresos

    FROM ingresos i
    LEFT JOIN cuentas c
        ON c.cuentas_id = i.cuentas_id
    WHERE $whereOtrosIngresos
";

$resOtrosIngresos = $insMainModel->ejecutar_consulta_simple($sqlOtrosIngresos);

$total_ingresos_registrados = 0;
$otros_ingresos = 0;
$ingreso_inversion = 0;

if ($resOtrosIngresos && $resOtrosIngresos->num_rows > 0) {
    $rowOtrosIngresos = $resOtrosIngresos->fetch_assoc();

    $total_ingresos_registrados = (float)$rowOtrosIngresos['total_ingresos_registrados'];
    $otros_ingresos = (float)$rowOtrosIngresos['otros_ingresos'];
    $ingreso_inversion = (float)$rowOtrosIngresos['ingreso_inversion'];
}

$sqlGastos = "
    SELECT
        COALESCE(SUM(e.total), 0) AS total_egresos_registrados,

        COALESCE(SUM(
            CASE
                WHEN IFNULL(cg.es_inversion, 0) = 1
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%REPOSICIÓN%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%REPOSICIÓN%'
                THEN e.total
                ELSE 0
            END
        ), 0) AS egreso_inversion_apartada,

        COALESCE(SUM(
            CASE
                WHEN IFNULL(cg.es_inversion, 0) = 1
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(cg.nombre, '')) LIKE '%REPOSICIÓN%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%INVERSION%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%INVERSIÓN%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%REPOSICION%'
                  OR UPPER(IFNULL(e.observacion, '')) LIKE '%REPOSICIÓN%'
                THEN 0
                ELSE e.total
            END
        ), 0) AS total_gastos_reales

    FROM egresos e
    LEFT JOIN categoria_gastos cg
        ON cg.categoria_gastos_id = e.categoria_gastos_id
    WHERE $whereEgresosGastos
";

$resGastos = $insMainModel->ejecutar_consulta_simple($sqlGastos);

$total_egresos_registrados = 0;
$egreso_inversion_apartada = 0;
$total_gastos = 0;

if ($resGastos && $resGastos->num_rows > 0) {
    $rowGastos = $resGastos->fetch_assoc();

    $total_egresos_registrados = (float)$rowGastos['total_egresos_registrados'];
    $egreso_inversion_apartada = (float)$rowGastos['egreso_inversion_apartada'];
    $total_gastos = (float)$rowGastos['total_gastos_reales'];
}

$total_inversion_apartada = $ingreso_inversion;

$sqlRetirosTotal = "
    SELECT COALESCE(SUM(cr.monto), 0) AS retiro_caja_total
    FROM caja_retiros cr
    WHERE $whereRetiros
";

$resRetirosTotal = $insMainModel->ejecutar_consulta_simple($sqlRetirosTotal);

$retiro_caja_total = 0;

if ($resRetirosTotal && $resRetirosTotal->num_rows > 0) {
    $rowRetirosTotal = $resRetirosTotal->fetch_assoc();
    $retiro_caja_total = (float)$rowRetirosTotal['retiro_caja_total'];
}

$sqlRetirosPendientes = "
    SELECT COALESCE(SUM(cr.monto), 0) AS retiro_caja_pendiente
    FROM caja_retiros cr
    WHERE $whereRetirosPendientes
";

$resRetirosPendientes = $insMainModel->ejecutar_consulta_simple($sqlRetirosPendientes);

$retiro_caja_pendiente = 0;

if ($resRetirosPendientes && $resRetirosPendientes->num_rows > 0) {
    $rowRetirosPendientes = $resRetirosPendientes->fetch_assoc();
    $retiro_caja_pendiente = (float)$rowRetirosPendientes['retiro_caja_pendiente'];
}

$sqlRetirosConvertidos = "
    SELECT COALESCE(SUM(cr.monto), 0) AS retiro_caja_convertido_gasto
    FROM caja_retiros cr
    WHERE $whereRetiros
      AND IFNULL(cr.egresos_id, 0) > 0
";

$resRetirosConvertidos = $insMainModel->ejecutar_consulta_simple($sqlRetirosConvertidos);

$retiro_caja_convertido_gasto = 0;

if ($resRetirosConvertidos && $resRetirosConvertidos->num_rows > 0) {
    $rowRetirosConvertidos = $resRetirosConvertidos->fetch_assoc();
    $retiro_caja_convertido_gasto = (float)$rowRetirosConvertidos['retiro_caja_convertido_gasto'];
}

$pendiente_cobro = $total_facturado - $total_cobrado;

if ($pendiente_cobro < 0) {
    $pendiente_cobro = 0;
}

$neto_disponible = ($total_cobrado + $otros_ingresos) - $total_gastos - $retiro_caja_pendiente;

$efectivo_esperado_caja = ($monto_apertura + $efectivo) - $retiro_caja_pendiente;

if ($efectivo_esperado_caja < 0) {
    $efectivo_esperado_caja = 0;
}

$dinero_recomendado_guardar = $costo_productos_vendidos;
$dinero_despues_reponer = $total_cobrado - $costo_productos_vendidos;

$porcentaje_costo = 0;
$porcentaje_ganancia = 0;

if ($total_vendido_detalle > 0) {
    $porcentaje_costo = ($costo_productos_vendidos / $total_vendido_detalle) * 100;
    $porcentaje_ganancia = ($ganancia_bruta / $total_vendido_detalle) * 100;
}

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
        'estado_caja' => $estado_caja,
        'texto_estado_caja' => $texto_estado_caja,
        'fecha_caja' => $fecha_caja,
        'monto_apertura' => $monto_apertura,
        'total_facturado' => $total_facturado,
        'total_cobrado' => $total_cobrado,
        'total_vendido' => $total_cobrado,
        'pendiente_cobro' => $pendiente_cobro,
        'efectivo' => $efectivo,
        'tarjeta' => $tarjeta,
        'transferencia' => $transferencia,
        'cheque' => $cheque,
        'total_ingresos_registrados' => $total_ingresos_registrados,
        'otros_ingresos' => $otros_ingresos,
        'ingreso_inversion' => $ingreso_inversion,
        'total_gastos' => $total_gastos,
        'total_gastos_reales' => $total_gastos,
        'total_egresos_registrados' => $total_egresos_registrados,
        'total_inversion_apartada' => $total_inversion_apartada,
        'egreso_inversion_apartada' => $egreso_inversion_apartada,
        'retiro_caja' => $retiro_caja_total,
        'retiro_caja_total' => $retiro_caja_total,
        'retiro_caja_pendiente' => $retiro_caja_pendiente,
        'retiro_caja_convertido_gasto' => $retiro_caja_convertido_gasto,
        'neto_disponible' => $neto_disponible,
        'efectivo_esperado_caja' => $efectivo_esperado_caja,
        'costo_productos_vendidos' => $costo_productos_vendidos,
        'total_vendido_detalle' => $total_vendido_detalle,
        'ganancia_bruta' => $ganancia_bruta,
        'dinero_recomendado_guardar' => $dinero_recomendado_guardar,
        'dinero_despues_reponer' => $dinero_despues_reponer,
        'porcentaje_costo' => $porcentaje_costo,
        'porcentaje_ganancia' => $porcentaje_ganancia,
        'diferencia_conciliacion' => $diferencia_conciliacion
    ],
    'detalles' => $detalles
]);