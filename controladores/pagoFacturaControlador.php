<?php
if ($peticionAjax) {
	require_once '../modelos/pagoFacturaModelo.php';
} else {
	require_once './modelos/pagoFacturaModelo.php';
}

class pagoFacturaControlador extends pagoFacturaModelo
{
	// PAGO CON EFECTIVO
	public function agregar_pago_factura_controlador_efectivo()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$facturas_id = $_POST['factura_id_efectivo'];
		$print_comprobante = $_POST['comprobante_print'];
		$consulta_fecha_factura = pagoFacturaModelo::consultar_factura_fecha($facturas_id)->fetch_assoc();
		$fecha = $_POST['fecha_efectivo'];
		$importe = $_POST['monto_efectivo'];
		$abono = $_POST['efectivo_bill'];
		$efectivo = $_POST['efectivo_bill'];
		$cambio = ($_POST['cambio_efectivo']) * -1;
		$empresa_id = $_SESSION['empresa_id_sd'];
		$colaboradores_id = $_SESSION['colaborador_id_sd'];
		$tipo_pago_id = 1;  // EFECTIVO
		$banco_id = 0;  // SIN BANCO
		$tipo_pago = $_POST['tipo_factura'];  // 1. SIN ABONO 2. CON ABONO
		$referencia_pago1 = '';
		$referencia_pago2 = '';  // DESCRIPCION ADICIONAL QUE SE ESCRIBE EN EL MODAL
		$referencia_pago3 = '';

		$usuario = isset($_POST['usuario_tarjeta']) && $_POST['usuario_tarjeta'] !== '' ? (int) $_POST['usuario_tarjeta'] : $_SESSION['users_id_sd'];

		$fecha_registro = date('Y-m-d H:i:s');
		$estado = 2;
		$estado_factura = $_POST['tipo_factura'];  // PAGADA 1 //credito 2
		$tarjeta = 0;

		$datos = [
			'multiple_pago' => isset($_POST['multiple_pago']) ? $_POST['multiple_pago'] : 0,
			'facturas_id' => $facturas_id,
			'fecha' => $fecha,
			'importe' => $importe,
			'cambio' => $cambio,
			'usuario' => $usuario,
			'estado' => $estado,
			'estado_factura' => $estado_factura,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'abono' => $_POST['efectivo_bill'],
			'tipo_pago_id' => $tipo_pago_id,
			'banco_id' => $banco_id,
			'referencia_pago1' => $referencia_pago1,
			'referencia_pago2' => $referencia_pago2,
			'referencia_pago3' => $referencia_pago3,
			'print_comprobante' => $_POST['comprobante_print'],
			'tipo_pago' => $tipo_pago,
			'efectivo' => $efectivo === '' ? 0 : $efectivo,
			'tarjeta' => $tarjeta === '' ? 0 : $tarjeta,
			'colaboradores_id' => $colaboradores_id,
			'recibide' => ''
		];

		$alert = pagoFacturaModelo::agregar_pago_factura_base($datos);

		return mainModel::sweetAlert($alert);
	}

	// PAGO CON TARJETA
	public function agregar_pago_factura_controlador_tarjeta()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$facturas_id = $_POST['factura_id_tarjeta'];
		$print_comprobante = $_POST['comprobante_print'];
		$consulta_fecha_factura = pagoFacturaModelo::consultar_factura_fecha($facturas_id)->fetch_assoc();
		$fecha = $_POST['fecha_tarjeta'];
		$importe = $_POST['importe'];
		$cambio = 0;
		$empresa_id = $_SESSION['empresa_id_sd'];
		$colaboradores_id = $_SESSION['colaborador_id_sd'];
		$tipo_pago_id = 2;  // TARJETA
		$banco_id = 0;  // SIN BANCO
		$tipo_pago = $_POST['tipo_factura'];  // 1. CONTADO 2. CRÉDITO	3.MIXTO
		$efectivo = 0;
		$tarjeta = $_POST['monto_efectivo'];
		$estado_factura = $_POST['tipo_factura'];  // PAGADA 1 //CREDITO 2
		$abono = $_POST['monto_efectivo'];

		$referencia_pago1 = mainModel::cleanStringConverterCase($_POST['cr_bill']);  // TARJETA DE CREDITO
		$referencia_pago2 = mainModel::cleanStringConverterCase($_POST['exp']);  // FECHA DE EXPIRACION
		$referencia_pago3 = mainModel::cleanStringConverterCase($_POST['cvcpwd']);  // NUMERO DE APROBACIÓN

		$usuario = isset($_POST['usuario_efectivo']) && $_POST['usuario_efectivo'] !== '' ? (int) $_POST['usuario_efectivo'] : $_SESSION['users_id_sd'];

		$fecha_registro = date('Y-m-d H:i:s');
		$estado = 2;

		if ($estado_factura == 1) {
			$abono = 0;
			$tarjeta = $_POST['importe'];
		}

		$datos = [
			'multiple_pago' => isset($_POST['multiple_pago']) ? $_POST['multiple_pago'] : 0,
			'facturas_id' => $facturas_id,
			'fecha' => $fecha,
			'importe' => $importe,
			'cambio' => $cambio,
			'usuario' => $usuario,
			'estado' => $estado,
			'estado_factura' => $estado_factura,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'abono' => $abono,
			'tipo_pago_id' => $tipo_pago_id,
			'banco_id' => $banco_id,
			'referencia_pago1' => $referencia_pago1,
			'referencia_pago2' => $referencia_pago2,
			'referencia_pago3' => $referencia_pago3,
			'print_comprobante' => $_POST['comprobante_print'],
			'tipo_pago' => $tipo_pago,
			'efectivo' => $efectivo === '' ? 0 : $efectivo,
			'tarjeta' => $tarjeta === '' ? 0 : $tarjeta,
			'colaboradores_id' => $colaboradores_id
		];

		$alert = pagoFacturaModelo::agregar_pago_factura_base($datos);

		return mainModel::sweetAlert($alert);
	}

	// PAGO CON TRANSFERENCIA
	public function agregar_pago_factura_controlador_transferencia()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$facturas_id = $_POST['factura_id_transferencia'];
		$print_comprobante = $_POST['comprobante_print'];
		$consulta_fecha_factura = pagoFacturaModelo::consultar_factura_fecha($facturas_id)->fetch_assoc();
		$fecha = date('Y-m-d');
		$importe = $_POST['monto_efectivo'];
		$cambio = 0;
		$abono = $_POST['importe'];
		$empresa_id = $_SESSION['empresa_id_sd'];
		$colaboradores_id = $_SESSION['colaborador_id_sd'];
		$tipo_pago_id = 3;  // TRANSFERENCIA
		$banco_id = $_POST['bk_nm'];
		$tipo_pago = $_POST['tipo_factura'];  // 1. CONTADO 2. CRÉDITO
		$estado_factura = $_POST['tipo_factura'];  // PAGADA 1 //credito 2
		$efectivo = 0;
		$tarjeta = 0;

		$referencia_pago1 = mainModel::cleanStringConverterCase($_POST['ben_nm']);  // TARJETA DE CREDITO
		$referencia_pago2 = '';
		$referencia_pago3 = '';

		$usuario = isset($_POST['usuario_transferencia']) && $_POST['usuario_transferencia'] !== '' ? (int) $_POST['usuario_transferencia'] : $_SESSION['users_id_sd'];

		$fecha_registro = date('Y-m-d H:i:s');
		$estado = 2;

		$datos = [
			'multiple_pago' => isset($_POST['multiple_pago']) ? $_POST['multiple_pago'] : 0,
			'facturas_id' => $facturas_id,
			'fecha' => $fecha,
			'importe' => $importe,
			'cambio' => $cambio,
			'usuario' => $usuario,
			'estado' => $estado,
			'estado_factura' => $estado_factura,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'abono' => $abono,
			'tipo_pago_id' => $tipo_pago_id,
			'banco_id' => $banco_id,
			'referencia_pago1' => $referencia_pago1,
			'referencia_pago2' => $referencia_pago2,
			'referencia_pago3' => $referencia_pago3,
			'print_comprobante' => $print_comprobante,
			'tipo_pago' => $tipo_pago,
			'efectivo' => $efectivo === '' ? 0 : $efectivo,
			'tarjeta' => $tarjeta === '' ? 0 : $tarjeta,
			'colaboradores_id' => $colaboradores_id
		];

		$alert = pagoFacturaModelo::agregar_pago_factura_base($datos);

		return mainModel::sweetAlert($alert);
	}

	// PAGO CON CHEQUE
	public function agregar_pago_factura_controlador_cheque()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$facturas_id = $_POST['factura_id_cheque'];
		$print_comprobante = $_POST['comprobante_print'];
		$consulta_fecha_factura = pagoFacturaModelo::consultar_factura_fecha($facturas_id)->fetch_assoc();
		$fecha = date('Y-m-d');
		$importe = $_POST['monto_efectivo'];
		$cambio = 0;
		$empresa_id = $_SESSION['empresa_id_sd'];
		$colaboradores_id = $_SESSION['colaborador_id_sd'];
		$tipo_pago_id = 4;  // CHEQUE
		$banco_id = $_POST['bk_nm_chk'];
		$tipo_pago = $_POST['tipo_factura'];  // 1. CONTADO 2. CRÉDITO
		$estado_factura = $_POST['tipo_factura'];  // 1 PAGADA 2 credito
		$efectivo = 0;
		$tarjeta = 0;

		$referencia_pago1 = mainModel::cleanStringConverterCase($_POST['check_num']);  // TARJETA DE CREDITO
		$referencia_pago2 = '';
		$referencia_pago3 = '';

		$usuario = isset($_POST['usuario_cheque']) && $_POST['usuario_cheque'] !== '' ? (int) $_POST['usuario_cheque'] : $_SESSION['users_id_sd'];

		$fecha_registro = date('Y-m-d H:i:s');
		$estado = 2;
		$abono = $_POST['importe'];

		$datos = [
			'multiple_pago' => isset($_POST['multiple_pago']) ? $_POST['multiple_pago'] : 0,
			'facturas_id' => $facturas_id,
			'fecha' => $fecha,
			'importe' => $importe,
			'cambio' => $cambio,
			'usuario' => $usuario,
			'estado' => $estado,
			'estado_factura' => $estado_factura,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'abono' => $abono,
			'tipo_pago_id' => $tipo_pago_id,
			'banco_id' => $banco_id,
			'referencia_pago1' => $referencia_pago1,
			'referencia_pago2' => $referencia_pago2,
			'referencia_pago3' => $referencia_pago3,
			'print_comprobante' => $print_comprobante,
			'tipo_pago' => $tipo_pago,
			'efectivo' => $efectivo === '' ? 0 : $efectivo,
			'tarjeta' => $tarjeta === '' ? 0 : $tarjeta,
			'colaboradores_id' => $colaboradores_id
		];

		$alert = pagoFacturaModelo::agregar_pago_factura_base($datos);
		return mainModel::sweetAlert($alert);
	}

	public function cancelar_pago_controlador()
	{
		$pagos_id = $_POST['pagos_id'];

		$query = pagoFacturaModelo::cancelar_pago_modelo($pagos_id);

		if ($query) {
			$alert = [
				'alert' => 'clear',
				'title' => 'Registro eliminado',
				'text' => 'El registro se ha eliminado correctamente',
				'type' => 'success',
				'btn-class' => 'btn-primary',
				'btn-text' => '¡Bien Hecho!',
				'form' => '',
				'id' => '',
				'valor' => 'Cancelar',
				'funcion' => 'modal_pagos'
			];
		} else {
			$alert = [
				'alert' => 'simple',
				'title' => 'Ocurrio un error inesperado',
				'text' => 'No hemos podido procesar su solicitud',
				'type' => 'error',
				'btn-class' => 'btn-danger',
			];
		}

		return mainModel::sweetAlert($alert);
	}
}
