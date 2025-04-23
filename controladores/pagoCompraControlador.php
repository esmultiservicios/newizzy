<?php
if ($peticionAjax) {
	require_once '../modelos/pagoCompraModelo.php';
} else {
	require_once './modelos/pagoCompraModelo.php';
}

class pagoCompraControlador extends pagoCompraModelo
{
	public function agregar_pago_compra_controlador_efectivo()
	{
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

		$compras_id = $_POST['compras_id_efectivo'];
		$consulta_fecha_compra = pagoCompraModelo::consultar_compra_fecha($compras_id)->fetch_assoc();
		$fecha = $_POST['fecha_compras_efectivo'];
		$importe = $_POST['monto_efectivoPurchase'];
		$abono = isset($_POST['efectivo_Purchase']) ? $_POST['efectivo_Purchase'] : 0;
		$cambio = $_POST['cambio_efectivoPurchase'];
		$empresa_id = $_SESSION['empresa_id_sd'];
		$cuentas_id = $_POST['metodopago_efectivo_compras'];
		$banco_id = 0;  // SIN BANCO
		$tipo_pago = $_POST['tipo_factura'];  // 1. CONTADO 2. CRÉDITO
		$metodo_pago = 1;  // EFECTIVO
		$efectivo = 0;
		$tarjeta = 0;
		$multiple_pago = $_POST['multiple_pago'];

		$referencia_pago1 = '';
		$referencia_pago2 = '';
		$referencia_pago3 = '';
		$colaboradores_id = $_SESSION['colaborador_id_sd'];

		$usuario = isset($_POST['usuario_efectivo_compras']) && $_POST['usuario_efectivo_compras'] !== '' ? (int) $_POST['usuario_efectivo_compras'] : $_SESSION['users_id_sd'];
		$fecha_registro = date('Y-m-d H:i:s');
		$estado = 1;

		$datos = [
			'multiple_pago' => isset($_POST['multiple_pago']) ? $_POST['multiple_pago'] : 0,
			'compras_id' => $compras_id,
			'fecha' => $fecha,
			'importe' => $importe,
			'abono' => $abono,
			'cambio' => $cambio,
			'usuario' => $usuario,
			'estado' => $estado,
			'fecha_registro' => $fecha_registro,
			'empresa' => $empresa_id,
			'tipo_pago' => $tipo_pago,
			'metodo_pago' => 1,  // efectivo
			'efectivo' => isset($_POST['efectivo_Purchase']) ? $_POST['efectivo_Purchase'] : $_POST['monto_efectivoPurchase'],
			'tarjeta' => 0,
			'banco_id' => $banco_id,
			'referencia_pago1' => $referencia_pago1,
			'referencia_pago2' => $referencia_pago2,
			'referencia_pago3' => $referencia_pago3,
			'colaboradores_id' => $colaboradores_id,
			'cuentas_id' => $cuentas_id,
		];

		$alert = pagoCompraModelo::agregar_pago_compras_base($datos);
		return mainModel::showNotification($alert);
	}

	public function agregar_pago_compra_controlador_tarjeta()
	{
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

		$usuario = isset($_POST['usuario_tarjeta_compras']) && $_POST['usuario_tarjeta_compras'] !== '' ? (int) $_POST['usuario_tarjeta_compras'] : $_SESSION['users_id_sd'];

		$datos = [
			'multiple_pago' => $_POST['multiple_pago'],
			'compras_id' => $_POST['compras_id_tarjeta'],
			'fecha' => $_POST['fecha_compras_tarjeta'],
			'importe' => $_POST['monto_efectivoPurchase'],
			'abono' => isset($_POST['monto_efectivo_tarjeta']) ? $_POST['monto_efectivo_tarjeta'] : 0,
			'cambio' => 0,
			'usuario' => $usuario,
			'estado' => 1,
			'fecha_registro' => date('Y-m-d H:i:s'),
			'empresa' => $_SESSION['empresa_id_sd'],
			'tipo_pago' => $_POST['tipo_factura'],  // 1. CONTADO 2. CRÉDITO
			'metodo_pago' => 2,  // TARJETA
			'efectivo' => 0,
			'tarjeta' => isset($_POST['monto_efectivo_tarjeta']) ? $_POST['monto_efectivo_tarjeta'] : $_POST['monto_efectivoPurchase'],
			'banco_id' => 0,
			'referencia_pago1' => mainModel::cleanStringConverterCase($_POST['cr_Purchase']),  // TARJETA DE CREDITO
			'referencia_pago2' => mainModel::cleanStringConverterCase($_POST['exp']),  // FECHA DE EXPIRACION
			'referencia_pago3' => mainModel::cleanStringConverterCase($_POST['cvcpwd'])  // NUMERO DE APROBACIÓN
		];

		$alert = pagoCompraModelo::agregar_pago_compras_base($datos);
		return mainModel::showNotification($alert);
	}

	// TRANSFERENCIA
	public function agregar_pago_compra_controlador_transferencia()
	{
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

		$usuario = isset($_POST['usuario_transferencia_compras']) && $_POST['usuario_transferencia_compras'] !== '' ? (int) $_POST['usuario_transferencia_compras'] : $_SESSION['users_id_sd'];

		$datos = [
			'multiple_pago' => $_POST['multiple_pago'],
			'compras_id' => $_POST['compras_id_transferencia'],
			'fecha' => $_POST['fecha_compras_transferencia'],
			'importe' => $_POST['monto_efectivoPurchase'],
			'abono' => isset($_POST['importe_transferencia']) ? $_POST['importe_transferencia'] : 0,
			'cambio' => 0,
			'usuario' => $usuario,
			'estado' => 1,
			'fecha_registro' => date('Y-m-d H:i:s'),
			'empresa' => $_SESSION['empresa_id_sd'],
			'tipo_pago' => $_POST['tipo_factura'],  // 1. CONTADO 2. CRÉDITO
			'metodo_pago' => 3,  // TRASFERENCIA
			'tarjeta' => 0,
			'efectivo' => isset($_POST['importe_transferencia']) ? $_POST['importe_transferencia'] : $_POST['monto_efectivoPurchase'],
			'banco_id' => $_POST['bk_nm'],
			'referencia_pago1' => mainModel::cleanStringConverterCase($_POST['ben_nm']),  // TARJETA DE CREDITO
			'referencia_pago2' => '',
			'referencia_pago3' => ''
		];

		$alert = pagoCompraModelo::agregar_pago_compras_base($datos);
		return mainModel::showNotification($alert);
	}

	// PAGO CON CHEQUE
	public function agregar_pago_compra_controlador_cheque()
	{
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

		$usuario = isset($_POST['usuario_cheque_compras']) && $_POST['usuario_cheque_compras'] !== '' ? (int) $_POST['usuario_cheque_compras'] : $_SESSION['users_id_sd'];

		$datos = [
			'multiple_pago' => $_POST['multiple_pago'],
			'compras_id' => $_POST['compras_id_cheque'],
			'fecha' => $_POST['fecha_compras_cheque'],
			'importe' => $_POST['monto_efectivoPurchase'],
			'abono' => isset($_POST['importe_cheque']) ? $_POST['importe_cheque'] : 0,
			'cambio' => 0,
			'usuario' => $usuario,
			'estado' => 1,
			'fecha_registro' => date('Y-m-d H:i:s'),
			'empresa' => $_SESSION['empresa_id_sd'],
			'tipo_pago' => $_POST['tipo_factura'],  // 1. CONTADO 2. CRÉDITO
			'metodo_pago' => 4,  // CHEQUE
			'tarjeta' => 0,
			'efectivo' => isset($_POST['importe_cheque']) ? $_POST['importe_cheque'] : $_POST['monto_efectivoPurchase'],
			'banco_id' => $_POST['bk_nm_chk'],
			'referencia_pago1' => mainModel::cleanStringConverterCase($_POST['check_num']),
			'referencia_pago2' => '',
			'referencia_pago3' => ''
		];

		$alert = pagoCompraModelo::agregar_pago_compras_base($datos);
		return mainModel::showNotification($alert);
	}

	public function cancelar_pago_controlador()
	{
		$pagos_id = $_POST['pagos_id'];

		$query = pagoCompraModelo::cancelar_pago_modelo($pagos_id);

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
				'funcion' => 'modal_pagosPurchase'
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

		return mainModel::showNotification($alert);
	}
}
