<?php
if($peticionAjax){
    require_once "../modelos/aperturaCajaModelo.php";
}else{
    require_once "./modelos/aperturaCajaModelo.php";
}

class aperturaCajaControlador extends aperturaCajaModelo{

    private function iniciarSesionSegura() {
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        return null;
    }

    private function registrarHistorial($accion, $observacion) {
        $datos = [
            "modulo" => 'Caja',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => $accion,
            "observacion" => $observacion,
            "fecha_registro" => date("Y-m-d H:i:s")
        ];

        mainModel::guardarHistorial($datos);
    }

    private function validarConfigApertura() {
        $config = $this->valid_config_apertura_modelo("Validar Apertura Caja")->fetch_assoc();
        return $config['validar'] == 0;
    }

    private function cajaAbierta($datos) {
        $result = $this->valid_apertura_caja_modelo($datos);
        return $result && $result->num_rows > 0;
    }

    public function agregar_apertura_caja_controlador(){
        $sesion = $this->iniciarSesionSegura();

        if($sesion !== null){
            return $sesion;
        }

        if(empty($_POST['colaboradores_id_apertura'])){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "Todos los campos son obligatorios",
                "type" => "error"
            ]);
        }

        $datos = [
            "colaboradores_id" => $_POST['colaboradores_id_apertura'],
            "fecha" => $_POST['fecha_apertura'],
            "factura_inicial" => "",
            "factura_final" => "",
            "monto" => !empty($_POST['monto_apertura']) ? $_POST['monto_apertura'] : 0,
            "neto" => 0,
            "estado" => 1,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "empresa_id_sd" => $_SESSION['empresa_id_sd'],
        ];

        if($this->validarConfigApertura() || !$this->cajaAbierta($datos)){
            $apertura_ok = $this->agregar_apertura_caja_modelo($datos);

            if($apertura_ok){
                $this->registrarHistorial("Apertura", "Se aperturó la caja");

                return mainModel::showNotification([
                    "title" => "Caja aperturada",
                    "text" => "La caja se ha aperturado correctamente",
                    "type" => "success",
                    "form" => "formAperturaCaja",
                    "funcion" => "validarAperturaCajaUsuario();getCajero();listar_registro_cajas();",
                    "closeAllModals" => true
                ]);
            }

            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo aperturar la caja",
                "type" => "error"
            ]);
        }

        return mainModel::showNotification([
            "title" => "Caja abierta",
            "text" => "La caja ya se encuentra abierta. <a href='".htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8')."cajas/' class='alert-link'>Ir a Ventas > Caja</a>",
            "type" => "error",
            "allow_html" => true
        ]);
    }

    public function cerrar_caja_controlador(){
        $sesion = $this->iniciarSesionSegura();

        if($sesion !== null){
            return $sesion;
        }

        $colaboradores_id_apertura = isset($_POST['colaboradores_id_apertura']) ? $_POST['colaboradores_id_apertura'] : 0;
        $fecha_apertura = isset($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : date('Y-m-d');

        $datos_apertura = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha" => $fecha_apertura
        ];

        $result = $this->valid_apertura_caja_modelo($datos_apertura);

        if(!$result || $result->num_rows == 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "La caja no se encuentra abierta",
                "type" => "error"
            ]);
        }

        $consultaApertura = $result->fetch_assoc();
        $apertura_id = (int)$consultaApertura['apertura_id'];

        if($this->validarCierreContabilizado($apertura_id)){
            return mainModel::showNotification([
                "title" => "Caja ya contabilizada",
                "text" => "Esta caja ya tiene ingresos registrados por cierre. No se puede cerrar nuevamente.",
                "type" => "error"
            ]);
        }

        $factura_inicial = $this->obtenerFactura($apertura_id, 'inicial');
        $factura_final = $this->obtenerFactura($apertura_id, 'final');

        $totales = $this->calcularTotalesCaja($apertura_id);
        $total_retiros = $this->obtenerTotalRetirosCaja($apertura_id);

        $neto_caja = (float)$totales['total_despues_isv'] - (float)$total_retiros;

        if($neto_caja < 0){
            $neto_caja = 0;
        }

        $datos_cierre = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha" => $fecha_apertura,
            "factura_inicial" => $factura_inicial,
            "factura_final" => $factura_final,
            "monto" => 0,
            "neto" => $neto_caja,
            "estado" => 2,
            "fecha_registro" => date("Y-m-d H:i:s"),
        ];

        $query = $this->cerrar_caja_modelo($datos_cierre);

        if(!$query){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo cerrar la caja",
                "type" => "error"
            ]);
        }

        $this->registrarMovimientosContables($apertura_id);

        $this->registrarHistorial(
            "Cierre",
            "Se cerró la caja. Venta: ".$totales['total_despues_isv']." | Retiros: ".$total_retiros." | Neto caja: ".$neto_caja
        );

        return mainModel::showNotification([
            "title" => "Cierre exitoso",
            "text" => "La caja se ha cerrado correctamente",
            "type" => "success",
            "funcion" => "validarAperturaCajaUsuario();getCajero();printComprobanteCajas($apertura_id);listar_registro_cajas();",
            "form" => "formAperturaCaja",
            "closeAllModals" => true
        ]);
    }

    private function obtenerFactura($apertura_id, $tipo) {
        if($tipo == 'inicial'){
            $result = $this->consultar_factura_inicial($apertura_id);
        }else{
            $result = $this->consultar_factura_final($apertura_id);
        }

        if($result && $result->num_rows > 0){
            $consulta = $result->fetch_assoc();
            return $consulta['prefijo']."".str_pad($consulta['numero'], $consulta['relleno'], "0", STR_PAD_LEFT);
        }

        return "";
    }

    private function calcularTotalesCaja($apertura_id) {
        $result = $this->consulta_facturas_electronicas_con_pagos($apertura_id);

        $totales = [
            'total' => 0,
            'descuentos' => 0,
            'isv_neto' => 0,
            'importe_gravado' => 0,
            'importe_excento' => 0,
            'subtotal' => 0,
            'total_despues_isv' => 0
        ];

        if($result){
            while($data = $result->fetch_assoc()){
                $detalles = $this->consulta_detalles_facturas($data['facturas_id']);

                if($detalles){
                    while($detalle = $detalles->fetch_assoc()){
                        $totales['total'] += ($detalle["precio"] * $detalle["cantidad"]);
                        $totales['descuentos'] += $detalle["descuento"];
                        $totales['isv_neto'] += $detalle["isv_valor"];

                        if($detalle["isv_valor"] > 0){
                            $totales['importe_gravado'] += ($detalle["precio"] * $detalle["cantidad"]);
                        }else{
                            $totales['importe_excento'] += ($detalle["precio"] * $detalle["cantidad"]);
                        }
                    }
                }
            }
        }

        $totales['subtotal'] = $totales['importe_gravado'] + $totales['importe_excento'];
        $totales['total_despues_isv'] = ($totales['total'] + $totales['isv_neto']) - $totales['descuentos'];

        return $totales;
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

    private function obtenerCategoriaGastoCierreCaja(){
        $query = "
            SELECT categoria_gastos_id
            FROM categoria_gastos
            WHERE estado = 1
              AND (
                    nombre LIKE '%Otros%'
                 OR nombre LIKE '%Caja%'
                 OR nombre LIKE '%Cierre%'
              )
            ORDER BY 
                CASE 
                    WHEN nombre LIKE '%Caja%' THEN 1
                    WHEN nombre LIKE '%Cierre%' THEN 2
                    WHEN nombre LIKE '%Otros%' THEN 3
                    ELSE 4
                END
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return (int)$row['categoria_gastos_id'];
        }

        return 14;
    }

    private function validarCierreContabilizado($apertura_id){
        $factura = "AP-".$apertura_id;

        $query = "
            SELECT ingresos_id
            FROM ingresos
            WHERE factura = '$factura'
              AND tipo_ingreso = 1
              AND estado = 1
            LIMIT 1
        ";

        $sql = mainModel::connection()->query($query);

        if(!$sql){
            die(mainModel::connection()->error);
        }

        return $sql->num_rows > 0;
    }

    private function registrarEgresoCierreCaja($apertura_id, $cuentas_id, $monto, $fecha, $fecha_registro, $empresa_id, $colaboradores_id){
        if($monto <= 0){
            return 0;
        }

        $egresos_id = mainModel::correlativo("egresos_id", "egresos");
        $proveedores_id = 1;
        $tipo_egreso = 2;
        $categoria_gastos_id = $this->obtenerCategoriaGastoCierreCaja();
        $factura = "CC-".$apertura_id."-".$egresos_id;
        $observacion = "Cierre de caja - neto entregado de caja";

        $insert = "
            INSERT INTO egresos (
                egresos_id,
                cuentas_id,
                proveedores_id,
                empresa_id,
                tipo_egreso,
                fecha,
                factura,
                factura_pdf,
                subtotal,
                descuento,
                nc,
                impuesto,
                total,
                observacion,
                estado,
                colaboradores_id,
                fecha_registro,
                categoria_gastos_id
            ) VALUES (
                '$egresos_id',
                '$cuentas_id',
                '$proveedores_id',
                '$empresa_id',
                '$tipo_egreso',
                '$fecha',
                '$factura',
                NULL,
                '$monto',
                '0',
                '0',
                '0',
                '$monto',
                '$observacion',
                '1',
                '$colaboradores_id',
                '$fecha_registro',
                '$categoria_gastos_id'
            )
        ";

        $ok = mainModel::connection()->query($insert);

        if(!$ok){
            die(mainModel::connection()->error);
        }

        return $egresos_id;
    }

    private function registrarMovimientosContables($apertura_id) {
        $cn = mainModel::connection();
        $cn->begin_transaction();

        try{
            $fecha = date('Y-m-d');
            $fecha_registro = date('Y-m-d H:i:s');
            $empresa_id = $_SESSION['empresa_id_sd'];
            $colaboradores_id = $_SESSION['colaborador_id_sd'];
            $tipo_ingreso = 1;

            $montos = $this->getMontosNoContabilizadosPorCuenta($apertura_id);
            $total_retiros = $this->obtenerTotalRetirosCaja($apertura_id);
            $cuenta_efectivo = $this->obtenerCuentaEfectivoCaja();

            $porcentaje_isv = 0.0;
            $rowISV = mainModel::getISV("Facturas");

            if($rowISV && $rowISV->num_rows > 0){
                $porcentaje_isv = (float)$rowISV->fetch_assoc()['valor'];
            }

            $recibide = '';
            $rowCli = $this->getNombreClienteModelo(1);

            if($rowCli && $rowCli->num_rows > 0){
                $rec = $rowCli->fetch_assoc();
                $recibide = isset($rec['nombre']) ? $rec['nombre'] : '';
            }

            if($montos){
                while($monto = $montos->fetch_assoc()){
                    $cuentas_id = (int)$monto['cuentas_id'];
                    $total_original = (float)$monto['monto'];
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

                    $datos_ingreso = [
                        "clientes_id" => 2,
                        "cuentas_id" => $cuentas_id,
                        "empresa_id" => $empresa_id,
                        "fecha" => $fecha,
                        "factura" => "AP-".$apertura_id,
                        "subtotal" => $subtotal,
                        "isv" => $isv_neto,
                        "descuento" => 0,
                        "nc" => 0,
                        "total" => $total,
                        "observacion" => "Ingresos por venta Cierre de Caja",
                        "estado" => 1,
                        "fecha_registro" => $fecha_registro,
                        "colaboradores_id" => $colaboradores_id,
                        "tipo_ingreso" => $tipo_ingreso,
                        "recibide" => $recibide
                    ];

                    $ingreso_id = $this->agregar_ingresos_contabilidad_modelo($datos_ingreso);

                    $this->marcar_pagos_contabilizados_por_cuenta($apertura_id, $cuentas_id, $ingreso_id);

                    $saldo_actual = 0;
                    $rsSaldo = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);

                    if($rsSaldo && $rsSaldo->num_rows > 0){
                        $filaS = $rsSaldo->fetch_assoc();
                        $saldo_actual = isset($filaS['saldo']) ? (float)$filaS['saldo'] : 0;
                    }

                    $nuevo_saldo = $saldo_actual + $total;

                    $datos_movimiento = [
                        "cuentas_id" => $cuentas_id,
                        "empresa_id" => $empresa_id,
                        "fecha" => $fecha,
                        "ingreso" => $total,
                        "egreso" => 0,
                        "saldo" => $nuevo_saldo,
                        "colaboradores_id" => $colaboradores_id,
                        "fecha_registro" => $fecha_registro,
                    ];

                    $this->agregar_movimientos_contabilidad_modelo($datos_movimiento);

                    if($cuentas_id == $cuenta_efectivo){
                        $this->registrarEgresoCierreCaja(
                            $apertura_id,
                            $cuentas_id,
                            $total,
                            $fecha,
                            $fecha_registro,
                            $empresa_id,
                            $colaboradores_id
                        );
                    }
                }
            }

            $cn->commit();
        }catch(Throwable $e){
            $cn->rollback();
            throw $e;
        }
    }
}