<?php
if ($peticionAjax) {
    require_once '../modelos/pagoFacturaModelo.php';
} else {
    require_once './modelos/pagoFacturaModelo.php';
}

class pagoFacturaControlador extends pagoFacturaModelo
{
    protected function prepararDatosPago($tipoPago) {
        if (!isset($_SESSION['user_sd'])) {
            session_start(['name' => 'SD']);
        }

        $campoId = "factura_id_" . $tipoPago;
        $campoFecha = "fecha_" . $tipoPago;
        $campoMonto = "monto_" . $tipoPago;
        $campoUsuario = "usuario_" . $tipoPago;

        // Asegurar que el usuario tenga un valor válido
        $usuario = isset($_POST[$campoUsuario]) && $_POST[$campoUsuario] !== '' 
                 ? intval($_POST[$campoUsuario]) 
                 : $_SESSION['users_id_sd'];

        $datosBase = [
            'multiple_pago' => isset($_POST['multiple_pago']) ? intval($_POST['multiple_pago']) : 0,
            'facturas_id' => intval($_POST[$campoId]),
            'fecha' => $_POST[$campoFecha] ?? date('Y-m-d'),
            'importe' => floatval($_POST[$campoMonto]),
            'cambio' => 0,
            'usuario' => $usuario,
            'estado' => 2,
            'estado_factura' => intval($_POST['tipo_factura']),
            'fecha_registro' => date('Y-m-d H:i:s'),
            'empresa' => intval($_SESSION['empresa_id_sd']),
            'abono' => floatval($_POST[$campoMonto]),
            'print_comprobante' => $_POST['comprobante_print'],
            'tipo_pago' => intval($_POST['tipo_factura']),
            'colaboradores_id' => intval($_SESSION['colaborador_id_sd']),
            'efectivo' => 0,
            'tarjeta' => 0
        ];

        return $datosBase;
    }

    // PAGO CON EFECTIVO
    public function agregar_pago_factura_controlador_efectivo()
    {
        $datos = $this->prepararDatosPago('efectivo');
        
        $datos['tipo_pago_id'] = 1;  // EFECTIVO
        $datos['banco_id'] = 0;  // SIN BANCO
        $datos['cambio'] = floatval($_POST['cambio_efectivo']) * -1;
        $datos['abono'] = floatval($_POST['efectivo_bill']);
        $datos['efectivo'] = floatval($_POST['efectivo_bill']);
        $datos['referencia_pago1'] = '';
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $alert = pagoFacturaModelo::agregar_pago_factura_base($datos);
        return mainModel::showNotification($alert);
    }

    // PAGO CON TARJETA
    public function agregar_pago_factura_controlador_tarjeta()
    {
        $datos = $this->prepararDatosPago('tarjeta');
        
        $datos['tipo_pago_id'] = 2;  // TARJETA
        $datos['banco_id'] = 0;  // SIN BANCO
        $datos['tarjeta'] = floatval($_POST['monto_efectivo']);
        $datos['referencia_pago1'] = mainModel::cleanStringConverterCase($_POST['cr_bill']);
        $datos['referencia_pago2'] = mainModel::cleanStringConverterCase($_POST['exp']);
        $datos['referencia_pago3'] = mainModel::cleanStringConverterCase($_POST['cvcpwd']);

        if ($datos['estado_factura'] == 1) {
            $datos['abono'] = 0;
            $datos['tarjeta'] = floatval($_POST['importe']);
        }

        $alert = pagoFacturaModelo::agregar_pago_factura_base($datos);
        return mainModel::showNotification($alert);
    }

    // PAGO CON TRANSFERENCIA
    public function agregar_pago_factura_controlador_transferencia()
    {
        $datos = $this->prepararDatosPago('transferencia');
        
        $datos['tipo_pago_id'] = 3;  // TRANSFERENCIA
        $datos['banco_id'] = intval($_POST['bk_nm']);
        $datos['referencia_pago1'] = mainModel::cleanStringConverterCase($_POST['ben_nm']);
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $alert = pagoFacturaModelo::agregar_pago_factura_base($datos);
        return mainModel::showNotification($alert);
    }

    // PAGO CON CHEQUE
    public function agregar_pago_factura_controlador_cheque()
    {
        $datos = $this->prepararDatosPago('cheque');
        
        $datos['tipo_pago_id'] = 4;  // CHEQUE
        $datos['banco_id'] = intval($_POST['bk_nm_chk']);
        $datos['referencia_pago1'] = mainModel::cleanStringConverterCase($_POST['check_num']);
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $alert = pagoFacturaModelo::agregar_pago_factura_base($datos);
        return mainModel::showNotification($alert);
    }

    public function cancelar_pago_controlador()
    {
        $pagos_id = intval($_POST['pagos_id']);
        $query = pagoFacturaModelo::cancelar_pago_modelo($pagos_id);

        $alert = [
            'alert' => $query ? 'clear' : 'simple',
            'title' => $query ? 'Registro eliminado' : 'Ocurrio un error inesperado',
            'text' => $query ? 'El registro se ha eliminado correctamente' : 'No hemos podido procesar su solicitud',
            'type' => $query ? 'success' : 'error',
            'form' => '',
            'funcion' => $query ? 'modal_pagos' : ''
        ];

        return mainModel::showNotification($alert);
    }
}