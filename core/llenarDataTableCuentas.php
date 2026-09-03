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
| IMPORTANTE:
| - Ingresos/Egresos se calculan por la fecha contable del movimiento.
| - Saldo anterior NO se busca por la fecha más reciente.
| - El campo saldo es acumulado según el orden real de registro/procesamiento.
|   Por eso buscamos el último movimientos_cuentas_id existente ANTES del
|   inicio del período.
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

$stmtSaldoAnterior = $conexion->prepare("
    SELECT saldo
    FROM movimientos_cuentas
    WHERE cuentas_id = ?
      AND fecha < ?
    ORDER BY movimientos_cuentas_id DESC
    LIMIT 1
");

if (!$stmtSaldoAnterior) {
    die($conexion->error);
}

while ($row = $result->fetch_assoc()) {
    $cuentas_id = (int)$row['cuentas_id'];

    /*
    |--------------------------------------------------------------------------
    | INGRESOS / EGRESOS DEL PERÍODO
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
    | SALDO ANTERIOR
    |--------------------------------------------------------------------------
    | El saldo anterior debe ser el saldo acumulado del ÚLTIMO MOVIMIENTO
    | PROCESADO cuya fecha contable sea anterior al inicio del período.
    |
    | Ejemplo real:
    | ID 464 - fecha 2026-08-17 - saldo 108.98
    | ID 465 - fecha 2026-08-16 - saldo  92.98 (registrado después)
    |
    | ORDER BY fecha DESC devolvería 108.98 y sería incorrecto.
    | ORDER BY movimientos_cuentas_id DESC devuelve 92.98, que es el saldo
    | acumulado real antes de septiembre.
    |--------------------------------------------------------------------------
    */
    $stmtSaldoAnterior->bind_param('is', $cuentas_id, $fechai);
    $stmtSaldoAnterior->execute();

    $resultSaldoAnterior = $stmtSaldoAnterior->get_result();
    $rowSaldoAnterior = $resultSaldoAnterior ? $resultSaldoAnterior->fetch_assoc() : null;

    $saldo_anterior = isset($rowSaldoAnterior['saldo']) ? (float)$rowSaldoAnterior['saldo'] : 0.0;

    /*
    |--------------------------------------------------------------------------
    | CÁLCULOS
    |--------------------------------------------------------------------------
    */
    $saldo_cierre = $ingreso - $egreso;
    $neto = $saldo_anterior + $saldo_cierre;

    $saldo_anterior = (float)$saldo_anterior;
    $ingreso = (float)$ingreso;
    $egreso = (float)$egreso;
    $saldo_cierre = (float)$saldo_cierre;
    $neto = (float)$neto;

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
            number_format($neto, 2, '.', '');

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
        'saldo_anterior_valor' => $saldo_anterior,
        'ingreso_valor' => $ingreso,
        'egreso_valor' => $egreso,
        'saldo_cierre_valor' => $saldo_cierre,
        'neto_valor' => $neto,
        'saldo_anterior' => 'L. ' . number_format($saldo_anterior, 2),
        'ingreso' => 'L. ' . number_format($ingreso, 2),
        'egreso' => 'L. ' . number_format($egreso, 2),
        'saldo_cierre' => 'L. ' . number_format($saldo_cierre, 2),
        'neto' => 'L. ' . number_format($neto, 2)
    );
}

$stmtPeriodo->close();
$stmtSaldoAnterior->close();

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
