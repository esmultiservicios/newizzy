<?php
// llenarDataTableCuentas.php
$peticionAjax = true;

require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
$fechai = isset($_POST['fechai']) && $_POST['fechai'] !== '' ? $_POST['fechai'] : date('Y-m-01');
$fechaf = isset($_POST['fechaf']) && $_POST['fechaf'] !== '' ? $_POST['fechaf'] : date('Y-m-d');

$buscar = isset($_POST['buscar']) ? trim($_POST['buscar']) : '';
$tipo_cuenta = isset($_POST['tipo_cuenta']) ? trim($_POST['tipo_cuenta']) : '';
$tipo_saldo = isset($_POST['tipo_saldo']) ? trim($_POST['tipo_saldo']) : '';
$orden_cuentas = isset($_POST['orden_cuentas']) ? trim($_POST['orden_cuentas']) : 'neto_desc';

/*
|--------------------------------------------------------------------------
| VALIDACIÓN DE FECHAS
|--------------------------------------------------------------------------
*/
$fechaInicioObj = DateTime::createFromFormat('Y-m-d', $fechai);
$fechaFinObj = DateTime::createFromFormat('Y-m-d', $fechaf);

if (
    !$fechaInicioObj ||
    !$fechaFinObj ||
    $fechaInicioObj->format('Y-m-d') !== $fechai ||
    $fechaFinObj->format('Y-m-d') !== $fechaf
) {
    $fechai = date('Y-m-01');
    $fechaf = date('Y-m-d');
}

if ($fechai > $fechaf) {
    $temp = $fechai;
    $fechai = $fechaf;
    $fechaf = $temp;
}

/*
|--------------------------------------------------------------------------
| CONEXIÓN
|--------------------------------------------------------------------------
*/
$conexion = $insMainModel->connection();

/*
|--------------------------------------------------------------------------
| OBTENER CUENTAS
|--------------------------------------------------------------------------
*/
if ($estado === '0' || $estado === '1') {
    $result = $insMainModel->getCuentasContabilidad($estado);
} else {
    $query_cuentas = "
        SELECT
            cuentas_id,
            codigo,
            nombre,
            estado,
            fecha_registro,
            es_inversion
        FROM cuentas
        ORDER BY nombre ASC
    ";

    $result = $conexion->query($query_cuentas) or die($conexion->error);
}

$data = array();

/*
|--------------------------------------------------------------------------
| CONSULTAS PREPARADAS
|--------------------------------------------------------------------------
| REGLA CONTABLE BLINDADA:
|
| 1. El campo movimientos_cuentas.saldo es acumulado.
| 2. El SALDO ACTUAL real de una cuenta es el saldo del movimiento con el
|    mayor movimientos_cuentas_id, sin importar que su fecha contable haya
|    sido registrada hacia atrás.
| 3. Los ingresos/egresos del período sí se agrupan por fecha contable.
| 4. Para reconstruir correctamente cualquier mes/año histórico:
|
|    saldo_fin_periodo =
|        saldo_actual
|        - ingresos posteriores a fechaf
|        + egresos posteriores a fechaf
|
|    saldo_anterior =
|        saldo_fin_periodo
|        - ingresos del período
|        + egresos del período
|
| Esto evita mezclar "último registro procesado" con "última fecha contable".
|--------------------------------------------------------------------------
*/

$stmtPeriodo = $conexion->prepare("
    SELECT
        COALESCE(SUM(ingreso), 0) AS ingresos,
        COALESCE(SUM(egreso), 0) AS egresos
    FROM movimientos_cuentas
    WHERE cuentas_id = ?
      AND fecha >= ?
      AND fecha <= ?
");

if (!$stmtPeriodo) {
    die($conexion->error);
}

$stmtSaldoActual = $conexion->prepare("
    SELECT
        saldo,
        movimientos_cuentas_id,
        fecha
    FROM movimientos_cuentas
    WHERE cuentas_id = ?
    ORDER BY movimientos_cuentas_id DESC
    LIMIT 1
");

if (!$stmtSaldoActual) {
    die($conexion->error);
}

$stmtPosteriorPeriodo = $conexion->prepare("
    SELECT
        COALESCE(SUM(ingreso), 0) AS ingresos_posteriores,
        COALESCE(SUM(egreso), 0) AS egresos_posteriores
    FROM movimientos_cuentas
    WHERE cuentas_id = ?
      AND fecha > ?
");

if (!$stmtPosteriorPeriodo) {
    die($conexion->error);
}

while ($row = $result->fetch_assoc()) {
    $cuentas_id = (int)$row['cuentas_id'];

    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTO DEL PERÍODO
    |--------------------------------------------------------------------------
    */
    $stmtPeriodo->bind_param('iss', $cuentas_id, $fechai, $fechaf);
    $stmtPeriodo->execute();

    $resultPeriodo = $stmtPeriodo->get_result();
    $rowPeriodo = $resultPeriodo ? $resultPeriodo->fetch_assoc() : null;

    $ingreso = isset($rowPeriodo['ingresos']) ? (float)$rowPeriodo['ingresos'] : 0.0;
    $egreso = isset($rowPeriodo['egresos']) ? (float)$rowPeriodo['egresos'] : 0.0;

    /*
    |--------------------------------------------------------------------------
    | SALDO ACTUAL REAL
    |--------------------------------------------------------------------------
    | Siempre se toma del último movimiento PROCESADO (mayor ID).
    | Esta es la cifra que debe coincidir con la última fila real de
    | movimientos_cuentas.
    |--------------------------------------------------------------------------
    */
    $stmtSaldoActual->bind_param('i', $cuentas_id);
    $stmtSaldoActual->execute();

    $resultSaldoActual = $stmtSaldoActual->get_result();
    $rowSaldoActual = $resultSaldoActual ? $resultSaldoActual->fetch_assoc() : null;

    $saldo_actual = isset($rowSaldoActual['saldo']) ? (float)$rowSaldoActual['saldo'] : 0.0;
    $ultimo_movimiento_id = isset($rowSaldoActual['movimientos_cuentas_id']) ? (int)$rowSaldoActual['movimientos_cuentas_id'] : 0;
    $ultima_fecha_movimiento = isset($rowSaldoActual['fecha']) ? $rowSaldoActual['fecha'] : '';

    /*
    |--------------------------------------------------------------------------
    | RECONSTRUIR SALDO AL CIERRE DEL PERÍODO
    |--------------------------------------------------------------------------
    */
    $stmtPosteriorPeriodo->bind_param('is', $cuentas_id, $fechaf);
    $stmtPosteriorPeriodo->execute();

    $resultPosterior = $stmtPosteriorPeriodo->get_result();
    $rowPosterior = $resultPosterior ? $resultPosterior->fetch_assoc() : null;

    $ingresos_posteriores = isset($rowPosterior['ingresos_posteriores'])
        ? (float)$rowPosterior['ingresos_posteriores']
        : 0.0;

    $egresos_posteriores = isset($rowPosterior['egresos_posteriores'])
        ? (float)$rowPosterior['egresos_posteriores']
        : 0.0;

    /*
    |--------------------------------------------------------------------------
    | CÁLCULOS
    |--------------------------------------------------------------------------
    */
    $movimiento_periodo = $ingreso - $egreso;

    // Saldo histórico al final de fechaf, reconstruido desde el saldo actual.
    $saldo_fin_periodo = $saldo_actual - $ingresos_posteriores + $egresos_posteriores;

    // Saldo existente inmediatamente antes de fechai.
    $saldo_anterior = $saldo_fin_periodo - $ingreso + $egreso;

    // "Saldo Cierre" conserva el significado visual que ya tenía la tarjeta:
    // movimiento neto del período.
    $saldo_cierre = $movimiento_periodo;

    // "Saldo Total" SIEMPRE representa el saldo real actual de la cuenta.
    $neto = $saldo_actual;

    $saldo_anterior = round((float)$saldo_anterior, 2);
    $ingreso = round((float)$ingreso, 2);
    $egreso = round((float)$egreso, 2);
    $saldo_cierre = round((float)$saldo_cierre, 2);
    $saldo_fin_periodo = round((float)$saldo_fin_periodo, 2);
    $saldo_actual = round((float)$saldo_actual, 2);
    $neto = round((float)$neto, 2);

    $codigo = isset($row['codigo']) ? $row['codigo'] : '';
    $nombre = isset($row['nombre']) ? $row['nombre'] : '';
    $estado_cuenta = isset($row['estado']) ? (int)$row['estado'] : 0;
    $es_inversion = isset($row['es_inversion']) ? (int)$row['es_inversion'] : 0;
    $fecha_registro = isset($row['fecha_registro']) ? $row['fecha_registro'] : '';

    /*
    |--------------------------------------------------------------------------
    | FILTRO POR TIPO DE CUENTA
    |--------------------------------------------------------------------------
    */
    if ($tipo_cuenta === 'inversion' && $es_inversion !== 1) {
        continue;
    }

    if ($tipo_cuenta === 'normal' && $es_inversion === 1) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO POR TIPO DE SALDO
    |--------------------------------------------------------------------------
    | Se filtra por el SALDO ACTUAL REAL.
    |--------------------------------------------------------------------------
    */
    if ($tipo_saldo === 'positivo' && $neto <= 0) {
        continue;
    }

    if ($tipo_saldo === 'negativo' && $neto >= 0) {
        continue;
    }

    if ($tipo_saldo === 'cero' && round($neto, 2) != 0.00) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | FILTRO DE BÚSQUEDA GENERAL
    |--------------------------------------------------------------------------
    */
    if ($buscar !== '') {
        $buscar_lower = function_exists('mb_strtolower')
            ? mb_strtolower($buscar, 'UTF-8')
            : strtolower($buscar);

        $texto_original =
            $codigo . ' ' .
            $nombre . ' ' .
            ($estado_cuenta === 1 ? 'activo activa' : 'inactivo inactiva') . ' ' .
            ($es_inversion === 1 ? 'inversion inversión reposicion reposición' : 'normal') . ' ' .
            number_format($saldo_anterior, 2, '.', '') . ' ' .
            number_format($ingreso, 2, '.', '') . ' ' .
            number_format($egreso, 2, '.', '') . ' ' .
            number_format($saldo_cierre, 2, '.', '') . ' ' .
            number_format($saldo_fin_periodo, 2, '.', '') . ' ' .
            number_format($saldo_actual, 2, '.', '');

        $texto_busqueda = function_exists('mb_strtolower')
            ? mb_strtolower($texto_original, 'UTF-8')
            : strtolower($texto_original);

        if (strpos($texto_busqueda, $buscar_lower) === false) {
            continue;
        }
    }

    $data[] = array(
        'cuentas_id' => $cuentas_id,
        'codigo' => $codigo,
        'nombre' => $nombre,
        'estado' => $estado_cuenta,
        'es_inversion' => $es_inversion,
        'fecha_registro' => $fecha_registro,

        // Valores numéricos
        'saldo_anterior_valor' => $saldo_anterior,
        'ingreso_valor' => $ingreso,
        'egreso_valor' => $egreso,
        'saldo_cierre_valor' => $saldo_cierre,
        'saldo_fin_periodo_valor' => $saldo_fin_periodo,
        'saldo_actual_valor' => $saldo_actual,
        'neto_valor' => $neto,

        // Datos de auditoría / trazabilidad del saldo actual
        'ultimo_movimiento_id' => $ultimo_movimiento_id,
        'ultima_fecha_movimiento' => $ultima_fecha_movimiento,

        // Formato de presentación existente
        'saldo_anterior' => 'L. ' . number_format($saldo_anterior, 2),
        'ingreso' => 'L. ' . number_format($ingreso, 2),
        'egreso' => 'L. ' . number_format($egreso, 2),
        'saldo_cierre' => 'L. ' . number_format($saldo_cierre, 2),
        'saldo_fin_periodo' => 'L. ' . number_format($saldo_fin_periodo, 2),
        'saldo_actual' => 'L. ' . number_format($saldo_actual, 2),
        'neto' => 'L. ' . number_format($neto, 2)
    );
}

$stmtPeriodo->close();
$stmtSaldoActual->close();
$stmtPosteriorPeriodo->close();

/*
|--------------------------------------------------------------------------
| ORDENAMIENTO
|--------------------------------------------------------------------------
*/
usort($data, function($a, $b) use ($orden_cuentas) {
    switch ($orden_cuentas) {
        case 'neto_asc':
            if ($a['neto_valor'] == $b['neto_valor']) {
                return 0;
            }
            return ($a['neto_valor'] > $b['neto_valor']) ? 1 : -1;

        case 'nombre_asc':
            return strcasecmp($a['nombre'], $b['nombre']);

        case 'nombre_desc':
            return strcasecmp($b['nombre'], $a['nombre']);

        case 'ingreso_desc':
            if ($a['ingreso_valor'] == $b['ingreso_valor']) {
                return 0;
            }
            return ($a['ingreso_valor'] < $b['ingreso_valor']) ? 1 : -1;

        case 'egreso_desc':
            if ($a['egreso_valor'] == $b['egreso_valor']) {
                return 0;
            }
            return ($a['egreso_valor'] < $b['egreso_valor']) ? 1 : -1;

        case 'neto_desc':
        default:
            if ($a['neto_valor'] == $b['neto_valor']) {
                return 0;
            }
            return ($a['neto_valor'] < $b['neto_valor']) ? 1 : -1;
    }
});

$arreglo = array(
    'echo' => 1,
    'totalrecords' => count($data),
    'totaldisplayrecords' => count($data),
    'data' => $data
);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($arreglo);
