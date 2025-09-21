<?php
//pagoFacturaControlador.php
if ($peticionAjax) {
    require_once "../modelos/pagoFacturaModelo.php";
} else {
    require_once "./modelos/pagoFacturaModelo.php";
}

class pagoFacturaControlador extends pagoFacturaModelo {

    /* ===========================
    /* ===========================
    * PREPARAR DATOS DEL PAGO
    * =========================== */
    protected function prepararDatosPago($tipoPago) {
        $validacion = mainModel::validarSesion();
        if ($validacion['error']) {
            return [
                "status"   => false,
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion']
            ];
        }

        $campoId      = "factura_id_" . $tipoPago;     // factura_id_efectivo/tarjeta/transferencia/cheque
        $campoFecha   = "fecha_" . $tipoPago;          // fecha_efectivo/tarjeta/transferencia/cheque
        $campoUsuario = "usuario_" . $tipoPago;        // usuario_efectivo/tarjeta/...

        if (!isset($_POST[$campoId])) {
            return ["status"=>false,"title"=>"Error","message"=>"No se recibió el ID de la factura"];
        }

        // === Monto según tipo de pago ===
        switch ($tipoPago) {
            case 'efectivo':
                $monto = isset($_POST['efectivo_bill']) ? $this->parseMonto($_POST['efectivo_bill']) : 0.0;
                break;
            case 'tarjeta':
                $monto = isset($_POST['importe_tarjeta']) ? $this->parseMonto($_POST['importe_tarjeta']) : 0.0;
                if ($monto <= 0) {
                    $monto = isset($_POST['importe']) ? $this->parseMonto($_POST['importe']) : 0.0;
                }
                break;
            case 'transferencia':
                $monto = isset($_POST['importe_transferencia']) ? $this->parseMonto($_POST['importe_transferencia']) : 0.0;
                if ($monto <= 0) {
                    $monto = isset($_POST['importe']) ? $this->parseMonto($_POST['importe']) : 0.0;
                }
                break;
            case 'cheque':
                $monto = isset($_POST['importe_cheque']) ? $this->parseMonto($_POST['importe_cheque']) : 0.0;
                if ($monto <= 0) {
                    $monto = isset($_POST['importe']) ? $this->parseMonto($_POST['importe']) : 0.0;
                }
                break;
            case 'puntos':
                $monto = isset($_POST['importe_puntos']) ? $this->parseMonto($_POST['importe_puntos']) : 0.0;
                if ($monto <= 0) {
                    $monto = isset($_POST['importe']) ? $this->parseMonto($_POST['importe']) : 0.0;
                }
                break;
            default:
                $monto = isset($_POST['importe']) ? $this->parseMonto($_POST['importe']) : 0.0;
                break;
        }

        // Añade logging para debugging (elimina después de probar)
        error_log("Tipo pago: $tipoPago, Monto: $monto");
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'importe') !== false || strpos($key, 'efectivo') !== false) {
                error_log("Campo: $key = $value");
            }
        }

        // Normaliza a 2 decimales y elimina residuos binarios
        $monto = round($monto + 1e-9, 2);

        // === Factura ===
        $factura = mainModel::getFactura($_POST[$campoId])->fetch_assoc();
        if(!$factura) {
            return ["status"=>false,"title"=>"Error","message"=>"No se encontró la factura"];
        }

        if ($monto <= 0) {
            return ["status"=>false,"title"=>"Error","message"=>"El monto debe ser mayor que cero"];
        }

        // 1=contado, 2=crédito/abonos
        $tipo_factura_post = isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura'])
                        : (isset($_POST['tipo_factura_transferencia']) ? intval($_POST['tipo_factura_transferencia'])
                        : (isset($_POST['tipo_factura_cheque']) ? intval($_POST['tipo_factura_cheque'])
                        : ((isset($_POST['facturas_activo']) && $_POST['facturas_activo']=='1') ? 1 : 2)));

        $origen_pago = isset($_POST['origen_pago']) ? $_POST['origen_pago'] : 'facturacion';

        // === Saldo pendiente CxC (redondeado) ===
        $saldoPendiente = 0.00;
        $saldoRes = mainModel::connection()->query(
            "SELECT ROUND(saldo,2) AS saldo FROM cobrar_clientes WHERE facturas_id = '".intval($_POST[$campoId])."'"
        );
        if ($saldoRes && $saldoRes->num_rows > 0) {
            $saldoPendiente = round((float)$saldoRes->fetch_assoc()['saldo'] + 1e-9, 2);
        }

        // Tolerancia para comparaciones (½ centavo)
        $EPS = 0.005;

        // === Validaciones por tipo ===
        if ($tipo_factura_post == 1) { // contado (pago total)
            if ($monto + $EPS < $saldoPendiente) {
                return [
                    "status"=>false,"title"=>"Error",
                    "message"=>"Para pago completo debe ingresar un monto igual o mayor al saldo pendiente (L. ".number_format($saldoPendiente,2).")"
                ];
            }
        } else { // crédito / abono
            if ($monto - $saldoPendiente > $EPS) {
                return [
                    "status"=>false,"title"=>"Error",
                    "message"=>"El monto no puede ser mayor al saldo pendiente (L. ".number_format($saldoPendiente,2).")"
                ];
            }
        }

        // Número formateado (si lo ocupas después)
        $relleno         = isset($factura['relleno']) ? intval($factura['relleno']) : 8;
        $numeroEntero    = isset($factura['numero_factura']) ? intval($factura['numero_factura']) : 0;
        $factura_number  = ($numeroEntero > 0) ? str_pad($numeroEntero, $relleno, "0", STR_PAD_LEFT) : '';

        $usuario = (isset($_POST[$campoUsuario]) && $_POST[$campoUsuario] !== '')
            ? intval($_POST[$campoUsuario])
            : $_SESSION['users_id_sd'];

        // Importe que afecta saldo
        $importeReal = ($tipo_factura_post == 1
            ? ($saldoPendiente > 0 ? $saldoPendiente : $monto)
            : $monto
        );

        // Cambio contado
        $cambio = ($tipo_factura_post == 1
            ? max(0, round($monto - ($saldoPendiente > 0 ? $saldoPendiente : $monto), 2))
            : 0
        );

        return [
            'multiple_pago'      => ($tipo_factura_post == 2 ? 1 : 0),
            'facturas_id'        => intval($_POST[$campoId]),
            'fecha'              => isset($_POST[$campoFecha]) ? $_POST[$campoFecha] : date('Y-m-d'),
            'importe'            => $importeReal,
            'cambio'             => $cambio,
            'usuario'            => $usuario,
            'estado'             => 1,
            'tipo_factura'       => $tipo_factura_post,
            'fecha_registro'     => date('Y-m-d H:i:s'),
            'empresa'            => intval($_SESSION['empresa_id_sd']),
            'abono'              => $saldoPendiente,
            'print_comprobante'  => isset($_POST['comprobante_print']) ? $_POST['comprobante_print'] : 0,
            'colaboradores_id'   => intval($_SESSION['colaborador_id_sd']),
            'efectivo'           => $monto,
            'tarjeta'            => 0,
            'banco_id'           => 0,
            'referencia_pago1'   => '',
            'referencia_pago2'   => '',
            'referencia_pago3'   => '',
            'clientes_id'        => $factura['clientes_id'],
            'factura_number'     => $factura_number,
            'origen_pago'        => $origen_pago
        ];
    }

    /* ===========================
     * EFECTIVO
     * =========================== */
    public function agregar_pago_factura_controlador_efectivo() {
        $datos = $this->prepararDatosPago('efectivo');
        if (isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$datos['title'],"text"=>$datos['message'],
                "redirect"=>$datos['redirect'] ?? ''
            ]);
        }
        $datos['tipo_pago_id'] = 1; // efectivo
        $datos['banco_id']     = 0;

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$result['title'],"text"=>$result['message']
            ]);
        }

        return mainModel::showNotification([
            "type"=>"success",
            "title"=>$result['title'] ?? "Pago registrado",
            "text"=>$result['message'] ?? "Pago en efectivo registrado correctamente",
            "form"=>"formEfectivoBill",
            "funcion"=>$result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=>true
        ]);
    }

    /* ===========================
     * TARJETA
     * =========================== */
    public function agregar_pago_factura_controlador_tarjeta() {
        $datos = $this->prepararDatosPago('tarjeta');
        if (isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$datos['title'],"text"=>$datos['message'],
                "redirect"=>$datos['redirect'] ?? ''
            ]);
        }
        $datos['tipo_pago_id']      = 2; // tarjeta
        $datos['banco_id']          = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;
        // Descripciones: 1=cr_bill, 2=exp (MM/YY), 3=cvcpwd
        $datos['referencia_pago1']  = isset($_POST['cr_bill']) ? $_POST['cr_bill'] : '';
        $datos['referencia_pago2']  = isset($_POST['exp']) ? $_POST['exp'] : '';
        $datos['referencia_pago3']  = isset($_POST['cvcpwd']) ? $_POST['cvcpwd'] : '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$result['title'],"text"=>$result['message']
            ]);
        }

        return mainModel::showNotification([
            "type"=>"success",
            "title"=>$result['title'] ?? "Pago registrado",
            "text"=>$result['message'] ?? "Pago con tarjeta registrado correctamente",
            "form"=>"formTarjetaBill",
            "funcion"=>$result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=>true
        ]);
    }

    /* ===========================
     * TRANSFERENCIA
     * =========================== */
    public function agregar_pago_factura_controlador_transferencia() {
        $datos = $this->prepararDatosPago('transferencia');
        if (isset($datos['status']) && !$datos['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$datos['title'],"text"=>$datos['message'],
                "redirect"=>$datos['redirect'] ?? ''
            ]);
        }
        $datos['tipo_pago_id']      = 3; // transferencia
        $datos['banco_id']          = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;
        // SOLO descripcion1 = ben_nm
        $datos['referencia_pago1']  = isset($_POST['ben_nm']) ? $_POST['ben_nm'] : '';
        $datos['referencia_pago2']  = '';
        $datos['referencia_pago3']  = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && !$result['status']) {
            return mainModel::showNotification([
                "type"=>"error","title"=>$result['title'],"text"=>$result['message']
            ]);
        }

        return mainModel::showNotification([
            "type"=>"success",
            "title"=>$result['title'] ?? "Pago registrado",
            "text"=>$result['message'] ?? "Transferencia registrada correctamente",
            "form"=>"formTransferenciaBill",
            "funcion"=>$result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=>true
        ]);
    }

    /* ===========================
     * CHEQUE
     * =========================== */
    public function agregar_pago_factura_controlador_cheque() {
        $datos = $this->prepararDatosPago('cheque');
        if (isset($datos['status']) && !$datos['status']){
            return mainModel::showNotification([
                "type"=>"error","title"=>$datos['title'],"text"=>$datos['message'],
                "redirect"=>$datos['redirect'] ?? ''
            ]);
        }
        $datos['tipo_pago_id']      = 4; // cheque
        $datos['banco_id']          = isset($_POST['bk_nm_chk']) ? intval($_POST['bk_nm_chk']) : 0;
        // SOLO descripcion1 = check_num (o num_chk como respaldo)
        $numCheque                  = isset($_POST['check_num']) ? $_POST['check_num'] : (isset($_POST['num_chk']) ? $_POST['num_chk'] : '');
        $datos['referencia_pago1']  = $numCheque;
        $datos['referencia_pago2']  = '';
        $datos['referencia_pago3']  = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && !$result['status']){
            return mainModel::showNotification([
                "type"=>"error","title"=>$result['title'],"text"=>$result['message']
            ]);
        }

        return mainModel::showNotification([
            "type"=>"success",
            "title"=>$result['title'] ?? "Pago registrado",
            "text"=>$result['message'] ?? "Cheque registrado correctamente",
            "form"=>"formChequeBill",
            "funcion"=>$result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=>true
        ]);
    }

    /** Sanea montos: quita comas, moneda y deja solo dígitos, punto y signo. */
    private function parseMonto($str){
        $s = (string)$str;
        $s = str_replace([',','L','l',' '], '', $s);     // quitar separadores y moneda
        $s = preg_replace('/[^0-9.\-]/', '', $s);        // dejar dígitos, punto y signo
        return (float)$s;
    }
}