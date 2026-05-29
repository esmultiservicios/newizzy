<?php
if($peticionAjax){
    require_once "../modelos/aperturaCajaModelo.php";
}else{
    require_once "./modelos/aperturaCajaModelo.php";
}

class cierreCajaControlador extends aperturaCajaModelo{

    public function cerrar_caja_controlador(){
        if(!isset($_SESSION)) session_start(['name'=>'SD']);

        $colaboradores_id_apertura = isset($_POST['colaboradores_id_apertura']) ? $_POST['colaboradores_id_apertura'] : 0;    
        $fecha_apertura            = isset($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : date('Y-m-d');
        $fecha                     = date("Y-m-d");
        $fecha_registro            = date("Y-m-d H:i:s");

        $datos_apertura = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha"            => $fecha_apertura
        ];

        $res = $this->valid_apertura_caja_modelo($datos_apertura);

        if(!$res || $res->num_rows == 0){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Error al cerrar la caja",
                "text"  => "Lo sentimos, la caja no se encuentra abierta"
            ]);
        }

        $apRow = $res->fetch_assoc();
        $apertura_id = (int)$apRow['apertura_id'];

        $factura_inicial = "";
        $r1 = $this->consultar_factura_inicial($apertura_id);

        if($r1 && $r1->num_rows > 0){
            $c = $r1->fetch_assoc();
            $factura_inicial = $c['prefijo'].str_pad($c['numero'], $c['relleno'], "0", STR_PAD_LEFT);
        }

        $factura_final = "";
        $r2 = $this->consultar_factura_final($apertura_id);

        if($r2 && $r2->num_rows > 0){
            $c = $r2->fetch_assoc();
            $factura_final = $c['prefijo'].str_pad($c['numero'], $c['relleno'], "0", STR_PAD_LEFT);
        }

        $totales = $this->calcularTotalesCaja($apertura_id);
        $total_retiros = $this->obtenerTotalRetirosCaja($apertura_id);

        $neto_caja = (float)$totales['total_despues_isv'] - (float)$total_retiros;

        if($neto_caja < 0){
            $neto_caja = 0;
        }

        $datos = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha"            => $fecha_apertura,
            "factura_inicial"  => $factura_inicial,
            "factura_final"    => $factura_final,
            "monto"            => 0,
            "neto"             => $neto_caja,
            "estado"           => 2,
            "fecha_registro"   => $fecha_registro
        ];

        $ok = $this->cerrar_caja_modelo($datos);

        if(!$ok){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Ocurrió un error inesperado",
                "text"  => "No hemos podido procesar su solicitud"
            ]);
        }

        $this->registrarMovimientosContables($apertura_id);

        return mainModel::showNotification([
            "type"          => "success",
            "title"         => "Cierre de caja",
            "text"          => "La caja se ha cerrado correctamente",
            "form"          => "formColaboradores",
            "funcion"       => "validarAperturaCajaUsuario();getCajero();printComprobanteCajas($apertura_id);listar_registro_cajas();",
            "closeAllModals"=> true
        ]);
    }

    private function obtenerTotalRetirosCaja($apertura_id){
        $empresa_id = isset($_SESSION['empresa_id_sd']) ? (int)$_SESSION['empresa_id_sd'] : 0;

        $query = "
            SELECT COALESCE(SUM(monto), 0) AS total_retiros
            FROM caja_retiros
            WHERE apertura_id = '$apertura_id'
              AND empresa_id = '$empresa_id'
              AND estado = 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (float)$row['total_retiros'];
        }

        return 0;
    }

    private function obtenerCuentaEfectivoCaja(){
        $query = "
            SELECT cuentas_id
            FROM tipo_pago
            WHERE tipo_pago_id = 1
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (int)$row['cuentas_id'];
        }

        return 0;
    }

    private function calcularTotalesCaja($apertura_id){
        $res = $this->consulta_facturas_electronicas_con_pagos($apertura_id);

        $t = [
            'total'             => 0,
            'descuentos'        => 0,
            'isv_neto'          => 0,
            'importe_gravado'   => 0,
            'importe_excento'   => 0,
            'subtotal'          => 0,
            'total_despues_isv' => 0
        ];

        if($res && $res->num_rows > 0){
            while($f = $res->fetch_assoc()){
                $d = $this->consulta_detalles_facturas($f['facturas_id']);

                if($d){
                    while($r = $d->fetch_assoc()){
                        $t['total']      += ($r["precio"] * $r["cantidad"]);
                        $t['descuentos'] += $r["descuento"];
                        $t['isv_neto']   += $r["isv_valor"];

                        if($r["isv_valor"] > 0){
                            $t['importe_gravado'] += ($r["precio"] * $r["cantidad"]);
                        }else{
                            $t['importe_excento'] += ($r["precio"] * $r["cantidad"]);
                        }
                    }
                }
            }
        }

        $t['subtotal'] = $t['importe_gravado'] + $t['importe_excento'];
        $t['total_despues_isv'] = ($t['total'] + $t['isv_neto']) - $t['descuentos'];

        return $t;
    }

    private function registrarMovimientosContables($apertura_id){
        $cn = mainModel::connection();
        $cn->begin_transaction();

        try{
            $fecha            = date('Y-m-d');
            $fecha_registro   = date('Y-m-d H:i:s');
            $empresa_id       = $_SESSION['empresa_id_sd'];
            $colaboradores_id = $_SESSION['colaborador_id_sd'];
            $tipo_ingreso     = 1;

            $montos = $this->getMontosNoContabilizadosPorCuenta($apertura_id);
            $total_retiros = $this->obtenerTotalRetirosCaja($apertura_id);
            $cuenta_efectivo = $this->obtenerCuentaEfectivoCaja();

            $porcentaje_isv = 0.0;
            $porcRow = mainModel::getISV("Facturas");

            if($porcRow && $porcRow->num_rows > 0){
                $porcentaje_isv = (float)$porcRow->fetch_assoc()['valor'];
            }

            $recibide = '';
            $cliRow = $this->getNombreClienteModelo(1);

            if($cliRow && $cliRow->num_rows > 0){
                $c = $cliRow->fetch_assoc();
                $recibide = $c['nombre'] ?? '';
            }

            if($montos){
                while($m = $montos->fetch_assoc()){
                    $cuentas_id = (int)$m['cuentas_id'];
                    $total_original = (float)$m['monto'];
                    $total = $total_original;

                    if($cuentas_id == $cuenta_efectivo){
                        $total = $total_original - $total_retiros;

                        if($total < 0){
                            $total = 0;
                        }
                    }

                    if($total <= 0){
                        $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, 0);
                        continue;
                    }

                    $subtotal = ($porcentaje_isv > 0) ? ($total / (1 + ($porcentaje_isv/100))) : $total;
                    $isv_neto = $total - $subtotal;

                    $facturaRef = "AP-".$apertura_id;

                    $datos_ing = [
                        "clientes_id"      => 2,
                        "cuentas_id"       => $cuentas_id,
                        "empresa_id"       => $empresa_id,
                        "fecha"            => $fecha,
                        "factura"          => $facturaRef,
                        "subtotal"         => $subtotal,
                        "isv"              => $isv_neto,
                        "descuento"        => 0,
                        "nc"               => 0,
                        "total"            => $total,
                        "observacion"      => "Ingresos por venta Cierre de Caja",
                        "estado"           => 1,
                        "fecha_registro"   => $fecha_registro,
                        "colaboradores_id" => $colaboradores_id,
                        "tipo_ingreso"     => $tipo_ingreso,
                        "recibide"         => $recibide
                    ];

                    $ingresos_id = $this->agregar_ingresos_contabilidad_modelo($datos_ing);

                    $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingresos_id);

                    $saldo_actual = 0.0;
                    $saldoRes = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);

                    if($saldoRes && $saldoRes->num_rows > 0){
                        $s = $saldoRes->fetch_assoc();
                        $saldo_actual = (float)($s['saldo'] ?? 0);
                    }

                    $nuevo_saldo = $saldo_actual + $total;

                    $mov = [
                        "cuentas_id"       => $cuentas_id,
                        "empresa_id"       => $empresa_id,
                        "fecha"            => $fecha,
                        "ingreso"          => $total,
                        "egreso"           => 0,
                        "saldo"            => $nuevo_saldo,
                        "colaboradores_id" => $colaboradores_id,
                        "fecha_registro"   => $fecha_registro
                    ];

                    $this->agregar_movimientos_contabilidad_modelo($mov);
                }
            }

            $cn->commit();

        }catch(Throwable $e){
            $cn->rollback();
            throw $e;
        }
    }
}