<?php
// core/caja/addRetiroCaja.php
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
            'title' => 'Sesión inválida',
            'message' => $validacion['mensaje'] ?? 'Sesión inválida'
        ]);
        exit;
    }
}

function limpiarTextoRetiroCaja($texto) {
    $texto = trim($texto);
    $texto = strip_tags($texto);
    $texto = str_replace(["\\", "'", '"', ";"], ["", "", "", ""], $texto);
    return $texto;
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

$apertura_id = isset($_POST['retiro_apertura_id']) ? (int)$_POST['retiro_apertura_id'] : 0;

$monto_efectivo = isset($_POST['retiro_monto_efectivo']) ? (float)$_POST['retiro_monto_efectivo'] : 0;
$monto_transferencia = isset($_POST['retiro_monto_transferencia']) ? (float)$_POST['retiro_monto_transferencia'] : 0;
$monto_legacy = isset($_POST['retiro_monto']) ? (float)$_POST['retiro_monto'] : 0;

if ($monto_efectivo <= 0 && $monto_transferencia <= 0 && $monto_legacy > 0) {
    $monto_efectivo = $monto_legacy;
}

$categoria_gastos_id = isset($_POST['retiro_categoria_gastos_id']) ? (int)$_POST['retiro_categoria_gastos_id'] : 0;
$observacion = isset($_POST['retiro_observacion']) ? limpiarTextoRetiroCaja($_POST['retiro_observacion']) : '';
$observacion = mb_substr($observacion, 0, 255, 'UTF-8');

$fecha = date('Y-m-d');
$fecha_registro = date('Y-m-d H:i:s');

if ($empresa_id <= 0 || $colaboradores_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Error',
        'message' => 'No se pudo identificar la empresa o el usuario de la sesión.'
    ]);
    exit;
}

if ($apertura_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Caja inválida',
        'message' => 'No se recibió una caja válida.'
    ]);
    exit;
}

if ($monto_efectivo < 0 || $monto_transferencia < 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto inválido',
        'message' => 'Los montos no pueden ser negativos.'
    ]);
    exit;
}

$monto_total = $monto_efectivo + $monto_transferencia;

if ($monto_total <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto inválido',
        'message' => 'Ingrese un monto válido para retirar en efectivo o transferencia.'
    ]);
    exit;
}

if ($categoria_gastos_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Categoría requerida',
        'message' => 'Seleccione la categoría del retiro.'
    ]);
    exit;
}

$sqlCategoria = "
    SELECT categoria_gastos_id, nombre
    FROM categoria_gastos
    WHERE categoria_gastos_id = '$categoria_gastos_id'
      AND estado = 1
    LIMIT 1
";

$resCategoria = $insMainModel->ejecutar_consulta_simple($sqlCategoria);

if (!$resCategoria || $resCategoria->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Categoría inválida',
        'message' => 'La categoría seleccionada no existe o está inactiva.'
    ]);
    exit;
}

$rowCategoria = $resCategoria->fetch_assoc();

$motivo_base = limpiarTextoRetiroCaja($rowCategoria['nombre']);
$motivo_base = mb_substr($motivo_base, 0, 80, 'UTF-8');

$sqlApertura = "
    SELECT apertura_id, apertura
    FROM apertura
    WHERE apertura_id = '$apertura_id'
      AND colaboradores_id = '$colaboradores_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
    LIMIT 1
";

$resApertura = $insMainModel->ejecutar_consulta_simple($sqlApertura);

if (!$resApertura || $resApertura->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Caja no disponible',
        'message' => 'La caja no está abierta o no pertenece al usuario actual.'
    ]);
    exit;
}

$rowApertura = $resApertura->fetch_assoc();
$monto_apertura = (float)$rowApertura['apertura'];

$cuenta_efectivo = obtenerCuentaTipoPagoRetiroCaja($insMainModel, 1);
$cuenta_transferencia = obtenerCuentaTipoPagoRetiroCaja($insMainModel, 3);

if ($cuenta_efectivo <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Cuenta no encontrada',
        'message' => 'No se encontró la cuenta contable del efectivo.'
    ]);
    exit;
}

if ($cuenta_transferencia <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Cuenta no encontrada',
        'message' => 'No se encontró la cuenta contable de transferencia.'
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
        COALESCE(SUM(CASE WHEN cuentas_id = '$cuenta_transferencia' THEN monto ELSE 0 END), 0) AS retiros_transferencia
    FROM caja_retiros
    WHERE apertura_id = '$apertura_id'
      AND empresa_id = '$empresa_id'
      AND estado = 1
";

$resRetiros = $insMainModel->ejecutar_consulta_simple($sqlRetiros);

$retiros_efectivo = 0;
$retiros_transferencia = 0;

if ($resRetiros && $resRetiros->num_rows > 0) {
    $rowRetiros = $resRetiros->fetch_assoc();
    $retiros_efectivo = (float)$rowRetiros['retiros_efectivo'];
    $retiros_transferencia = (float)$rowRetiros['retiros_transferencia'];
}

$saldo_efectivo = ($monto_apertura + $efectivo) - $retiros_efectivo;
$saldo_transferencia = $transferencia - $retiros_transferencia;

if ($saldo_efectivo < 0) {
    $saldo_efectivo = 0;
}

if ($saldo_transferencia < 0) {
    $saldo_transferencia = 0;
}

if ($monto_efectivo > $saldo_efectivo) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto mayor al disponible',
        'message' => 'El monto a retirar en efectivo es mayor al efectivo disponible en caja.'
    ]);
    exit;
}

if ($monto_transferencia > $saldo_transferencia) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto mayor al disponible',
        'message' => 'El monto a retirar de transferencia es mayor al saldo disponible por transferencia.'
    ]);
    exit;
}

$insertados = [];
$egresos_id = 0;
$estado = 1;

function insertarRetiroPorMedio($insMainModel, $apertura_id, $egresos_id, $cuentas_id, $empresa_id, $monto, $motivo, $observacion, $estado, $colaboradores_id, $fecha, $fecha_registro) {
    $monto = (float)$monto;

    if ($monto <= 0) {
        return [true, 0];
    }

    $caja_retiros_id = $insMainModel->correlativo('caja_retiros_id', 'caja_retiros');

    $insertRetiro = "
        INSERT INTO caja_retiros (
            caja_retiros_id,
            apertura_id,
            egresos_id,
            cuentas_id,
            empresa_id,
            monto,
            motivo,
            observacion,
            estado,
            colaboradores_id,
            fecha,
            fecha_registro
        ) VALUES (
            '$caja_retiros_id',
            '$apertura_id',
            '$egresos_id',
            '$cuentas_id',
            '$empresa_id',
            '$monto',
            '$motivo',
            '$observacion',
            '$estado',
            '$colaboradores_id',
            '$fecha',
            '$fecha_registro'
        )
    ";

    $okRetiro = $insMainModel->ejecutar_consulta_simple($insertRetiro);

    if (!$okRetiro) {
        return [false, 0];
    }

    return [true, $caja_retiros_id];
}

if ($monto_efectivo > 0) {
    $motivo = mb_substr($motivo_base.' - Efectivo', 0, 100, 'UTF-8');
    list($ok, $id) = insertarRetiroPorMedio($insMainModel, $apertura_id, $egresos_id, $cuenta_efectivo, $empresa_id, $monto_efectivo, $motivo, $observacion, $estado, $colaboradores_id, $fecha, $fecha_registro);

    if (!$ok) {
        echo json_encode([
            'success' => false,
            'title' => 'Error al registrar',
            'message' => 'No se pudo registrar el retiro de efectivo.'
        ]);
        exit;
    }

    $insertados[] = $id;
}

if ($monto_transferencia > 0) {
    $motivo = mb_substr($motivo_base.' - Transferencia', 0, 100, 'UTF-8');
    list($ok, $id) = insertarRetiroPorMedio($insMainModel, $apertura_id, $egresos_id, $cuenta_transferencia, $empresa_id, $monto_transferencia, $motivo, $observacion, $estado, $colaboradores_id, $fecha, $fecha_registro);

    if (!$ok) {
        echo json_encode([
            'success' => false,
            'title' => 'Error al registrar',
            'message' => 'No se pudo registrar el retiro de transferencia.'
        ]);
        exit;
    }

    $insertados[] = $id;
}

$saldo_final_efectivo = $saldo_efectivo - $monto_efectivo;
$saldo_final_transferencia = $saldo_transferencia - $monto_transferencia;

if ($saldo_final_efectivo < 0) {
    $saldo_final_efectivo = 0;
}

if ($saldo_final_transferencia < 0) {
    $saldo_final_transferencia = 0;
}

$saldo_anterior_total = $saldo_efectivo + $saldo_transferencia;
$saldo_final_total = $saldo_final_efectivo + $saldo_final_transferencia;

$mensaje = 'Retiro de caja registrado correctamente.';

if ($monto_efectivo > 0 && $monto_transferencia > 0) {
    $mensaje = 'Retiro de efectivo y transferencia registrado correctamente.';
} elseif ($monto_efectivo > 0) {
    $mensaje = 'Retiro de efectivo registrado correctamente.';
} elseif ($monto_transferencia > 0) {
    $mensaje = 'Retiro de transferencia registrado correctamente.';
}

echo json_encode([
    'success' => true,
    'title' => 'Retiro registrado',
    'message' => $mensaje,
    'caja_retiros_id' => isset($insertados[0]) ? $insertados[0] : 0,
    'caja_retiros_ids' => $insertados,
    'apertura_id' => $apertura_id,
    'saldo_anterior_caja' => number_format($saldo_anterior_total, 2, '.', ''),
    'saldo_anterior_efectivo' => number_format($saldo_efectivo, 2, '.', ''),
    'saldo_anterior_transferencia' => number_format($saldo_transferencia, 2, '.', ''),
    'monto_retirado' => number_format($monto_total, 2, '.', ''),
    'monto_efectivo' => number_format($monto_efectivo, 2, '.', ''),
    'monto_transferencia' => number_format($monto_transferencia, 2, '.', ''),
    'saldo_final_caja' => number_format($saldo_final_total, 2, '.', ''),
    'saldo_final_efectivo' => number_format($saldo_final_efectivo, 2, '.', ''),
    'saldo_final_transferencia' => number_format($saldo_final_transferencia, 2, '.', ''),
    'categoria_gastos_id' => $categoria_gastos_id,
    'categoria' => $motivo_base,
    'egresos_id' => $egresos_id
]);
