<?php
// core/caja/reintegrarRetiroCaja.php
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

function limpiarTextoReintegroRetiroCaja($texto) {
    $texto = trim($texto);
    $texto = strip_tags($texto);
    $texto = str_replace(["\\", "'", '"', ";"], ["", "", "", ""], $texto);
    return $texto;
}

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$caja_retiros_id = isset($_POST['caja_retiros_id']) ? (int)$_POST['caja_retiros_id'] : 0;
$apertura_id = isset($_POST['apertura_id']) ? (int)$_POST['apertura_id'] : 0;
$monto_reintegro = isset($_POST['monto_reintegro']) ? (float)$_POST['monto_reintegro'] : 0;
$observacion = isset($_POST['observacion']) ? limpiarTextoReintegroRetiroCaja($_POST['observacion']) : '';

$observacion = mb_substr($observacion, 0, 255, 'UTF-8');

$fecha = date('Y-m-d');
$fecha_registro = date('Y-m-d H:i:s');

if ($empresa_id <= 0 || $colaboradores_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo identificar la empresa o el usuario de la sesión.'
    ]);
    exit;
}

if ($caja_retiros_id <= 0 || $apertura_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió un retiro válido.'
    ]);
    exit;
}

if ($monto_reintegro <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Ingrese un monto válido para reintegrar.'
    ]);
    exit;
}

$sqlApertura = "
    SELECT apertura_id, estado
    FROM apertura
    WHERE apertura_id = '$apertura_id'
      AND empresa_id = '$empresa_id'
    LIMIT 1
";

$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

if (!$resApertura || $resApertura->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'La apertura de caja no existe o no pertenece a la empresa actual.'
    ]);
    exit;
}

$rowApertura = $resApertura->fetch_assoc();

if ((int)$rowApertura['estado'] !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'No se puede reintegrar este retiro porque la caja ya está cerrada.'
    ]);
    exit;
}

$sqlRetiro = "
    SELECT
        caja_retiros_id,
        apertura_id,
        egresos_id,
        cuentas_id,
        empresa_id,
        monto,
        motivo,
        observacion,
        estado
    FROM caja_retiros
    WHERE caja_retiros_id = '$caja_retiros_id'
      AND apertura_id = '$apertura_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
    LIMIT 1
";

$resRetiro = $insMainModel->ejecutar_consulta_simple($sqlRetiro);

if (!$resRetiro || $resRetiro->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'El retiro no existe, ya fue anulado o no pertenece a la caja seleccionada.'
    ]);
    exit;
}

$rowRetiro = $resRetiro->fetch_assoc();

$monto_actual = (float)$rowRetiro['monto'];
$egresos_id = (int)$rowRetiro['egresos_id'];
$cuentas_id = (int)$rowRetiro['cuentas_id'];
$observacion_actual = $rowRetiro['observacion'];

if ($monto_reintegro > $monto_actual) {
    echo json_encode([
        'success' => false,
        'message' => 'El monto a reintegrar no puede ser mayor al monto actual del retiro.'
    ]);
    exit;
}

if ($cuentas_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'El retiro no tiene una cuenta contable válida.'
    ]);
    exit;
}

$monto_restante = $monto_actual - $monto_reintegro;

if ($monto_restante < 0) {
    $monto_restante = 0;
}

$saldo_actual = 0;

$sqlSaldo = "
    SELECT saldo
    FROM movimientos_cuentas
    WHERE cuentas_id = '$cuentas_id'
      AND empresa_id = '$empresa_id'
    ORDER BY movimientos_cuentas_id DESC
    LIMIT 1
";

$resSaldo = $insMainModel->ejecutar_consulta_simple($sqlSaldo);

if ($resSaldo && $resSaldo->num_rows > 0) {
    $rowSaldo = $resSaldo->fetch_assoc();
    $saldo_actual = (float)$rowSaldo['saldo'];
}

$nuevo_saldo = $saldo_actual + $monto_reintegro;

$movimientos_cuentas_id = $insMainModel->correlativo('movimientos_cuentas_id', 'movimientos_cuentas');

$insertMovimiento = "
    INSERT INTO movimientos_cuentas (
        movimientos_cuentas_id,
        cuentas_id,
        empresa_id,
        fecha,
        ingreso,
        egreso,
        saldo,
        colaboradores_id,
        fecha_registro
    ) VALUES (
        '$movimientos_cuentas_id',
        '$cuentas_id',
        '$empresa_id',
        '$fecha',
        '$monto_reintegro',
        '0',
        '$nuevo_saldo',
        '$colaboradores_id',
        '$fecha_registro'
    )
";

$okMovimiento = $insMainModel->ejecutar_consulta_simple($insertMovimiento);

if (!$okMovimiento) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo registrar el movimiento del reintegro.'
    ]);
    exit;
}

$textoReintegro = " | Reintegro: L. ".number_format($monto_reintegro, 2, '.', '')." - ".$fecha_registro;

if ($observacion !== '') {
    $textoReintegro .= " - ".$observacion;
}

$nuevaObservacionRetiro = mb_substr($observacion_actual.$textoReintegro, 0, 255, 'UTF-8');

if ($monto_restante <= 0) {
    $updateRetiro = "
        UPDATE caja_retiros
        SET
            monto = '0',
            estado = 0,
            observacion = '$nuevaObservacionRetiro'
        WHERE caja_retiros_id = '$caja_retiros_id'
          AND apertura_id = '$apertura_id'
          AND empresa_id = '$empresa_id'
        LIMIT 1
    ";
} else {
    $updateRetiro = "
        UPDATE caja_retiros
        SET
            monto = '$monto_restante',
            observacion = '$nuevaObservacionRetiro'
        WHERE caja_retiros_id = '$caja_retiros_id'
          AND apertura_id = '$apertura_id'
          AND empresa_id = '$empresa_id'
        LIMIT 1
    ";
}

$okRetiro = $insMainModel->ejecutar_consulta_simple($updateRetiro);

if (!$okRetiro) {
    echo json_encode([
        'success' => false,
        'message' => 'El movimiento fue registrado, pero no se pudo actualizar el retiro.'
    ]);
    exit;
}

if ($egresos_id > 0) {
    $observacionEgreso = " | Reintegro aplicado L. ".number_format($monto_reintegro, 2, '.', '');

    if ($monto_restante <= 0) {
        $updateEgreso = "
            UPDATE egresos
            SET
                subtotal = '0',
                total = '0',
                estado = 0,
                observacion = LEFT(CONCAT(observacion, '$observacionEgreso', ' | Retiro anulado por reintegro total'), 150)
            WHERE egresos_id = '$egresos_id'
              AND empresa_id = '$empresa_id'
            LIMIT 1
        ";
    } else {
        $updateEgreso = "
            UPDATE egresos
            SET
                subtotal = '$monto_restante',
                total = '$monto_restante',
                observacion = LEFT(CONCAT(observacion, '$observacionEgreso'), 150)
            WHERE egresos_id = '$egresos_id'
              AND empresa_id = '$empresa_id'
            LIMIT 1
        ";
    }

    $okEgreso = $insMainModel->ejecutar_consulta_simple($updateEgreso);

    if (!$okEgreso) {
        echo json_encode([
            'success' => false,
            'message' => 'El reintegro fue aplicado en caja, pero no se pudo actualizar el egreso.'
        ]);
        exit;
    }
}

$mensaje = '';

if ($monto_restante <= 0) {
    $mensaje = 'Reintegro total registrado correctamente. El retiro fue anulado.';
} else {
    $mensaje = 'Reintegro parcial registrado correctamente. El retiro quedó ajustado.';
}

echo json_encode([
    'success' => true,
    'message' => $mensaje,
    'caja_retiros_id' => $caja_retiros_id,
    'apertura_id' => $apertura_id,
    'monto_anterior' => number_format($monto_actual, 2, '.', ''),
    'monto_reintegrado' => number_format($monto_reintegro, 2, '.', ''),
    'monto_restante_retiro' => number_format($monto_restante, 2, '.', ''),
    'saldo_anterior_cuenta' => number_format($saldo_actual, 2, '.', ''),
    'saldo_final_cuenta' => number_format($nuevo_saldo, 2, '.', ''),
    'movimientos_cuentas_id' => $movimientos_cuentas_id
]);