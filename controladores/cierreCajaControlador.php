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

        // Verificar caja abierta
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

        // Factura inicial
        $factura_inicial = "";
        $r1 = $this->consultar_factura_inicial($apertura_id);
        if($r1 && $r1->num_rows > 0){
            $c = $r1->fetch_assoc();
            $factura_inicial = $c['prefijo'].str_pad($c['numero'], $c['relleno'], "0", STR_PAD_LEFT);
        }

        // Factura final
        $factura_final = "";
        $r2 = $this->consultar_factura_final($apertura_id);
        if($r2 && $r2->num_rows > 0){
            $c = $r2->fetch_assoc();
            $factura_final = $c['prefijo'].str_pad($c['numero'], $c['relleno'], "0", STR_PAD_LEFT);
        }

        // Totales para neto/impresión
        $totales = $this->calcularTotalesCaja($apertura_id);

        // Cerrar
        $datos = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha"            => $fecha_apertura,
            "factura_inicial"  => $factura_inicial,
            "factura_final"    => $factura_final,
            "monto"            => 0,
            "neto"             => $totales['total_despues_isv'],
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

        // Contabilizar pagos NO contabilizados de esta apertura
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

    private function calcularTotalesCaja($apertura_id){
        $res = $this->consulta_facturas_electronicas_con_pagos($apertura_id);
        $t = [
            'total'=>0,'descuentos'=>0,'isv_neto'=>0,
            'importe_gravado'=>0,'importe_excento'=>0,
            'subtotal'=>0,'total_despues_isv'=>0
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
            $fecha           = date('Y-m-d');
            $fecha_registro  = date('Y-m-d H:i:s');
            $empresa_id      = $_SESSION['empresa_id_sd'];
            $colaboradores_id= $_SESSION['colaborador_id_sd'];
            $tipo_ingreso    = 1; // Ingresos por ventas

            // Trae montos agrupados por cuenta (solo pagos no contabilizados de facturas electrónicas de esta apertura)
            $montos = $this->getMontosNoContabilizadosPorCuenta($apertura_id);

            // Porcentaje ISV
            $porcentaje_isv = 0.0;
            $porcRow = mainModel::getISV("Facturas");
            if($porcRow && $porcRow->num_rows > 0){
                $porcentaje_isv = (float)$porcRow->fetch_assoc()['valor'];
            }

            // Nombre recibido
            $recibide = '';
            $cliRow = $this->getNombreClienteModelo(1);
            if($cliRow && $cliRow->num_rows > 0){
                $c = $cliRow->fetch_assoc();
                $recibide = $c['nombre'] ?? '';
            }

            if($montos){
                while($m = $montos->fetch_assoc()){
                    $cuentas_id = (int)$m['cuentas_id'];
                    $total      = (float)$m['monto'];

                    $subtotal = ($porcentaje_isv > 0) ? ($total / (1 + ($porcentaje_isv/100))) : $total;
                    $isv_neto = $total - $subtotal;

                    $facturaRef = "AP-".$apertura_id;

                    $datos_ing = [
                        "clientes_id"     => 2,
                        "cuentas_id"      => $cuentas_id,
                        "empresa_id"      => $empresa_id,
                        "fecha"           => $fecha,
                        "factura"         => $facturaRef,
                        "subtotal"        => $subtotal,
                        "isv"             => $isv_neto,
                        "descuento"       => 0,
                        "nc"              => 0,
                        "total"           => $total,
                        "observacion"     => "Ingresos por venta Cierre de Caja",
                        "estado"          => 1,
                        "fecha_registro"  => $fecha_registro,
                        "colaboradores_id"=> $colaboradores_id,
                        "tipo_ingreso"    => $tipo_ingreso,
                        "recibide"        => $recibide
                    ];

                    $ingresos_id = $this->agregar_ingresos_contabilidad_modelo($datos_ing);

                    // Marcar pagos como contabilizados para esta cuenta
                    $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingresos_id);

                    // Movimiento: saldo previo
                    $saldo_actual = 0.0;
                    $saldoRes = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);
                    if($saldoRes && $saldoRes->num_rows > 0){
                        $s = $saldoRes->fetch_assoc();
                        $saldo_actual = (float)($s['saldo'] ?? 0);
                    }
                    $nuevo_saldo = $saldo_actual + $total;

                    $mov = [
                        "cuentas_id"      => $cuentas_id,
                        "empresa_id"      => $empresa_id,
                        "fecha"           => $fecha,
                        "ingreso"         => $total,
                        "egreso"          => 0,
                        "saldo"           => $nuevo_saldo,
                        "colaboradores_id"=> $colaboradores_id,
                        "fecha_registro"  => $fecha_registro
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