<?php
if ($peticionAjax) {
    require_once "../modelos/pagoFacturaModelo.php";
} else {
    require_once "./modelos/pagoFacturaModelo.php";
}

class pagoFacturaControlador extends pagoFacturaModelo {
    protected function prepararDatosPago($tipoPago) {
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

        if (!isset($_POST[$campoId])) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se recibió el ID de la factura"
            ];
        }

        $monto = 0;
        if ($tipoPago === 'efectivo') {
            $monto = isset($_POST['efectivo_bill']) ? floatval($_POST['efectivo_bill']) : 0;
        } elseif ($tipoPago === 'tarjeta' || $tipoPago === 'transferencia' || $tipoPago === 'cheque') {
            $monto = isset($_POST['importe']) ? floatval($_POST['importe']) : 0;
        }

        $factura = mainModel::getFactura($_POST[$campoId])->fetch_assoc();
        if(!$factura) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "No se encontró la factura"
            ];
        }

        if ($monto <= 0) {
            return [
                "status" => false,
                "title" => "Error",
                "message" => "El monto debe ser mayor que cero"
            ];
        }

        $tipo_factura_post = isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura']) : 1;
        $saldoPendiente = $this->obtener_saldo_credito($_POST[$campoId]);
        
        // Validación CORREGIDA según tus condiciones exactas
        if ($tipo_factura_post == 1) {
            // PAGO COMPLETO: Solo validar que no sea menor
            if ($monto < $saldoPendiente) {
                return [
                    "status" => false,
                    "title" => "Error",
                    "message" => "Para pago completo debe ingresar un monto igual o mayor al saldo pendiente (L. " . number_format($saldoPendiente, 2) . ")"
                ];
            }
            // No hay validación de monto máximo para pago completo
        } else {
            // PAGO MÚLTIPLE: Validar que no sea mayor
            if ($monto > $saldoPendiente) {
                return [
                    "status" => false,
                    "title" => "Error",
                    "message" => "El monto no puede ser mayor al saldo pendiente (L. " . number_format($saldoPendiente, 2) . ")"
                ];
            }
        }

        $factura_number = isset($factura['number']) ? str_pad($factura['number'], 8, "0", STR_PAD_LEFT) : '';
        $usuario = isset($_POST[$campoUsuario]) && $_POST[$campoUsuario] !== '' 
                 ? intval($_POST[$campoUsuario]) 
                 : $_SESSION['users_id_sd'];

        $datosBase = [
            'multiple_pago' => $tipo_factura_post == 2 ? 1 : 0,
            'facturas_id' => intval($_POST[$campoId]),
            'fecha' => isset($_POST[$campoFecha]) ? $_POST[$campoFecha] : date('Y-m-d'),
            'importe' => $tipo_factura_post == 1 ? $saldoPendiente : $monto, // Importe real a registrar (saldo pendiente para pago completo)
            'cambio' => $tipo_factura_post == 1 ? ($monto - $saldoPendiente) : 0, // Cambio solo para pagos completos
            'usuario' => $usuario,
            'estado' => 1,
            'tipo_factura' => $tipo_factura_post,
            'fecha_registro' => date('Y-m-d H:i:s'),
            'empresa' => intval($_SESSION['empresa_id_sd']),
            'abono' => $saldoPendiente,
            'print_comprobante' => isset($_POST['comprobante_print']) ? $_POST['comprobante_print'] : 0,
            'colaboradores_id' => intval($_SESSION['colaborador_id_sd']),
            'efectivo' => $monto, // Monto recibido del cliente (efectivo_bill)
            'tarjeta' => 0,
            'banco_id' => 0,
            'referencia_pago1' => '',
            'referencia_pago2' => '',
            'referencia_pago3' => '',
            'clientes_id' => $factura['clientes_id'],
            'factura_number' => $factura_number
        ];

        return $datosBase;
    }

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
        
        $datos['tipo_pago_id'] = 1;
        $datos['banco_id'] = 0;

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
        
        $datos['tipo_pago_id'] = 2;
        $datos['banco_id'] = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;
        $datos['referencia_pago1'] = isset($_POST['cr_bill']) ? $_POST['cr_bill'] : '';
        $datos['referencia_pago2'] = isset($_POST['exp']) ? $_POST['exp'] : '';
        $datos['referencia_pago3'] = isset($_POST['cvcpwd']) ? $_POST['cvcpwd'] : '';

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
        
        $datos['tipo_pago_id'] = 3;
        $datos['banco_id'] = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;
        $datos['referencia_pago1'] = isset($_POST['ref']) ? $_POST['ref'] : '';
        $datos['referencia_pago2'] = isset($_POST['fecha_transferencia']) ? $_POST['fecha_transferencia'] : '';
        $datos['referencia_pago3'] = isset($_POST['observacion_transferencia']) ? $_POST['observacion_transferencia'] : '';

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
            "text" => $result['message'] ?? "Transferencia registrada correctamente",
            "form" => "formTransferenciaBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }

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
        
        $datos['tipo_pago_id'] = 4;
        $datos['banco_id'] = isset($_POST['bk_nm_chk']) ? intval($_POST['bk_nm_chk']) : 0;
        $datos['referencia_pago1'] = isset($_POST['num_chk']) ? $_POST['num_chk'] : '';
        $datos['referencia_pago2'] = isset($_POST['fecha_cheque']) ? $_POST['fecha_cheque'] : '';
        $datos['referencia_pago3'] = isset($_POST['observacion_cheque']) ? $_POST['observacion_cheque'] : '';

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
            "text" => $result['message'] ?? "Cheque registrado correctamente",
            "form" => "formChequeBill",
            "funcion" => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals" => true
        ]);
    }
    
    protected function obtener_saldo_credito($facturas_id) {
        $result = mainModel::connection()->query("SELECT saldo FROM cobrar_clientes WHERE facturas_id = '$facturas_id'");
        if($result->num_rows == 0) {
            throw new Exception("No se encontró la cuenta por cobrar para esta factura");
        }
        
        $data = $result->fetch_assoc();
        return $data['saldo'];
    }
}