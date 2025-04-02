<?php
$peticionAjax = true;
require_once 'configGenerales.php';
require_once 'mainModel.php';

$insMainModel = new mainModel();

$result = $insMainModel->getCuentasContabilidad();

$arreglo = array();
$importe_venta = 0.0;
$neto = 0.0;
$saldo_anterior = 0.0;
$saldo_cierre = 0.0;

$data = array();

while ($row = $result->fetch_assoc()) {
	$cuentas_id = $row['cuentas_id'];

	$datos = [
		'fechai' => $_POST['fechai'],
		'fechaf' => $_POST['fechaf'],
		'cuentas_id' => $cuentas_id
	];

	$result_ingresos = $insMainModel->getCuentasIngresos($datos);
	$row_ingresos = $result_ingresos->fetch_assoc();
	$ingreso = $row_ingresos['ingresos'];

	// Obtener el año de la fecha anterior
	$fecha_anterior = date('Y-m-d', strtotime('-1 month', strtotime($_POST['fechai'])));
	$año_anterior = date('Y', strtotime($fecha_anterior));
	$mes_anterior = date('m', strtotime($fecha_anterior));

	/* ####################################################################################### */
	$result_saldo_anterior = $insMainModel->getSaldoMovimientosCuentasSaldoAnterior($datos);

	$saldo_anterior = 0.0;
	$saldo_cierre = 0.0;

	if ($result_saldo_anterior->num_rows > 0) {
		$row_saldo_anterior = $result_saldo_anterior->fetch_assoc();
		$saldo_anterior = $row_saldo_anterior['saldo'];
	} else {
		// Consultamos el último saldo de la cuenta
		$result_ultimo_saldo = $insMainModel->getSaldoMovimientosCuentasUltimoSaldo($datos);

		if ($result_ultimo_saldo->num_rows > 0) {
			$row_ultimo_saldo = $result_ultimo_saldo->fetch_assoc();
			$saldo_anterior = $row_ultimo_saldo['saldo'];
			$fecha = $row_ultimo_saldo['fecha'];

			// Consultamos los registros anteriores a la fecha del último saldo
			$result_ultimo_fecha_valores = $insMainModel->getSaldoMovimientosCuentasUltimaFecha($cuentas_id, $fecha);

			if ($result_ultimo_fecha_valores->num_rows > 0) {
				$saldo_anterior = $row_ultimo_saldo['saldo'];
			} else {
				$saldo_anterior = 0;
			}
		}
	}

	$result_egresos = $insMainModel->getCuentaEgresos($datos);
	$row_egresos = $result_egresos->fetch_assoc();
	$egreso = $row_egresos['egresos'];

	$saldo_cierre = $ingreso - $egreso;

	$neto = $saldo_anterior + $saldo_cierre;

	// Asegurarse de que las variables no sean null antes de usar number_format
	$saldo_anterior = (is_null($saldo_anterior)) ? 0.0 : $saldo_anterior;
	$ingreso = (is_null($ingreso)) ? 0.0 : $ingreso;
	$egreso = (is_null($egreso)) ? 0.0 : $egreso;
	$saldo_cierre = (is_null($saldo_cierre)) ? 0.0 : $saldo_cierre;
	$neto = (is_null($neto)) ? 0.0 : $neto;

	$data[] = array(
		'cuentas_id' => $cuentas_id,
		'codigo' => $row['codigo'],
		'nombre' => $row['nombre'],
		'saldo_anterior' => 'L. ' . number_format($saldo_anterior, 2),
		'ingreso' => 'L. ' . number_format($ingreso, 2),
		'egreso' => 'L. ' . number_format($egreso, 2),
		'saldo_cierre' => 'L. ' . number_format($saldo_cierre, 2),
		'neto' => 'L. ' . number_format($neto, 2)
	);
}

$arreglo = array(
	'echo' => 1,
	'totalrecords' => count($data),
	'totaldisplayrecords' => count($data),
	'data' => $data
);

echo json_encode($arreglo);
?>
