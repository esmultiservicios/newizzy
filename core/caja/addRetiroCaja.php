<?php
// core/caja/addRetiroCaja.php
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

$proveedores_id = 1;
$tipo_egreso = 2;
$estado = 1;

$egresos_id = $insMainModel->correlativo('egresos_id', 'egresos');
$factura_ref = 'RC-' . $apertura_id . '-' . $egresos_id;

$observacion_egreso = 'Retiro de caja - ' . $motivo;

if ($observacion !== '') {
    $observacion_egreso .= ' - ' . $observacion;
}

$observacion_egreso = mb_substr($observacion_egreso, 0, 150, 'UTF-8');

$insertEgreso = "
    INSERT INTO egresos (
        egresos_id,
        cuentas_id,
        proveedores_id,
        empresa_id,
        tipo_egreso,
        fecha,
        factura,
        factura_pdf,
        subtotal,
        descuento,
        nc,
        impuesto,
        total,
        observacion,
        estado,
        colaboradores_id,
        fecha_registro,
        categoria_gastos_id
    ) VALUES (
        '$egresos_id',
        '$cuentas_id',
        '$proveedores_id',
        '$empresa_id',
        '$tipo_egreso',
        '$fecha',
        '$factura_ref',
        NULL,
        '$monto',
        '0',
        '0',
        '0',
        '$monto',
        '$observacion_egreso',
        '$estado',
        '$colaboradores_id',
        '$fecha_registro',
        '$categoria_gastos_id'
    )
";

$okEgreso = $insMainModel->ejecutar_consulta_simple($insertEgreso);

if (!$okEgreso) {
    echo json_encode([
        'success' => false,
        'title' => 'Error al registrar',
        'message' => 'No se pudo registrar el egreso del retiro.'
    ]);
    exit;
}

$saldo_actual = 0;

$sqlSaldo = "
    SELECT saldo
    FROM movimientos_cuentas
    WHERE cuentas_id = '$cuentas_id'
    ORDER BY movimientos_cuentas_id DESC
    LIMIT 1
";

$resSaldo = $insMainModel->ejecutar_consulta_simple($sqlSaldo);

if ($resSaldo && $resSaldo->num_rows > 0) {
    $rowSaldo = $resSaldo->fetch_assoc();
    $saldo_actual = (float)$rowSaldo['saldo'];
}

$nuevo_saldo = $saldo_actual - $monto;

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
        '0',
        '$monto',
        '$nuevo_saldo',
        '$colaboradores_id',
        '$fecha_registro'
    )
";

$okMovimiento = $insMainModel->ejecutar_consulta_simple($insertMovimiento);

if (!$okMovimiento) {
    echo json_encode([
        'success' => false,
        'title' => 'Movimiento no registrado',
        'message' => 'El egreso fue creado, pero no se pudo registrar el movimiento de cuenta.'
    ]);
    exit;
}

$insertRetiro = "
    INSERT INTO caja_retiros (
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
        '$apertura_id',
        '$egresos_id',
        '$cuentas_id',
        '$empresa_id',
        '$monto',
        '$motivo',
        '$observacion',
        '1',
        '$colaboradores_id',
        '$fecha',
        '$fecha_registro'
    )
";

$okRetiro = $insMainModel->ejecutar_consulta_simple($insertRetiro);

if (!$okRetiro) {
    echo json_encode([
        'success' => false,
        'title' => 'Historial no registrado',
        'message' => 'El egreso y el movimiento fueron creados, pero no se pudo guardar el historial del retiro en caja_retiros.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'title' => 'Retiro registrado',
    'message' => 'Retiro de caja registrado correctamente.',
    'saldo_anterior' => number_format($saldo_disponible, 2, '.', ''),
    'monto_retirado' => number_format($monto, 2, '.', ''),
    'saldo_final' => number_format(($saldo_disponible - $monto), 2, '.', ''),
    'categoria_gastos_id' => $categoria_gastos_id,
    'categoria' => $motivo,
    'egresos_id' => $egresos_id
]);