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

$sqlEfectivo = "
    SELECT COALESCE(SUM(pd.efectivo), 0) AS efectivo
    FROM pagos pg
    INNER JOIN facturas f 
        ON f.facturas_id = pg.facturas_id
    INNER JOIN pagos_detalles pd 
        ON pd.pagos_id = pg.pagos_id
    WHERE f.apertura_id = '$apertura_id'
      AND f.estado = 2
      AND pg.estado = 1
      AND pd.tipo_pago_id = 1
";

$resEfectivo = $insMainModel->ejecutar_consulta_simple($sqlEfectivo);

$efectivo = 0;

if ($resEfectivo && $resEfectivo->num_rows > 0) {
    $rowEfectivo = $resEfectivo->fetch_assoc();
    $efectivo = (float)$rowEfectivo['efectivo'];
}

$sqlRetiros = "
    SELECT COALESCE(SUM(monto), 0) AS retiros
    FROM caja_retiros
    WHERE apertura_id = '$apertura_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
";

$resRetiros = $insMainModel->ejecutar_consulta_simple($sqlRetiros);

$retiros = 0;

if ($resRetiros && $resRetiros->num_rows > 0) {
    $rowRetiros = $resRetiros->fetch_assoc();
    $retiros = (float)$rowRetiros['retiros'];
}

$saldo_disponible = ($monto_apertura + $efectivo) - $retiros;

if ($saldo_disponible < 0) {
    $saldo_disponible = 0;
}

echo json_encode([
    'success' => true,
    'apertura_id' => $apertura_id,
    'monto_apertura' => number_format($monto_apertura, 2, '.', ''),
    'efectivo' => number_format($efectivo, 2, '.', ''),
    'retiros' => number_format($retiros, 2, '.', ''),
    'saldo_disponible' => number_format($saldo_disponible, 2, '.', '')
]);