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

        /* ==========================================================
         * Helpers de lectura
         * ----------------------------------------------------------
         * Se leen varios nombres porque el modal nuevo y algunos JS
         * antiguos no siempre envían el mismo name/id.
         * ========================================================== */
        $getAny = function($keys) {
            if (!is_array($keys)) {
                $keys = [$keys];
            }

            foreach ($keys as $key) {
                if (isset($_POST[$key]) && trim((string)$_POST[$key]) !== '') {
                    return trim((string)$_POST[$key]);
                }
            }

            return '';
        };

        $get = function($key, $alt = null) use ($getAny) {
            $keys = [$key];
            if ($alt !== null) {
                $keys[] = $alt;
            }
            return $getAny($keys);
        };

        $getInt = function($key, $alt = null) use ($get) {
            $v = $get($key, $alt);
            return ($v === '') ? 0 : intval($v);
        };

        $getMoneyAny = function($keys) use ($getAny) {
            $v = $getAny($keys);
            return ($v === '') ? 0.0 : $this->parseMonto($v);
        };

        $campoId      = "factura_id_" . $tipoPago;     // factura_id_efectivo/tarjeta/transferencia/cheque/puntos
        $campoFecha   = "fecha_" . $tipoPago;          // fecha_efectivo/tarjeta/transferencia/cheque/puntos
        $campoUsuario = "usuario_" . $tipoPago;        // usuario_efectivo/usuario_tarjeta/...

        if (!isset($_POST[$campoId]) || intval($_POST[$campoId]) <= 0) {
            return ["status"=>false,"title"=>"Error","message"=>"No se recibió el ID de la factura"];
        }

        $facturas_id = intval($_POST[$campoId]);

        // === Factura ===
        $rsFactura = mainModel::getFactura($facturas_id);
        $factura = ($rsFactura && $rsFactura->num_rows > 0) ? $rsFactura->fetch_assoc() : null;
        if(!$factura) {
            return ["status"=>false,"title"=>"Error","message"=>"No se encontró la factura"];
        }

        // 1=contado, 2=crédito/abonos
        $tipo_factura_post = isset($_POST['tipo_factura']) ? intval($_POST['tipo_factura'])
                        : (isset($_POST['tipo_factura_'.$tipoPago]) ? intval($_POST['tipo_factura_'.$tipoPago])
                        : (isset($_POST['tipo_factura_transferencia']) ? intval($_POST['tipo_factura_transferencia'])
                        : (isset($_POST['tipo_factura_cheque']) ? intval($_POST['tipo_factura_cheque'])
                        : ((isset($_POST['facturas_activo']) && $_POST['facturas_activo']=='1') ? 1 : 2))));

        $origen_pago = isset($_POST['origen_pago']) ? trim((string)$_POST['origen_pago']) : 'facturacion';
        $esCxc = ($origen_pago === 'cxc' || $origen_pago === 'CxC');

        // === Saldo pendiente CxC ===
        $saldoPendiente = 0.00;
        $saldoRes = mainModel::connection()->query(
            "SELECT ROUND(saldo,2) AS saldo FROM cobrar_clientes WHERE facturas_id = '".$facturas_id."' LIMIT 1"
        );
        if ($saldoRes && $saldoRes->num_rows > 0) {
            $saldoPendiente = round((float)$saldoRes->fetch_assoc()['saldo'] + 1e-9, 2);
        }

        // === Total real de la factura ===
        $totalFacturaBD = 0.00;
        if (isset($factura['importe'])) {
            $totalFacturaBD = round((float)$factura['importe'] + 1e-9, 2);
        }

        if ($totalFacturaBD <= 0) {
            $rsTotalFactura = mainModel::connection()->query(
                "SELECT ROUND(importe,2) AS importe FROM facturas WHERE facturas_id = '".$facturas_id."' LIMIT 1"
            );

            if ($rsTotalFactura && $rsTotalFactura->num_rows > 0) {
                $rowTotalFactura = $rsTotalFactura->fetch_assoc();
                $totalFacturaBD = round((float)$rowTotalFactura['importe'] + 1e-9, 2);
            }
        }

        // === valores digitados / aplicados ===
        $montoEntregadoCliente = 0.0;   // lo que escribe el cajero (solo efectivo)
        $montoAplicado         = 0.0;   // lo que realmente se aplica al saldo
        $monto                 = 0.0;   // valor recibido por el método seleccionado

        // === Monto según tipo de pago ===
        switch ($tipoPago) {
            case 'efectivo':
                $montoEntregadoCliente = $getMoneyAny([
                    'efectivo_bill',
                    'efectivo_recibido',
                    'monto_efectivo_recibido',
                    'importe_efectivo',
                    'efectivo',
                    'cash_bill'
                ]);

                $montoAplicado = $getMoneyAny([
                    'monto_efectivo',
                    'importe_aplicado_efectivo',
                    'total_efectivo',
                    'importe'
                ]);

                if ($montoEntregadoCliente <= 0 && $montoAplicado > 0) {
                    $montoEntregadoCliente = $montoAplicado;
                }

                $monto = $montoEntregadoCliente;
                break;

            case 'tarjeta':
                $monto = $getMoneyAny([
                    'importe_tarjeta',
                    'monto_tarjeta',
                    'total_tarjeta',
                    'importe_aplicado_tarjeta',
                    'importe'
                ]);
                $montoAplicado = $monto;
                break;

            case 'transferencia':
                $monto = $getMoneyAny([
                    'importe_transferencia',
                    'monto_transferencia',
                    'total_transferencia',
                    'importe_aplicado_transferencia',
                    'importe'
                ]);
                $montoAplicado = $monto;
                break;

            case 'cheque':
                $monto = $getMoneyAny([
                    'importe_cheque',
                    'monto_cheque',
                    'total_cheque',
                    'importe_aplicado_cheque',
                    'importe'
                ]);
                $montoAplicado = $monto;
                break;

            case 'puntos':
                $monto = $getMoneyAny([
                    'importe_puntos',
                    'monto_puntos',
                    'equivalente_puntos',
                    'importe'
                ]);
                $montoAplicado = $monto;
                break;

            default:
                $monto = $getMoneyAny(['importe', 'monto', 'total_pago']);
                $montoAplicado = $monto;
                break;
        }

        // Normaliza a 2 decimales
        $monto                 = round((float)$monto + 1e-9, 2);
        $montoAplicado          = round((float)$montoAplicado + 1e-9, 2);
        $montoEntregadoCliente  = round((float)$montoEntregadoCliente + 1e-9, 2);

        // Si es contado desde facturación y el método no es efectivo, el pago debe cubrir el total.
        // Esto evita falsos errores cuando el JS no envía importe_tarjeta/transferencia/cheque
        // porque el modal ya asumió el total de la factura.
        if ($tipo_factura_post == 1 && !$esCxc && $tipoPago !== 'efectivo' && $monto <= 0 && $totalFacturaBD > 0) {
            $monto = $totalFacturaBD;
            $montoAplicado = $totalFacturaBD;
        }

        // Tolerancia
        $EPS = 0.005;

        // === Importe que afecta saldo ===
        if ($tipo_factura_post == 1) {
            if ($esCxc) {
                // Desde CxC: liquida el saldo pendiente.
                $importeReal = ($saldoPendiente > 0 ? $saldoPendiente : ($totalFacturaBD > 0 ? $totalFacturaBD : $monto));
            } else {
                // Desde facturación: siempre toma el total real de la factura, no el efectivo entregado.
                $importeReal = ($totalFacturaBD > 0 ? $totalFacturaBD : ($saldoPendiente > 0 ? $saldoPendiente : ($montoAplicado > 0 ? $montoAplicado : $monto)));
            }
        } else {
            // Crédito / abono: toma el monto realmente aplicado.
            $importeReal = ($montoAplicado > 0 ? $montoAplicado : $monto);
        }

        $importeReal = round((float)$importeReal + 1e-9, 2);

        // === Validaciones por tipo de factura ===
        if ($tipo_factura_post == 1) { // contado
            if ($importeReal <= 0) {
                return [
                    "status"=>false,"title"=>"Error",
                    "message"=>"No se pudo determinar el total real de la factura."
                ];
            }

            if ($tipoPago === 'efectivo') {
                if ($montoEntregadoCliente <= 0) {
                    return ["status"=>false,"title"=>"Error","message"=>"El efectivo recibido debe ser mayor que cero"];
                }

                if ($montoEntregadoCliente + $EPS < $importeReal) {
                    return [
                        "status"=>false,"title"=>"Error",
                        "message"=>"El monto recibido no puede ser menor al total de la factura (L. ".number_format($importeReal,2).")"
                    ];
                }
            } else {
                if ($monto <= 0) {
                    return ["status"=>false,"title"=>"Error","message"=>"El monto debe ser mayor que cero"];
                }

                if ($monto + $EPS < $importeReal) {
                    return [
                        "status"=>false,"title"=>"Error",
                        "message"=>"El monto recibido no puede ser menor al total de la factura (L. ".number_format($importeReal,2).")"
                    ];
                }
            }
        } else { // crédito / abono
            if ($importeReal <= 0) {
                return ["status"=>false,"title"=>"Error","message"=>"El monto debe ser mayor que cero"];
            }

            if ($saldoPendiente > 0 && ($importeReal - $saldoPendiente > $EPS)) {
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
                $referencia1 = $get('puntos_uso', 'puntos_usar');               // Puntos usados
                $referencia2 = $get('equivalente_puntos');                      // Equivalente en moneda
                $referencia3 = 'Programa de puntos';
                break;

            // efectivo/no aplica -> referencias vacías
        }

        return [
            'multiple_pago'      => ($tipo_factura_post == 2 ? 1 : 0),
            'facturas_id'        => $facturas_id,
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
            'tarjeta'            => ($tipoPago === 'tarjeta') ? $importeReal : 0,               // normalizado
            'banco_id'           => $banco_id,
            'referencia_pago1'   => $referencia1,
            'referencia_pago2'   => $referencia2,
            'referencia_pago3'   => $referencia3,
            'clientes_id'        => isset($factura['clientes_id']) ? $factura['clientes_id'] : 0,
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
        $datos['tarjeta']      = 0; // asegurar índice

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
            "closeAllModals"=>true,
            "convertida_a_factura"=>$result['convertida_a_factura'] ?? false,
            "numero_factura"=>$result['numero_factura'] ?? 0,
            "factura_formateada"=>$result['factura_formateada'] ?? ""
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
        $datos['tarjeta'] = $datos['importe'];

        // Descripciones para pagos_detalles (se refuerzan desde el modelo igualmente)
        $datos['referencia_pago1'] = isset($_POST['cr_bill']) ? $_POST['cr_bill'] : ($datos['referencia_pago1'] ?? '');
        $datos['referencia_pago2'] = isset($_POST['exp']) ? $_POST['exp'] : ($datos['referencia_pago2'] ?? '');
        $datos['referencia_pago3'] = isset($_POST['cvcpwd']) ? $_POST['cvcpwd'] : ($datos['referencia_pago3'] ?? '');

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
            "closeAllModals"=> true,
            "convertida_a_factura"=>$result['convertida_a_factura'] ?? false,
            "numero_factura"=>$result['numero_factura'] ?? 0,
            "factura_formateada"=>$result['factura_formateada'] ?? ""
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
        $datos['tarjeta']      = 0;

        // refuerza referencias si vienen con otro nombre
        $datos['referencia_pago1'] = isset($_POST['ben_nm']) ? $_POST['ben_nm'] : ($datos['referencia_pago1'] ?? '');
        $datos['referencia_pago2'] = $datos['referencia_pago2'] ?? '';
        $datos['referencia_pago3'] = $datos['referencia_pago3'] ?? '';

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
            "closeAllModals"=> true,
            "convertida_a_factura"=>$result['convertida_a_factura'] ?? false,
            "numero_factura"=>$result['numero_factura'] ?? 0,
            "factura_formateada"=>$result['factura_formateada'] ?? ""
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
        $datos['tarjeta']      = 0;

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
            "closeAllModals"=> true,
            "convertida_a_factura"=>$result['convertida_a_factura'] ?? false,
            "numero_factura"=>$result['numero_factura'] ?? 0,
            "factura_formateada"=>$result['factura_formateada'] ?? ""
        ]);
    }

    /**
     * Sanea montos de forma segura.
     * Corrige el caso crítico: "L. 92.00" antes se convertía en 0.92
     * porque quedaba como ".92.00" al quitar solo la letra L.
     */
    private function parseMonto($str){
        $s = trim((string)$str);

        if ($s === '') {
            return 0.0;
        }

        $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');
        $s = strip_tags($s);
        $s = str_replace(["\xc2\xa0", ' '], '', $s);

        // Quitar moneda antes de limpiar símbolos para no dejar el punto de "L."
        $s = preg_replace('/\bHNL\b/i', '', $s);
        $s = preg_replace('/\bLPS\.?\b/i', '', $s);
        $s = preg_replace('/\bL\.\s*/i', '', $s);
        $s = preg_replace('/\bL\s*/i', '', $s);
        $s = str_replace(['₡', '$'], '', $s);

        // Si viene formato decimal con coma y sin punto: 92,50 => 92.50
        if (strpos($s, ',') !== false && strpos($s, '.') === false) {
            $s = str_replace(',', '.', $s);
        } else {
            // Formato normal: 1,500.00 => 1500.00
            $s = str_replace(',', '', $s);
        }

        // Dejar solo números, punto y signo negativo
        $s = preg_replace('/[^0-9.\-]/', '', $s);

        if ($s === '' || $s === '-' || $s === '.') {
            return 0.0;
        }

        // Si por cualquier razón quedaron varios puntos, conservar el último como decimal.
        if (substr_count($s, '.') > 1) {
            $negativo = (strpos($s, '-') === 0);
            $s = str_replace('-', '', $s);
            $partes = explode('.', $s);
            $decimal = array_pop($partes);
            $entero = implode('', $partes);
            $s = ($negativo ? '-' : '') . $entero . '.' . $decimal;
        }

        return round((float)$s + 1e-9, 2);
    }

    private function json($arr){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr);
        exit;
    }    
}