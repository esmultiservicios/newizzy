<?php
if ($peticionAjax) {
    require_once "../modelos/pagoFacturaModelo.php";
} else {
    require_once "./modelos/pagoFacturaModelo.php";
}

class pagoFacturaControlador extends pagoFacturaModelo {
    protected function prepararDatosPago($tipoPago) {
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

        $campoId = "factura_id_" . $tipoPago;
        $campoFecha = "fecha_" . $tipoPago;
        $campoMonto = $tipoPago === 'efectivo' ? 'efectivo_bill' : ($tipoPago === 'tarjeta' ? 'importe' : 'importe');
        $campoUsuario = "usuario_" . $tipoPago;

        // Validar campos requeridos
        if (!isset($_POST[$campoId]) || empty($_POST[$campoId])) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se recibió el ID de la factura"
            ];
        }

        // Validar y obtener el monto
        $monto = 0;
        if ($tipoPago === 'efectivo') {
            $monto = isset($_POST['efectivo_bill']) ? floatval($_POST['efectivo_bill']) : 0;
        } elseif ($tipoPago === 'tarjeta') {
            $monto = isset($_POST['importe']) ? floatval($_POST['importe']) : 0;
        } elseif ($tipoPago === 'transferencia') {
            $monto = isset($_POST['importe']) ? floatval($_POST['importe']) : 0;
        } elseif ($tipoPago === 'cheque') {
            $monto = isset($_POST['importe']) ? floatval($_POST['importe']) : 0;
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
            'facturas_id' => intval($_POST[$campoId]),
            'fecha' => isset($_POST[$campoFecha]) ? $_POST[$campoFecha] : date('Y-m-d'),
            'importe' => $monto,
            'cambio' => 0,
            'usuario' => $usuario,
            'estado' => 1, // Estado inicial activo
            'estado_factura' => isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura']) : 1,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'empresa' => intval($_SESSION['empresa_id_sd']),
            'abono' => $monto,
            'print_comprobante' => isset($_POST['comprobante_print']) ? $_POST['comprobante_print'] : 0,
            'tipo_pago' => isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura']) : 1,
            'colaboradores_id' => intval($_SESSION['colaborador_id_sd']),
            'efectivo' => 0,
            'tarjeta' => 0,
            'banco_id' => 0,
            'referencia_pago1' => '',
            'referencia_pago2' => '',
            'referencia_pago3' => ''
        ];

        return $datosBase;
    }

    // PAGO CON EFECTIVO
    public function agregar_pago_factura_controlador_efectivo() {
        $datos = $this->prepararDatosPago('efectivo');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "redirect" => $datos['redirect'] ?? ''
            ]);
        }
        
        $datos['tipo_pago_id'] = 1;  // EFECTIVO
        $datos['banco_id'] = 0;  // SIN BANCO
        $datos['cambio'] = isset($_POST['cambio_efectivo']) ? floatval($_POST['cambio_efectivo']) * -1 : 0;
        $datos['efectivo'] = $datos['importe'];
        $datos['abono'] = $datos['importe'];

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        
        if(isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $result['title'],
                "text" => $result['message']
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => $result['title'] ?? "Pago registrado",
            "text" => $result['message'] ?? "Pago en efectivo registrado correctamente",
            "form" => "formEfectivoBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }

    // PAGO CON TARJETA
    public function agregar_pago_factura_controlador_tarjeta() {
        $datos = $this->prepararDatosPago('tarjeta');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "redirect" => $datos['redirect'] ?? ''
            ]);
        }
        
        $datos['tipo_pago_id'] = 2;  // TARJETA
        $datos['banco_id'] = 0;  // SIN BANCO
        $datos['tarjeta'] = $datos['importe'];
        $datos['referencia_pago1'] = isset($_POST['cr_bill']) ? mainModel::cleanStringConverterCase($_POST['cr_bill']) : '';
        $datos['referencia_pago2'] = isset($_POST['exp']) ? mainModel::cleanStringConverterCase($_POST['exp']) : '';
        $datos['referencia_pago3'] = isset($_POST['cvcpwd']) ? mainModel::cleanStringConverterCase($_POST['cvcpwd']) : '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        
        if(isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $result['title'],
                "text" => $result['message']
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => $result['title'] ?? "Pago registrado",
            "text" => $result['message'] ?? "Pago con tarjeta registrado correctamente",
            "form" => "formTarjetaBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }

    // PAGO CON TRANSFERENCIA
    public function agregar_pago_factura_controlador_transferencia() {
        $datos = $this->prepararDatosPago('transferencia');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "redirect" => $datos['redirect'] ?? ''
            ]);
        }
        
        if (!isset($_POST['bk_nm']) || empty($_POST['bk_nm'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar un banco"
            ]);
        }
        
        $datos['tipo_pago_id'] = 3;  // TRANSFERENCIA
        $datos['banco_id'] = intval($_POST['bk_nm']);
        $datos['referencia_pago1'] = isset($_POST['ben_nm']) ? mainModel::cleanStringConverterCase($_POST['ben_nm']) : '';
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        
        if(isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $result['title'],
                "text" => $result['message']
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => $result['title'] ?? "Pago registrado",
            "text" => $result['message'] ?? "Pago por transferencia registrado correctamente",
            "form" => "formTransferenciaBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }

    // PAGO CON CHEQUE
    public function agregar_pago_factura_controlador_cheque() {
        $datos = $this->prepararDatosPago('cheque');
        
        if(isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $datos['title'],
                "text" => $datos['message'],
                "redirect" => $datos['redirect'] ?? ''
            ]);
        }
        
        if (!isset($_POST['bk_nm_chk']) || empty($_POST['bk_nm_chk'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Debe seleccionar un banco"
            ]);
        }
        
        $datos['tipo_pago_id'] = 4;  // CHEQUE
        $datos['banco_id'] = intval($_POST['bk_nm_chk']);
        $datos['referencia_pago1'] = isset($_POST['check_num']) ? mainModel::cleanStringConverterCase($_POST['check_num']) : '';
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        
        if(isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => $result['title'],
                "text" => $result['message']
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => $result['title'] ?? "Pago registrado",
            "text" => $result['message'] ?? "Pago con cheque registrado correctamente",
            "form" => "formChequeBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }

    public function cancelar_pago_controlador() {
        if (!isset($_POST['pagos_id']) || empty($_POST['pagos_id'])) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se recibió el ID del pago"
            ]);
        }

        $pagos_id = intval($_POST['pagos_id']);
        $query = pagoFacturaModelo::cancelar_pago_modelo($pagos_id);

        if (!$query) {
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "No se pudo cancelar el pago"
            ]);
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Pago cancelado",
            "text" => "El pago se ha cancelado correctamente",
            "funcion" => "listar_cuentas_por_cobrar_clientes();"
        ]);
    }
}