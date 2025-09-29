<?php
//pagoFacturaControlador.php
if ($peticionAjax) {
    require_once "../modelos/pagoFacturaModelo.php";
} else {
    require_once "./modelos/pagoFacturaModelo.php";
}

class pagoFacturaControlador extends pagoFacturaModelo {

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

        // Helpers
        $get = function($key, $alt = null) {
            if (isset($_POST[$key]) && $_POST[$key] !== '') return trim((string)$_POST[$key]);
            if ($alt && isset($_POST[$alt]) && $_POST[$alt] !== '') return trim((string)$_POST[$alt]);
            return '';
        };
        $getInt = function($key, $alt = null) use ($get) {
            $v = $get($key, $alt);
            return ($v === '') ? 0 : intval($v);
        };
        $getMoney = function($key, $alt = null) use ($get) {
            $v = $get($key, $alt);
            return ($v === '') ? 0.0 : $this->parseMonto($v);
        };

        $campoId      = "factura_id_" . $tipoPago;     // factura_id_efectivo/tarjeta/transferencia/cheque/puntos
        $campoFecha   = "fecha_" . $tipoPago;          // fecha_efectivo/tarjeta/transferencia/cheque/puntos
        $campoUsuario = "usuario_" . $tipoPago;        // usuario_efectivo/usuario_tarjeta/...

        if (!isset($_POST[$campoId])) {
            return ["status"=>false,"title"=>"Error","message"=>"No se recibió el ID de la factura"];
        }

        // === valores digitados / aplicados ===
        $montoEntregadoCliente = 0.0;   // lo que escribe el cajero (solo efectivo)
        $montoAplicado         = 0.0;   // lo que realmente se aplica al saldo

        //var_dump($_POST);

        // === Monto según tipo de pago ===
        switch ($tipoPago) {
            case 'efectivo':
                $montoEntregadoCliente = $getMoney('efectivo_bill');
                $montoAplicado         = $getMoney('monto_efectivo');
                if ($montoEntregadoCliente <= 0 && $montoAplicado > 0) {
                    $montoEntregadoCliente = $montoAplicado;
                }
                $monto = $montoEntregadoCliente;
                break;

            case 'tarjeta':
                $monto = $getMoney('importe_tarjeta', 'importe');
                $montoAplicado = $monto;
                break;

            case 'transferencia':
                $monto = $getMoney('importe_transferencia', 'importe');
                $montoAplicado = $monto;
                break;

            case 'cheque':
                $monto = $getMoney('importe_cheque', 'importe');
                $montoAplicado = $monto;
                break;

            case 'puntos':
                $monto = $getMoney('importe_puntos', 'importe');
                $montoAplicado = $monto;
                break;

            default:
                $monto = $getMoney('importe');
                $montoAplicado = $monto;
                break;
        }

        // Normaliza a 2 decimales
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

        // === Saldo pendiente CxC ===
        $saldoPendiente = 0.00;
        $saldoRes = mainModel::connection()->query(
            "SELECT ROUND(saldo,2) AS saldo FROM cobrar_clientes WHERE facturas_id = '".intval($_POST[$campoId])."'"
        );
        if ($saldoRes && $saldoRes->num_rows > 0) {
            $saldoPendiente = round((float)$saldoRes->fetch_assoc()['saldo'] + 1e-9, 2);
        }

        // Tolerancia
        $EPS = 0.005;

        // === Validaciones por tipo de factura ===
        if ($tipo_factura_post == 1) { // contado
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

        // Número formateado
        $relleno         = isset($factura['relleno']) ? intval($factura['relleno']) : 8;
        $numeroEntero    = isset($factura['numero_factura']) ? intval($factura['numero_factura']) : 0;
        $factura_number  = ($numeroEntero > 0) ? str_pad($numeroEntero, $relleno, "0", STR_PAD_LEFT) : '';

        // Usuario (permite override por campo específico del método)
        $usuario = (isset($_POST[$campoUsuario]) && $_POST[$campoUsuario] !== '')
            ? intval($_POST[$campoUsuario])
            : $_SESSION['users_id_sd'];

        // === Importe que afecta saldo ===
        $importeReal = ($tipo_factura_post == 1
            ? ($saldoPendiente > 0 ? $saldoPendiente : $monto)          // contado: liquida
            : ($montoAplicado > 0 ? $montoAplicado : $monto)            // crédito: lo aplicado
        );

        // === Cambio (solo contado, solo efectivo) ===
        $cambio = 0.00;
        if ($tipo_factura_post == 1) {
            $entregado = ($tipoPago === 'efectivo') ? $montoEntregadoCliente : $monto;
            $cambio = max(0, round($entregado - $importeReal, 2));
        }

        /* ===============================
        * REFERENCIAS POR TIPO DE PAGO
        * =============================== */
        $referencia1 = '';
        $referencia2 = '';
        $referencia3 = '';
        $banco_id    = 0;

        switch ($tipoPago) {
            case 'tarjeta':
                // Números vienen del JS (packCardForSubmit): cr_bill, exp, cvcpwd, usuario_tarjeta
                $referencia1 = $get('cr_bill', 'numero_tarjeta');       // Nº de tarjeta (enmascarado/parcial)
                $referencia2 = $get('exp', 'expiracion');                // Expiración (MMYY o MM/YY)
                $referencia3 = $get('cvcpwd', 'numero_aprobacion');      // Nº aprobación / voucher
                $usrTarjeta  = $getInt('usuario_tarjeta', 'usuario_recibe');
                if ($usrTarjeta > 0) $usuario = $usrTarjeta;
                $banco_id    = $getInt('banco_id_tarjeta', 'banco_id');  // opcional
                break;

            case 'transferencia':
                // Usa lo que tengas en el form: número de ref, beneficiario/cuenta
                $referencia1 = $get('ref_transferencia', 'numero_referencia'); // Nº referencia
                $referencia2 = $get('ben_nm', 'beneficiario');                 // Beneficiario
                $referencia3 = $get('cta_transferencia', 'cuenta');            // Cuenta / observación
                $banco_id    = $getInt('banco_id_transferencia', 'banco_id');
                break;

            case 'cheque':
                $referencia1 = $get('check_num', 'numero_cheque');             // Nº cheque
                $referencia2 = $get('banco_cheque', 'banco');                   // Banco
                $referencia3 = $get('titular_cheque', 'titular');               // Titular / obs.
                $banco_id    = $getInt('banco_id_cheque', 'banco_id');
                break;

            case 'puntos':
                // Guarda cuántos puntos se usaron y su equivalente como referencia
                $referencia1 = $get('puntos_uso', 'puntos_usar');               // Puntos usados
                $referencia2 = $get('equivalente_puntos');                      // Equivalente en moneda
                $referencia3 = 'Programa de puntos';
                break;

            // efectivo/no aplica -> referencias vacías
        }

        return [
            'multiple_pago'      => ($tipo_factura_post == 2 ? 1 : 0),
            'facturas_id'        => intval($_POST[$campoId]),
            'fecha'              => isset($_POST[$campoFecha]) ? $_POST[$campoFecha] : date('Y-m-d'),
            'importe'            => $importeReal,                       // lo que aplica al saldo
            'cambio'             => $cambio,                            // cambio (si aplica)
            'usuario'            => $usuario,
            'estado'             => 1,
            'tipo_factura'       => $tipo_factura_post,
            'fecha_registro'     => date('Y-m-d H:i:s'),
            'empresa'            => intval($_SESSION['empresa_id_sd']),
            'abono'              => $saldoPendiente,
            'print_comprobante'  => isset($_POST['comprobante_print']) ? $_POST['comprobante_print'] : 0,
            'colaboradores_id'   => intval($_SESSION['colaborador_id_sd']),
            'efectivo'           => ($tipoPago === 'efectivo') ? $montoEntregadoCliente : 0.0,  // guarda lo digitado en efectivo
            'tarjeta'            => ($tipoPago === 'tarjeta') ? 1 : 0,                           // marcador/flag si fue tarjeta
            'banco_id'           => $banco_id,
            'referencia_pago1'   => $referencia1,
            'referencia_pago2'   => $referencia2,
            'referencia_pago3'   => $referencia3,
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
        if (isset($datos['status']) && $datos['status'] === false) {
            $this->json(["status"=>false, "title"=>$datos['title'], "message"=>$datos['message'], "redirect"=>$datos['redirect'] ?? ""]);
        }

        $datos['tipo_pago_id'] = 1; // efectivo
        $datos['banco_id']     = 0;

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && $result['status'] === false) {
            $this->json(["status"=>false, "title"=>$result['title'] ?? "Error", "message"=>$result['message'] ?? "No se pudo registrar el pago"]);
        }

        $this->json([
            "status"=>true,
            "title"=>$result['title'] ?? "Pago registrado",
            "message"=>$result['message'] ?? "Pago en efectivo registrado correctamente",
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
        if (isset($datos['status']) && $datos['status'] === false) {
            $this->json(["status"=>false, "title"=>$datos['title'], "message"=>$datos['message'], "redirect"=>$datos['redirect'] ?? ""]);
        }

        // Tipo y banco
        $datos['tipo_pago_id'] = 2; // tarjeta
        $datos['banco_id']     = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;

        // Guardar también el monto en la columna "tarjeta" de la tabla pagos
        // (en prepararDatosPago ya viene 'importe' calculado correctamente)
        $datos['tarjeta'] = $datos['importe'];

        // Descripciones para pagos_detalles:
        // descripcion1 = numero de tarjeta (cr_bill)
        // descripcion2 = expiracion (exp, MM/YY)
        // descripcion3 = numero de aprobacion (cvcpwd)
        $datos['referencia_pago1'] = isset($_POST['cr_bill']) ? $_POST['cr_bill'] : '';
        $datos['referencia_pago2'] = isset($_POST['exp']) ? $_POST['exp'] : '';
        $datos['referencia_pago3'] = isset($_POST['cvcpwd']) ? $_POST['cvcpwd'] : '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && $result['status'] === false) {
            $this->json(["status"=>false, "title"=>$result['title'] ?? "Error", "message"=>$result['message'] ?? "No se pudo registrar el pago"]);
        }

        $this->json([
            "status"        => true,
            "title"         => $result['title']   ?? "Pago registrado",
            "message"       => $result['message'] ?? "Pago con tarjeta registrado correctamente",
            "form"          => "formTarjetaBill",
            "funcion"       => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=> true
        ]);
    }

    /* ===========================
    * TRANSFERENCIA
    * =========================== */
    public function agregar_pago_factura_controlador_transferencia() {
        $datos = $this->prepararDatosPago('transferencia');
        if (isset($datos['status']) && $datos['status'] === false) {
            $this->json(["status"=>false, "title"=>$datos['title'], "message"=>$datos['message'], "redirect"=>$datos['redirect'] ?? ""]);
        }

        $datos['tipo_pago_id'] = 3; // transferencia
        $datos['banco_id']     = isset($_POST['bk_nm']) ? intval($_POST['bk_nm']) : 0;

        // Descripciones para pagos_detalles:
        // descripcion1 = numero de autorizacion (ben_nm)
        // descripcion2 = ''
        // descripcion3 = ''
        $datos['referencia_pago1'] = isset($_POST['ben_nm']) ? $_POST['ben_nm'] : '';
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && $result['status'] === false) {
            $this->json(["status"=>false, "title"=>$result['title'] ?? "Error", "message"=>$result['message'] ?? "No se pudo registrar el pago"]);
        }

        $this->json([
            "status"        => true,
            "title"         => $result['title']   ?? "Pago registrado",
            "message"       => $result['message'] ?? "Transferencia registrada correctamente",
            "form"          => "formTransferenciaBill",
            "funcion"       => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=> true
        ]);
    }
    
    /* ===========================
    * CHEQUE
    * =========================== */
    public function agregar_pago_factura_controlador_cheque() {
        $datos = $this->prepararDatosPago('cheque');
        if (isset($datos['status']) && $datos['status'] === false) {
            $this->json(["status"=>false, "title"=>$datos['title'], "message"=>$datos['message'], "redirect"=>$datos['redirect'] ?? ""]);
        }

        $datos['tipo_pago_id'] = 4; // cheque
        $datos['banco_id']     = isset($_POST['bk_nm_chk']) ? intval($_POST['bk_nm_chk']) : 0;

        // SOLO descripcion1 = número de cheque
        $numCheque                 = isset($_POST['check_num']) ? $_POST['check_num'] : (isset($_POST['num_chk']) ? $_POST['num_chk'] : '');
        $datos['referencia_pago1'] = $numCheque;
        $datos['referencia_pago2'] = '';
        $datos['referencia_pago3'] = '';

        $result = pagoFacturaModelo::agregar_pago_factura_base($datos);
        if (isset($result['status']) && $result['status'] === false) {
            $this->json(["status"=>false, "title"=>$result['title'] ?? "Error", "message"=>$result['message'] ?? "No se pudo registrar el pago"]);
        }

        $this->json([
            "status"        => true,
            "title"         => $result['title']   ?? "Pago registrado",
            "message"       => $result['message'] ?? "Cheque registrado correctamente",
            "form"          => "formChequeBill",
            "funcion"       => $result['funcion'] ?? "listar_cuentas_por_cobrar_clientes();getCollaboradoresModalPagoFacturas();",
            "closeAllModals"=> true
        ]);
    }

    /** Sanea montos: quita comas, moneda y deja solo dígitos, punto y signo. */
    private function parseMonto($str){
        $s = (string)$str;
        $s = str_replace([',','L','l',' '], '', $s);     // quitar separadores y moneda
        $s = preg_replace('/[^0-9.\-]/', '', $s);        // dejar dígitos, punto y signo
        return (float)$s;
    }

    private function json($arr){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr);
        exit;
    }    
}