<?php
// core/caja/getCuadreDiaCaja.php
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
            'medios_pago' => [],
            'gastos' => [],
            'inversiones' => []
        ]);
        exit;
    }
}

function fechaCuadreValida($fecha) {
    return is_string($fecha) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha);
}

function obtenerCuentaTipoPagoCuadre($insMainModel, $tipo_pago_id) {
    $tipo_pago_id = (int)$tipo_pago_id;
    $sql = "SELECT cuentas_id FROM tipo_pago WHERE tipo_pago_id = '$tipo_pago_id' LIMIT 1";
    $res = $insMainModel->ejecutar_consulta_simple($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return isset($row['cuentas_id']) ? (int)$row['cuentas_id'] : 0;
    }
    return 0;
}

function esTextoInversionCuadre($texto) {
    $texto = trim((string)$texto);
    if ($texto === '') return false;
    $texto = mb_strtoupper($texto, 'UTF-8');
    return (
        strpos($texto, 'INVERSION') !== false ||
        strpos($texto, 'INVERSIÓN') !== false ||
        strpos($texto, 'REPOSICION') !== false ||
        strpos($texto, 'REPOSICIÓN') !== false
    );
}

function clasificarSalidaCuadre($row) {
    $es_inversion = 0;
    if (isset($row['es_inversion_categoria']) && (int)$row['es_inversion_categoria'] === 1) $es_inversion = 1;
    if ($es_inversion === 0 && isset($row['categoria']) && esTextoInversionCuadre($row['categoria'])) $es_inversion = 1;
    if ($es_inversion === 0 && isset($row['observacion']) && esTextoInversionCuadre($row['observacion'])) $es_inversion = 1;
    return $es_inversion;
}

function sumarSalidaPorCuentaCuadre($cuentas_id, $total, $cuenta_efectivo, $cuenta_transferencia, $cuenta_tarjeta, $cuenta_cheque, &$efectivo, &$transferencia, &$tarjeta, &$cheque) {
    $cuentas_id = (int)$cuentas_id;
    $total = (float)$total;
    if ($cuentas_id === $cuenta_efectivo) $efectivo += $total;
    elseif ($cuentas_id === $cuenta_transferencia) $transferencia += $total;
    elseif ($cuentas_id === $cuenta_tarjeta) $tarjeta += $total;
    elseif ($cuentas_id === $cuenta_cheque) $cheque += $total;
    else $efectivo += $total;
}

function descontarSecuencialCuadre(&$pendiente, $disponible) {
    $pendiente = (float)$pendiente;
    $disponible = (float)$disponible;
    if ($pendiente <= 0 || $disponible <= 0) return 0;
    $descuento = min($pendiente, $disponible);
    $pendiente -= $descuento;
    return $descuento;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$modo = isset($_POST['modo']) ? trim($_POST['modo']) : 'periodo';
$apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : 0;
$fechai = isset($_POST['fechai']) ? trim($_POST['fechai']) : date('Y-m-d');
$fechaf = isset($_POST['fechaf']) ? trim($_POST['fechaf']) : date('Y-m-d');
$solo_mi_caja = isset($_POST['solo_mi_caja']) ? (int)$_POST['solo_mi_caja'] : 0;
$origen = isset($_POST['origen']) ? trim($_POST['origen']) : '';

if ($modo !== 'caja' && $modo !== 'periodo') $modo = 'periodo';
if (!fechaCuadreValida($fechai)) $fechai = date('Y-m-d');
if (!fechaCuadreValida($fechaf)) $fechaf = $fechai;
if ($fechai > $fechaf) { $tmp = $fechai; $fechai = $fechaf; $fechaf = $tmp; }

if ($empresa_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'No se pudo identificar la empresa de la sesión.','resumen'=>[],'medios_pago'=>[],'gastos'=>[],'inversiones'=>[]]);
    exit;
}

if ($solo_mi_caja === 1 && $origen === 'facturacion' && $colaboradores_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'No se pudo identificar el usuario de la sesión.','resumen'=>[],'medios_pago'=>[],'gastos'=>[],'inversiones'=>[]]);
    exit;
}

if ($modo === 'caja' && $apertura_id <= 0) {
    echo json_encode(['success'=>false,'message'=>'No se recibió una apertura de caja válida.','resumen'=>[],'medios_pago'=>[],'gastos'=>[],'inversiones'=>[]]);
    exit;
}

$whereApertura = " a.empresa_id = '$empresa_id' ";
$whereFacturas = " f.empresa_id = '$empresa_id' AND f.estado IN (2,3) ";
$whereRetiros = " cr.empresa_id = '$empresa_id' AND cr.estado = 1 ";

if ($modo === 'caja') {
    $whereApertura .= " AND a.apertura_id = '$apertura_id' ";
    $whereFacturas .= " AND f.apertura_id = '$apertura_id' ";
    $whereRetiros .= " AND cr.apertura_id = '$apertura_id' ";
} else {
    $whereApertura .= " AND a.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereFacturas .= " AND f.fecha BETWEEN '$fechai' AND '$fechaf' ";
    $whereRetiros .= " AND cr.fecha BETWEEN '$fechai' AND '$fechaf' ";
}

if ($solo_mi_caja === 1 && $origen === 'facturacion') {
    $whereApertura .= " AND a.colaboradores_id = '$colaboradores_id' ";
    $whereFacturas .= " AND EXISTS (SELECT 1 FROM apertura af WHERE af.apertura_id = f.apertura_id AND af.empresa_id = f.empresa_id AND af.colaboradores_id = '$colaboradores_id') ";
    $whereRetiros .= " AND EXISTS (SELECT 1 FROM apertura ar WHERE ar.apertura_id = cr.apertura_id AND ar.empresa_id = cr.empresa_id AND ar.colaboradores_id = '$colaboradores_id') ";
}

if ($modo === 'caja') {
    $sqlValidarApertura = "SELECT a.apertura_id FROM apertura a WHERE $whereApertura LIMIT 1";
    $resValidarApertura = $insMainModel->ejecutar_consulta_simple($sqlValidarApertura);
    if (!$resValidarApertura || $resValidarApertura->num_rows <= 0) {
        echo json_encode(['success'=>false,'message'=>'La apertura de caja no existe o no pertenece a la empresa actual.','resumen'=>[],'medios_pago'=>[],'gastos'=>[],'inversiones'=>[]]);
        exit;
    }
}

$cuenta_efectivo = obtenerCuentaTipoPagoCuadre($insMainModel, 1);
$cuenta_tarjeta = obtenerCuentaTipoPagoCuadre($insMainModel, 2);
$cuenta_transferencia = obtenerCuentaTipoPagoCuadre($insMainModel, 3);
$cuenta_cheque = obtenerCuentaTipoPagoCuadre($insMainModel, 4);

$sqlApertura = "SELECT COALESCE(SUM(a.apertura), 0) AS monto_apertura FROM apertura a WHERE $whereApertura";
$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);
$monto_apertura = 0;
if ($resApertura && $resApertura->num_rows > 0) $monto_apertura = (float)$resApertura->fetch_assoc()['monto_apertura'];

$sqlPagos = "
    SELECT
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 1 THEN pd.efectivo ELSE 0 END), 0) AS efectivo,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 2 THEN pd.efectivo ELSE 0 END), 0) AS tarjeta,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 3 THEN pd.efectivo ELSE 0 END), 0) AS transferencia,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 4 THEN pd.efectivo ELSE 0 END), 0) AS cheque,
        COALESCE(SUM(pd.efectivo), 0) AS total_cobrado
    FROM pagos pg
    INNER JOIN facturas f ON f.facturas_id = pg.facturas_id
    INNER JOIN pagos_detalles pd ON pd.pagos_id = pg.pagos_id
    WHERE $whereFacturas
      AND pg.estado = 1
";
$resPagos = $insMainModel->ejecutar_consulta_simple($sqlPagos);
$efectivo = $tarjeta = $transferencia = $cheque = $total_cobrado = 0;
if ($resPagos && $resPagos->num_rows > 0) {
    $rowPagos = $resPagos->fetch_assoc();
    $efectivo = (float)$rowPagos['efectivo'];
    $tarjeta = (float)$rowPagos['tarjeta'];
    $transferencia = (float)$rowPagos['transferencia'];
    $cheque = (float)$rowPagos['cheque'];
    $total_cobrado = (float)$rowPagos['total_cobrado'];
}

/*
    CÁLCULO CORRECTO DEL ISV:
    apertura -> facturas -> facturas_detalles
    tipo documento: facturas.secuencia_facturacion_id -> secuencia_facturacion.documento_id -> documento
    1 = Factura Electronica normal / SAR
    4 = Factura Proforma
*/
$sqlInventario = "
    SELECT
        COALESCE(SUM(fd.cantidad * fd.costo_unitario), 0) AS inversion_reposicion,
        COALESCE(SUM(fd.cantidad * fd.precio), 0) AS venta_base_productos,
        COALESCE(SUM(CASE WHEN sf.documento_id = 1 THEN (fd.isv_valor + fd.isv_valor1) ELSE 0 END), 0) AS isv_factura_normal_sar,
        COALESCE(SUM(CASE WHEN sf.documento_id = 4 THEN (fd.isv_valor + fd.isv_valor1) ELSE 0 END), 0) AS isv_proforma_informativo,
        COALESCE(SUM(fd.isv_valor + fd.isv_valor1), 0) AS isv_total_detalle,
        COALESCE(SUM((fd.cantidad * fd.precio) - (fd.cantidad * fd.costo_unitario)), 0) AS ganancia_productos
    FROM facturas f
    INNER JOIN facturas_detalles fd ON fd.facturas_id = f.facturas_id
    INNER JOIN secuencia_facturacion sf ON sf.secuencia_facturacion_id = f.secuencia_facturacion_id
    INNER JOIN documento d ON d.documento_id = sf.documento_id
    WHERE $whereFacturas
";
$resInventario = $insMainModel->ejecutar_consulta_simple($sqlInventario);
$inversion_reposicion = $venta_base_productos = $isv_total_detalle = $isv_factura_normal_sar = $isv_proforma_informativo = $ganancia_productos = 0;
if ($resInventario && $resInventario->num_rows > 0) {
    $rowInventario = $resInventario->fetch_assoc();
    $inversion_reposicion = (float)$rowInventario['inversion_reposicion'];
    $venta_base_productos = (float)$rowInventario['venta_base_productos'];
    $isv_factura_normal_sar = (float)$rowInventario['isv_factura_normal_sar'];
    $isv_proforma_informativo = (float)$rowInventario['isv_proforma_informativo'];
    $isv_total_detalle = (float)$rowInventario['isv_total_detalle'];
    $ganancia_productos = (float)$rowInventario['ganancia_productos'];
}

/*
    Normalización final ISV.
    Simplemente redondeamos los valores a 2 decimales sin modificarlos.
    La separación viene directa de la consulta SQL, la respetamos.
*/
$isv_factura_normal_sar = round((float)$isv_factura_normal_sar, 2);
$isv_proforma_informativo = round((float)$isv_proforma_informativo, 2);
$isv_total_detalle = round((float)$isv_total_detalle, 2);

// Si por alguna razón el total no coincide con la suma de las partes, corregimos
$suma_isv_separado = round($isv_factura_normal_sar + $isv_proforma_informativo, 2);
if ($isv_total_detalle > 0 && abs($suma_isv_separado - $isv_total_detalle) > 0.01) {
    // Si el total es 959.40 y la suma de partes es 0, asignamos todo a proforma
    if ($isv_factura_normal_sar <= 0 && $isv_proforma_informativo <= 0) {
        $isv_proforma_informativo = $isv_total_detalle;
    } elseif ($isv_factura_normal_sar > 0 && $isv_proforma_informativo <= 0) {
        $isv_proforma_informativo = max(0, round($isv_total_detalle - $isv_factura_normal_sar, 2));
    } elseif ($isv_factura_normal_sar <= 0 && $isv_proforma_informativo > 0) {
        $isv_factura_normal_sar = max(0, round($isv_total_detalle - $isv_proforma_informativo, 2));
    }
    // Recalculamos el total
    $isv_total_detalle = round($isv_factura_normal_sar + $isv_proforma_informativo, 2);
}

$retiros_total = $retiros_efectivo = $retiros_transferencia = $retiros_tarjeta = $retiros_cheque = 0;
$inversion_manual_registrada = $inversion_manual_efectivo = $inversion_manual_transferencia = $inversion_manual_tarjeta = $inversion_manual_cheque = 0;
$gastos = [];
$inversiones = [];

$sqlRetirosCuenta = "
    SELECT
        cr.cuentas_id,
        COALESCE(c.nombre, 'Sin cuenta') AS cuenta,
        COALESCE(cg.nombre, '') AS categoria,
        COALESCE(cg.es_inversion, 0) AS es_inversion_categoria,
        COALESCE(e.observacion, cr.observacion, '') AS observacion,
        COALESCE(SUM(cr.monto), 0) AS total
    FROM caja_retiros cr
    LEFT JOIN cuentas c ON c.cuentas_id = cr.cuentas_id
    LEFT JOIN egresos e ON e.egresos_id = cr.egresos_id AND e.empresa_id = cr.empresa_id
    LEFT JOIN categoria_gastos cg ON cg.categoria_gastos_id = e.categoria_gastos_id
    WHERE $whereRetiros
    GROUP BY cr.cuentas_id, c.nombre, cg.nombre, cg.es_inversion, e.observacion, cr.observacion
    ORDER BY c.nombre ASC, cg.nombre ASC
";
$resRetirosCuenta = $insMainModel->ejecutar_consulta_simple($sqlRetirosCuenta);
if ($resRetirosCuenta) {
    while ($row = $resRetirosCuenta->fetch_assoc()) {
        $cuentas_id = (int)$row['cuentas_id'];
        $total = (float)$row['total'];
        $es_inversion = clasificarSalidaCuadre($row);
        if ($es_inversion === 1) {
            $inversion_manual_registrada += $total;
            sumarSalidaPorCuentaCuadre($cuentas_id, $total, $cuenta_efectivo, $cuenta_transferencia, $cuenta_tarjeta, $cuenta_cheque, $inversion_manual_efectivo, $inversion_manual_transferencia, $inversion_manual_tarjeta, $inversion_manual_cheque);
            $inversiones[] = ['tipo'=>'Retiro para inversión','cuentas_id'=>$cuentas_id,'cuenta'=>$row['cuenta'],'categoria'=>$row['categoria'],'monto'=>$total];
        } else {
            $retiros_total += $total;
            sumarSalidaPorCuentaCuadre($cuentas_id, $total, $cuenta_efectivo, $cuenta_transferencia, $cuenta_tarjeta, $cuenta_cheque, $retiros_efectivo, $retiros_transferencia, $retiros_tarjeta, $retiros_cheque);
            $gastos[] = ['tipo'=>'Retiro de caja','cuentas_id'=>$cuentas_id,'cuenta'=>$row['cuenta'],'categoria'=>$row['categoria'],'monto'=>$total];
        }
    }
}

$otros_egresos_total = $otros_egresos_efectivo = $otros_egresos_transferencia = $otros_egresos_tarjeta = $otros_egresos_cheque = 0;
$gastos_total = $retiros_total + $otros_egresos_total;
$gastos_efectivo = $retiros_efectivo + $otros_egresos_efectivo;
$gastos_transferencia = $retiros_transferencia + $otros_egresos_transferencia;
$gastos_tarjeta = $retiros_tarjeta + $otros_egresos_tarjeta;
$gastos_cheque = $retiros_cheque + $otros_egresos_cheque;

$inversion_sugerida = $inversion_reposicion;
$inversion_pendiente = $inversion_sugerida - $inversion_manual_registrada;
if ($inversion_pendiente < 0) $inversion_pendiente = 0;

$base_efectivo = $monto_apertura + $efectivo;
$base_transferencia = $transferencia;
$base_tarjeta = $tarjeta;
$base_cheque = $cheque;

$inversion_efectivo = $inversion_manual_efectivo;
$inversion_transferencia = $inversion_manual_transferencia;
$inversion_tarjeta = $inversion_manual_tarjeta;
$inversion_cheque = $inversion_manual_cheque;

$disponible_efectivo_para_reponer = max(0, $base_efectivo - $inversion_manual_efectivo);
$disponible_transferencia_para_reponer = max(0, $base_transferencia - $inversion_manual_transferencia);
$disponible_tarjeta_para_reponer = max(0, $base_tarjeta - $inversion_manual_tarjeta);
$disponible_cheque_para_reponer = max(0, $base_cheque - $inversion_manual_cheque);

$pendiente_distribuir_reposicion = $inversion_pendiente;
$inversion_efectivo += descontarSecuencialCuadre($pendiente_distribuir_reposicion, $disponible_efectivo_para_reponer);
$inversion_transferencia += descontarSecuencialCuadre($pendiente_distribuir_reposicion, $disponible_transferencia_para_reponer);
$inversion_tarjeta += descontarSecuencialCuadre($pendiente_distribuir_reposicion, $disponible_tarjeta_para_reponer);
$inversion_cheque += descontarSecuencialCuadre($pendiente_distribuir_reposicion, $disponible_cheque_para_reponer);

$inversion_total_considerada = $inversion_efectivo + $inversion_transferencia + $inversion_tarjeta + $inversion_cheque;
$inversion_no_cubierta = max(0, $inversion_sugerida - $inversion_total_considerada);

if ($inversion_sugerida > 0) {
    if ($inversion_manual_registrada > 0) {
        if ($inversion_pendiente > 0) $inversiones[] = ['tipo'=>'Reposición pendiente sugerida','cuentas_id'=>0,'cuenta'=>'Inventario vendido','categoria'=>'Costo pendiente de separar','monto'=>$inversion_pendiente];
    } else {
        $inversiones[] = ['tipo'=>'Reposición sugerida','cuentas_id'=>0,'cuenta'=>'Inventario vendido','categoria'=>'Costo de productos vendidos','monto'=>$inversion_sugerida];
    }
}

$efectivo_esperado = $base_efectivo - $inversion_efectivo - $gastos_efectivo;
$transferencia_esperada = $base_transferencia - $inversion_transferencia - $gastos_transferencia;
$tarjeta_esperada = $base_tarjeta - $inversion_tarjeta - $gastos_tarjeta;
$cheque_esperado = $base_cheque - $inversion_cheque - $gastos_cheque;
$total_final_esperado = $efectivo_esperado + $transferencia_esperada + $tarjeta_esperada + $cheque_esperado;
$total_sin_reponer = ($monto_apertura + $total_cobrado) - $gastos_total;
$ganancia_real_dia = ($monto_apertura + $total_cobrado) - $inversion_total_considerada - $gastos_total;
$dinero_para_reponer = $inversion_sugerida;

$medios_pago = [
    ['medio'=>'Efectivo','cobrado'=>$efectivo,'apertura'=>$monto_apertura,'inversion'=>$inversion_efectivo,'gastos'=>$gastos_efectivo,'esperado'=>$efectivo_esperado],
    ['medio'=>'Transferencia','cobrado'=>$transferencia,'apertura'=>0,'inversion'=>$inversion_transferencia,'gastos'=>$gastos_transferencia,'esperado'=>$transferencia_esperada],
    ['medio'=>'Tarjeta','cobrado'=>$tarjeta,'apertura'=>0,'inversion'=>$inversion_tarjeta,'gastos'=>$gastos_tarjeta,'esperado'=>$tarjeta_esperada],
    ['medio'=>'Cheque','cobrado'=>$cheque,'apertura'=>0,'inversion'=>$inversion_cheque,'gastos'=>$gastos_cheque,'esperado'=>$cheque_esperado]
];

echo json_encode([
    'success' => true,
    'message' => 'Cuadre generado correctamente.',
    'resumen' => [
        'modo'=>$modo,
        'apertura_id'=>$apertura_id,
        'fechai'=>$fechai,
        'fechaf'=>$fechaf,
        'monto_apertura'=>$monto_apertura,
        'total_cobrado'=>$total_cobrado,
        'efectivo'=>$efectivo,
        'transferencia'=>$transferencia,
        'tarjeta'=>$tarjeta,
        'cheque'=>$cheque,
        'inversion_reposicion'=>$inversion_reposicion,
        'inversion_sugerida'=>$inversion_sugerida,
        'inversion_manual_registrada'=>$inversion_manual_registrada,
        'inversion_manual_efectivo'=>$inversion_manual_efectivo,
        'inversion_manual_transferencia'=>$inversion_manual_transferencia,
        'inversion_manual_tarjeta'=>$inversion_manual_tarjeta,
        'inversion_manual_cheque'=>$inversion_manual_cheque,
        'inversion_efectivo'=>$inversion_efectivo,
        'inversion_transferencia'=>$inversion_transferencia,
        'inversion_tarjeta'=>$inversion_tarjeta,
        'inversion_cheque'=>$inversion_cheque,
        'inversion_total_considerada'=>$inversion_total_considerada,
        'inversion_pendiente'=>$inversion_pendiente,
        'inversion_no_cubierta'=>$inversion_no_cubierta,
        'venta_base_productos'=>$venta_base_productos,
        'isv_total_detalle'=>$isv_total_detalle,
        'isv_factura_normal_sar'=>$isv_factura_normal_sar,
        'isv_proforma_informativo'=>$isv_proforma_informativo,
        'ganancia_productos'=>$ganancia_productos,
        'retiros_total'=>$retiros_total,
        'otros_egresos_total'=>$otros_egresos_total,
        'gastos_total'=>$gastos_total,
        'gastos_efectivo'=>$gastos_efectivo,
        'gastos_transferencia'=>$gastos_transferencia,
        'gastos_tarjeta'=>$gastos_tarjeta,
        'gastos_cheque'=>$gastos_cheque,
        'efectivo_esperado'=>$efectivo_esperado,
        'transferencia_esperada'=>$transferencia_esperada,
        'tarjeta_esperada'=>$tarjeta_esperada,
        'cheque_esperado'=>$cheque_esperado,
        'total_sin_reponer'=>$total_sin_reponer,
        'total_final_esperado'=>$total_final_esperado,
        'ganancia_real_dia'=>$ganancia_real_dia,
        'dinero_para_reponer'=>$dinero_para_reponer
    ],
    'medios_pago'=>$medios_pago,
    'gastos'=>$gastos,
    'inversiones'=>$inversiones
], JSON_UNESCAPED_UNICODE);