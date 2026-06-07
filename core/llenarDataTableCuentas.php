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
| CONEXIÓN
|--------------------------------------------------------------------------
| En este proyecto connection() NO es estático, por eso debe llamarse desde
| la instancia $insMainModel.
|--------------------------------------------------------------------------
*/
$conexion = $insMainModel->connection();

/*
|--------------------------------------------------------------------------
| OBTENER CUENTAS
|--------------------------------------------------------------------------
| Si viene estado 0 o 1, usamos el método existente.
| Si viene vacío, cargamos todas las cuentas.
|--------------------------------------------------------------------------
*/
if ($estado === '0' || $estado === '1') {
	$result = $insMainModel->getCuentasContabilidad($estado);
} else {
	$query_cuentas = "SELECT cuentas_id, codigo, nombre, estado, fecha_registro, es_inversion FROM cuentas ORDER BY nombre ASC";
	$result = $conexion->query($query_cuentas) or die($conexion->error);
}

$data = array();

while ($row = $result->fetch_assoc()) {
	$cuentas_id = $row['cuentas_id'];

	$datos = [
		'fechai' => $fechai,
		'fechaf' => $fechaf,
		'cuentas_id' => $cuentas_id
	];

	/*
	|--------------------------------------------------------------------------
	| INGRESOS
	|--------------------------------------------------------------------------
	*/
	$result_ingresos = $insMainModel->getCuentasIngresos($datos);
	$row_ingresos = $result_ingresos ? $result_ingresos->fetch_assoc() : null;
	$ingreso = isset($row_ingresos['ingresos']) ? (float)$row_ingresos['ingresos'] : 0.0;

	/*
	|--------------------------------------------------------------------------
	| SALDO ANTERIOR
	|--------------------------------------------------------------------------
	*/
	$result_saldo_anterior = $insMainModel->getSaldoMovimientosCuentasSaldoAnterior($datos);

	$saldo_anterior = 0.0;
	$saldo_cierre = 0.0;

	if ($result_saldo_anterior && $result_saldo_anterior->num_rows > 0) {
		$row_saldo_anterior = $result_saldo_anterior->fetch_assoc();
		$saldo_anterior = isset($row_saldo_anterior['saldo']) ? (float)$row_saldo_anterior['saldo'] : 0.0;
	} else {
		$result_ultimo_saldo = $insMainModel->getSaldoMovimientosCuentasUltimoSaldo($datos);

		if ($result_ultimo_saldo && $result_ultimo_saldo->num_rows > 0) {
			$row_ultimo_saldo = $result_ultimo_saldo->fetch_assoc();
			$saldo_anterior = isset($row_ultimo_saldo['saldo']) ? (float)$row_ultimo_saldo['saldo'] : 0.0;
			$fecha = isset($row_ultimo_saldo['fecha']) ? $row_ultimo_saldo['fecha'] : '';

			if ($fecha !== '') {
				$result_ultimo_fecha_valores = $insMainModel->getSaldoMovimientosCuentasUltimaFecha($cuentas_id, $fecha);

				if ($result_ultimo_fecha_valores && $result_ultimo_fecha_valores->num_rows > 0) {
					$saldo_anterior = isset($row_ultimo_saldo['saldo']) ? (float)$row_ultimo_saldo['saldo'] : 0.0;
				} else {
					$saldo_anterior = 0.0;
				}
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| EGRESOS
	|--------------------------------------------------------------------------
	*/
	$result_egresos = $insMainModel->getCuentaEgresos($datos);
	$row_egresos = $result_egresos ? $result_egresos->fetch_assoc() : null;
	$egreso = isset($row_egresos['egresos']) ? (float)$row_egresos['egresos'] : 0.0;

	/*
	|--------------------------------------------------------------------------
	| CÁLCULOS
	|--------------------------------------------------------------------------
	*/
	$saldo_cierre = $ingreso - $egreso;
	$neto = $saldo_anterior + $saldo_cierre;

	$saldo_anterior = is_null($saldo_anterior) ? 0.0 : (float)$saldo_anterior;
	$ingreso = is_null($ingreso) ? 0.0 : (float)$ingreso;
	$egreso = is_null($egreso) ? 0.0 : (float)$egreso;
	$saldo_cierre = is_null($saldo_cierre) ? 0.0 : (float)$saldo_cierre;
	$neto = is_null($neto) ? 0.0 : (float)$neto;

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