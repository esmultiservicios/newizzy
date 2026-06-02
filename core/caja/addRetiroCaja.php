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

$empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;
$colaboradores_id = isset($_SESSION['colaborador_id_sd']) ? (int)$_SESSION['colaborador_id_sd'] : 0;

$apertura_id = isset($_POST['retiro_apertura_id']) ? (int)$_POST['retiro_apertura_id'] : 0;
$monto = isset($_POST['retiro_monto']) ? (float)$_POST['retiro_monto'] : 0;

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

if ($monto <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto inválido',
        'message' => 'Ingrese un monto válido para retirar.'
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

$motivo = limpiarTextoRetiroCaja($rowCategoria['nombre']);
$motivo = mb_substr($motivo, 0, 100, 'UTF-8');

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

$sqlEfectivo = "
    SELECT COALESCE(SUM(pd.efectivo), 0) AS efectivo
    FROM pagos pg
    INNER JOIN facturas f ON f.facturas_id = pg.facturas_id
    INNER JOIN pagos_detalles pd ON pd.pagos_id = pg.pagos_id
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

if ($saldo_disponible <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Sin saldo disponible',
        'message' => 'No hay dinero disponible en caja para retirar.'
    ]);
    exit;
}

if ($monto > $saldo_disponible) {
    echo json_encode([
        'success' => false,
        'title' => 'Monto mayor al disponible',
        'message' => 'El monto a retirar es mayor al dinero disponible en caja.'
    ]);
    exit;
}

$sqlCuenta = "
    SELECT cuentas_id
    FROM tipo_pago
    WHERE tipo_pago_id = 1
    LIMIT 1
";

$resCuenta = $insMainModel->ejecutar_consulta_simple($sqlCuenta);

if (!$resCuenta || $resCuenta->num_rows <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Cuenta no encontrada',
        'message' => 'No se encontró la cuenta contable del efectivo.'
    ]);
    exit;
}

$rowCuenta = $resCuenta->fetch_assoc();
$cuentas_id = (int)$rowCuenta['cuentas_id'];

if ($cuentas_id <= 0) {
    echo json_encode([
        'success' => false,
        'title' => 'Cuenta inválida',
        'message' => 'La cuenta contable del efectivo no es válida.'
    ]);
    exit;
}

/*
    REGLA CORRECTA:
    El retiro de caja NO registra egresos.
    El retiro de caja NO registra movimientos_cuentas.

    Aquí SOLO se registra caja_retiros.

    Luego, cuando se cierre la caja:
    - Se registrará el ingreso por el total vendido.
    - Se registrará el egreso por los retiros activos.
    - Se registrarán ambos movimientos contables.
*/

$egresos_id = 0;
$estado = 1;

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
    echo json_encode([
        'success' => false,
        'title' => 'Error al registrar',
        'message' => 'No se pudo registrar el retiro de caja.'
    ]);
    exit;
}

$saldo_final_caja = $saldo_disponible - $monto;

if ($saldo_final_caja < 0) {
    $saldo_final_caja = 0;
}

echo json_encode([
    'success' => true,
    'title' => 'Retiro registrado',
    'message' => 'Retiro de caja registrado correctamente.',
    'caja_retiros_id' => $caja_retiros_id,
    'apertura_id' => $apertura_id,
    'saldo_anterior_caja' => number_format($saldo_disponible, 2, '.', ''),
    'monto_retirado' => number_format($monto, 2, '.', ''),
    'saldo_final_caja' => number_format($saldo_final_caja, 2, '.', ''),
    'categoria_gastos_id' => $categoria_gastos_id,
    'categoria' => $motivo,
    'egresos_id' => $egresos_id
]);