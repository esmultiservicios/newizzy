<?php
if ($peticionAjax) {
    require_once '../modelos/pagoCompraModelo.php';
} else {
    require_once './modelos/pagoCompraModelo.php';
}

class pagoCompraControlador extends pagoCompraModelo
{
    protected function prepararDatosBase($tipoPago) 
    {
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return [
                "status" => false,
                "title" => "Error de sesión",
                "message" => $validacion['mensaje'],
                "redirect" => $validacion['redireccion']
            ];
        }

        $campoId = "compras_id_" . $tipoPago;
        $campoFecha = "fecha_compras_" . $tipoPago;
        $campoUsuario = "usuario_" . $tipoPago . "_compras";

        if (!isset($_POST[$campoId]) || empty($_POST[$campoId])) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se recibió el ID de la compra"
            ];
        }

        // Validar y obtener el monto
        $monto = 0;
        if (isset($_POST['monto_efectivoPurchase'])) {
            $monto = floatval($_POST['monto_efectivoPurchase']);
        }

        if ($monto <= 0) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "El monto debe ser mayor a cero"
            ];
        }

        // Asegurar que el usuario tenga un valor válido
        $usuario = isset($_POST[$campoUsuario]) && $_POST[$campoUsuario] !== '' 
                 ? intval($_POST[$campoUsuario]) 
                 : $_SESSION['users_id_sd'];

        $datosBase = [
            'multiple_pago' => isset($_POST['multiple_pago']) ? intval($_POST['multiple_pago']) : 0,
            'compras_id' => intval($_POST[$campoId]),
            'fecha' => isset($_POST[$campoFecha]) ? $_POST[$campoFecha] : date('Y-m-d'),
            'importe' => $monto,
            'abono' => 0,
            'cambio' => 0,
            'usuario' => $usuario,
            'estado' => 1,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'empresa' => intval($_SESSION['empresa_id_sd']),
            'tipo_pago' => isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura']) : 1,
            'metodo_pago' => 0, // Se establecerá según el tipo
            'efectivo' => 0,
            'tarjeta' => 0,
            'banco_id' => 0,
            'referencia_pago1' => '',
            'referencia_pago2' => '',
            'referencia_pago3' => '',
            'colaboradores_id' => intval($_SESSION['colaborador_id_sd']),
            'cuentas_id' => 0
        ];

        return $datosBase;
    }

    public function agregar_pago_compra_controlador_efectivo()
    {
        $datos = $this->prepararDatosBase('efectivo');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "funcion" => isset($datos['redirect']) ? "window.location.href = '".$datos['redirect']."'" : ""
            ]);
        }

        // Configurar datos específicos de efectivo
        $datos['metodo_pago'] = 1;
        $datos['efectivo'] = isset($_POST['efectivo_Purchase']) ? floatval($_POST['efectivo_Purchase']) : floatval($_POST['monto_efectivoPurchase']);
        $datos['abono'] = $datos['efectivo'];
        $datos['cambio'] = isset($_POST['cambio_efectivoPurchase']) ? floatval($_POST['cambio_efectivoPurchase']) : 0;
        $datos['cuentas_id'] = isset($_POST['metodopago_efectivo_compras']) ? intval($_POST['metodopago_efectivo_compras']) : 0;

        $alert = pagoCompraModelo::agregar_pago_compras_base($datos);
        
        // Agregar función para limpiar formulario
        if(isset($alert['funcion'])) {
            $alert['funcion'] .= "limpiarFormularioPagos();";
        } else {
            $alert['funcion'] = "limpiarFormularioPagos();";
        }
        
        return mainModel::showNotification($alert);
    }

    public function agregar_pago_compra_controlador_tarjeta()
    {
        $datos = $this->prepararDatosBase('tarjeta');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "funcion" => isset($datos['redirect']) ? "window.location.href = '".$datos['redirect']."'" : ""
            ]);
        }

        // Configurar datos específicos de tarjeta
        $datos['metodo_pago'] = 2;
        $datos['tarjeta'] = isset($_POST['monto_efectivo_tarjeta']) ? floatval($_POST['monto_efectivo_tarjeta']) : floatval($_POST['monto_efectivoPurchase']);
        $datos['abono'] = $datos['tarjeta'];
        $datos['referencia_pago1'] = isset($_POST['cr_Purchase']) ? mainModel::cleanStringConverterCase($_POST['cr_Purchase']) : '';
        $datos['referencia_pago2'] = isset($_POST['exp']) ? mainModel::cleanStringConverterCase($_POST['exp']) : '';
        $datos['referencia_pago3'] = isset($_POST['cvcpwd']) ? mainModel::cleanStringConverterCase($_POST['cvcpwd']) : '';

        $alert = pagoCompraModelo::agregar_pago_compras_base($datos);
        
        // Agregar función para limpiar formulario
        if(isset($alert['funcion'])) {
            $alert['funcion'] .= "limpiarFormularioPagos();";
        } else {
            $alert['funcion'] = "limpiarFormularioPagos();";
        }
        
        return mainModel::showNotification($alert);
    }

    public function agregar_pago_compra_controlador_transferencia()
    {
        $datos = $this->prepararDatosBase('transferencia');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "funcion" => isset($datos['redirect']) ? "window.location.href = '".$datos['redirect']."'" : ""
            ]);
        }

        if (!isset($_POST['bk_nm']) || empty($_POST['bk_nm'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar un banco"
            ]);
        }

        // Configurar datos específicos de transferencia
        $datos['metodo_pago'] = 3;
        $datos['efectivo'] = isset($_POST['importe_transferencia']) ? floatval($_POST['importe_transferencia']) : floatval($_POST['monto_efectivoPurchase']);
        $datos['abono'] = $datos['efectivo'];
        $datos['banco_id'] = intval($_POST['bk_nm']);
        $datos['referencia_pago1'] = isset($_POST['ben_nm']) ? mainModel::cleanStringConverterCase($_POST['ben_nm']) : '';

        $alert = pagoCompraModelo::agregar_pago_compras_base($datos);
        
        // Agregar función para limpiar formulario
        if(isset($alert['funcion'])) {
            $alert['funcion'] .= "limpiarFormularioPagos();";
        } else {
            $alert['funcion'] = "limpiarFormularioPagos();";
        }
        
        return mainModel::showNotification($alert);
    }

    public function agregar_pago_compra_controlador_cheque()
    {
        $datos = $this->prepararDatosBase('cheque');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "funcion" => isset($datos['redirect']) ? "window.location.href = '".$datos['redirect']."'" : ""
            ]);
        }

        if (!isset($_POST['bk_nm_chk']) || empty($_POST['bk_nm_chk'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar un banco"
            ]);
        }

        // Configurar datos específicos de cheque
        $datos['metodo_pago'] = 4;
        $datos['efectivo'] = isset($_POST['importe_cheque']) ? floatval($_POST['importe_cheque']) : floatval($_POST['monto_efectivoPurchase']);
        $datos['abono'] = $datos['efectivo'];
        $datos['banco_id'] = intval($_POST['bk_nm_chk']);
        $datos['referencia_pago1'] = isset($_POST['check_num']) ? mainModel::cleanStringConverterCase($_POST['check_num']) : '';

        $alert = pagoCompraModelo::agregar_pago_compras_base($datos);
        
        // Agregar función para limpiar formulario
        if(isset($alert['funcion'])) {
            $alert['funcion'] .= "limpiarFormularioPagos();";
        } else {
            $alert['funcion'] = "limpiarFormularioPagos();";
        }
        
        return mainModel::showNotification($alert);
    }

    public function cancelar_pago_controlador()
    {
        if (!isset($_POST['pagos_id']) || empty($_POST['pagos_id'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se recibió el ID del pago"
            ]);
        }

        $query = pagoCompraModelo::cancelar_pago_modelo($_POST['pagos_id']);

        if ($query) {
            return mainModel::showNotification([
                "type" => "success",
                "title" => "Pago cancelado",
                "text" => "El pago se ha cancelado correctamente",
                "funcion" => "listar_cuentas_por_pagar_proveedores();limpiarFormularioPagos();"
            ]);
        } else {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo cancelar el pago"
            ]);
        }
    }
}