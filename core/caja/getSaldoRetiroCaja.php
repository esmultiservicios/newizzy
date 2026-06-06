<?php
// core/caja/getSaldoRetiroCaja.php
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
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
        exit;
    }
}

function obtenerCuentaTipoPagoRetiroCaja($insMainModel, $tipo_pago_id) {
    $tipo_pago_id = (int)$tipo_pago_id;

    $sql = "
        SELECT cuentas_id
        FROM tipo_pago
        WHERE tipo_pago_id = '$tipo_pago_id'
        LIMIT 1
    ";

    $res = $insMainModel->ejecutar_consulta_simple($sql);

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        return isset($row['cuentas_id']) ? (int)$row['cuentas_id'] : 0;
    }

    return 0;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

if ($empresa_id <= 0 || $colaboradores_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar la empresa o el usuario de la sesión.'
    ]);
    exit;
}

$sqlApertura = "
    SELECT apertura_id, apertura
    FROM apertura
    WHERE colaboradores_id = '$colaboradores_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
    ORDER BY apertura_id DESC
    LIMIT 1
";

$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

if (!$resApertura || $resApertura->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No hay una caja abierta para realizar retiros.'
    ]);
    exit;
}

$rowApertura = $resApertura->fetch_assoc();

$apertura_id = (int)$rowApertura['apertura_id'];
$monto_apertura = (float)$rowApertura['apertura'];

$cuenta_efectivo = obtenerCuentaTipoPagoRetiroCaja($insMainModel, 1);
$cuenta_transferencia = obtenerCuentaTipoPagoRetiroCaja($insMainModel, 3);

if ($cuenta_efectivo <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró la cuenta contable del efectivo en tipo_pago.'
    ]);
    exit;
}

if ($cuenta_transferencia <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se encontró la cuenta contable de transferencia en tipo_pago.'
    ]);
    exit;
}

$sqlPagos = "
    SELECT
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 1 THEN pd.efectivo ELSE 0 END), 0) AS efectivo,
        COALESCE(SUM(CASE WHEN pd.tipo_pago_id = 3 THEN pd.efectivo ELSE 0 END), 0) AS transferencia
    FROM pagos pg
    INNER JOIN facturas f
        ON f.facturas_id = pg.facturas_id
    INNER JOIN pagos_detalles pd
        ON pd.pagos_id = pg.pagos_id
    WHERE f.apertura_id = '$apertura_id'
      AND f.estado = 2
      AND pg.estado = 1
";

$resPagos = $insMainModel->ejecutar_consulta_simple($sqlPagos);

$efectivo = 0;
$transferencia = 0;

if ($resPagos && $resPagos->num_rows > 0) {
    $rowPagos = $resPagos->fetch_assoc();
    $efectivo = (float)$rowPagos['efectivo'];
    $transferencia = (float)$rowPagos['transferencia'];
}

$sqlRetiros = "
    SELECT
        COALESCE(SUM(CASE WHEN cuentas_id = '$cuenta_efectivo' THEN monto ELSE 0 END), 0) AS retiros_efectivo,
        COALESCE(SUM(CASE WHEN cuentas_id = '$cuenta_transferencia' THEN monto ELSE 0 END), 0) AS retiros_transferencia,
        COALESCE(SUM(monto), 0) AS retiros_total
    FROM caja_retiros
    WHERE apertura_id = '$apertura_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
";

$resRetiros = $insMainModel->ejecutar_consulta_simple($sqlRetiros);

$retiros_efectivo = 0;
$retiros_transferencia = 0;
$retiros_total = 0;

if ($resRetiros && $resRetiros->num_rows > 0) {
    $rowRetiros = $resRetiros->fetch_assoc();
    $retiros_efectivo = (float)$rowRetiros['retiros_efectivo'];
    $retiros_transferencia = (float)$rowRetiros['retiros_transferencia'];
    $retiros_total = (float)$rowRetiros['retiros_total'];
}

$saldo_efectivo = ($monto_apertura + $efectivo) - $retiros_efectivo;
$saldo_transferencia = $transferencia - $retiros_transferencia;

if ($saldo_efectivo < 0) {
    $saldo_efectivo = 0;
}

if ($saldo_transferencia < 0) {
    $saldo_transferencia = 0;
}

$saldo_disponible = $saldo_efectivo + $saldo_transferencia;

$saldo_final_efectivo = $saldo_efectivo;
$saldo_final_transferencia = $saldo_transferencia;

if ($saldo_disponible < 0) {
    $saldo_disponible = 0;
}

echo json_encode([
    'success' => true,
    'apertura_id' => $apertura_id,
    'cuenta_efectivo' => $cuenta_efectivo,
    'cuenta_transferencia' => $cuenta_transferencia,
    'monto_apertura' => number_format($monto_apertura, 2, '.', ''),
    'efectivo' => number_format($efectivo, 2, '.', ''),
    'transferencia' => number_format($transferencia, 2, '.', ''),
    'retiros' => number_format($retiros_total, 2, '.', ''),
    'retiros_efectivo' => number_format($retiros_efectivo, 2, '.', ''),
    'retiros_transferencia' => number_format($retiros_transferencia, 2, '.', ''),
    'saldo_efectivo' => number_format($saldo_efectivo, 2, '.', ''),
    'saldo_transferencia' => number_format($saldo_transferencia, 2, '.', ''),
    'saldo_disponible' => number_format($saldo_disponible, 2, '.', ''),
    'saldo_final_efectivo' => number_format($saldo_final_efectivo, 2, '.', ''),
    'saldo_final_transferencia' => number_format($saldo_final_transferencia, 2, '.', ''),
    'saldo_final_total' => number_format($saldo_disponible, 2, '.', '')
]);
