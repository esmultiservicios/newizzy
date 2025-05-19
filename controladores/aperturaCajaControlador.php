<?php
if($peticionAjax){
    require_once "../modelos/aperturaCajaModelo.php";
}else{
    require_once "./modelos/aperturaCajaModelo.php";
}

class aperturaCajaControlador extends aperturaCajaModelo{
    // Método para manejar sesión de forma segura
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
    }

    // Registrar historial común
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

    // Validar configuración de apertura
    private function validarConfigApertura() {
        $config = aperturaCajaModelo::valid_config_apertura_modelo("Validar Apertura Caja")->fetch_assoc();
        return $config['validar'] == 0;
    }

    // Validar si la caja ya está abierta
    private function cajaAbierta($datos) {
        $result = aperturaCajaModelo::valid_apertura_caja_modelo($datos);
        return $result->num_rows > 0;
    }

    // Método para abrir caja
    public function agregar_apertura_caja_controlador(){
        $this->iniciarSesionSegura();

        $datos = [
            "colaboradores_id" => $_POST['colaboradores_id_apertura'],
            "fecha" => $_POST['fecha_apertura'],
            "factura_inicial" => "",
            "factura_final" => "",
            "monto" => $_POST['monto_apertura'],
            "neto" => 0,                
            "estado" => 1,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "empresa_id_sd" => $_SESSION['empresa_id_sd'],                
        ];

        // Validaciones
        if(empty($_POST['colaboradores_id_apertura']) || empty($_POST['monto_apertura'])){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "Todos los campos son obligatorios",
                "type" => "error"
            ]);
        }

        // Procesar apertura
        if($this->validarConfigApertura() || !$this->cajaAbierta($datos)){
            $query = aperturaCajaModelo::agregar_apertura_caja_modelo($datos);
            
            if($query){
                $this->registrarHistorial("Apertura", "Se aperturó la caja");
                return mainModel::showNotification([
                    "title" => "Caja aperturada",
                    "text" => "La caja se ha aperturado correctamente",
                    "type" => "success",
                    "form" => "formAperturaCaja",
                    "funcion" => "validarAperturaCajaUsuario();getCajero();",
					"closeAllModals" => true
                ]);
            } else {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo aperturar la caja",
                    "type" => "error"
                ]);
            }
        } else {
            return mainModel::showNotification([
                "title" => "Caja abierta",
                "text" => "La caja ya se encuentra abierta. <a href='".htmlspecialchars(SERVERURL, ENT_QUOTES, 'UTF-8')."cajas/' class='alert-link'>Ir a Ventas > Caja</a>",
                "type" => "error",
                "allow_html" => true
            ]);
        }
    }

    // Método para cerrar caja
    public function cerrar_caja_controlador(){
        $this->iniciarSesionSegura();

        $datos_apertura = [
            "colaboradores_id" => $_POST['colaboradores_id_apertura'],
            "fecha" => $_POST['fecha_apertura']                
        ];

        // Verificar si la caja está abierta
        $result = aperturaCajaModelo::valid_apertura_caja_modelo($datos_apertura);            
        if($result->num_rows == 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "La caja no se encuentra abierta",
                "type" => "error"
            ]);
        }

        $consultaApertura = $result->fetch_assoc();
        $apertura_id = $consultaApertura['apertura_id'];

        // Obtener facturas inicial y final
        $factura_inicial = $this->obtenerFactura($apertura_id, 'inicial');
        $factura_final = $this->obtenerFactura($apertura_id, 'final');

        // Calcular totales
        $totales = $this->calcularTotalesCaja($apertura_id);

        // Preparar datos para cerrar caja
        $datos_cierre = [
            "colaboradores_id" => $_POST['colaboradores_id_apertura'],
            "fecha" => $_POST['fecha_apertura'],
            "factura_inicial" => $factura_inicial,
            "factura_final" => $factura_final,
            "monto" => 0,
            "neto" => $totales['total_despues_isv'],                
            "estado" => 2,
            "fecha_registro" => date("Y-m-d H:i:s"),                
        ];

        // Cerrar caja
        $query = aperturaCajaModelo::cerrar_caja_modelo($datos_cierre);
        
        if(!$query){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo cerrar la caja",
                "type" => "error"
            ]);
        }

        // Registrar movimientos contables
        $this->registrarMovimientosContables($apertura_id, $totales);

        $this->registrarHistorial("Cierre", "Se cerró la caja");

        return mainModel::showNotification([
            "title" => "Cierre exitoso",
            "text" => "La caja se ha cerrado correctamente",
            "type" => "success",
            "funcion" => "validarAperturaCajaUsuario();getCajero();printComprobanteCajas($apertura_id);",
            "form" => "formAperturaCaja",
			"closeAllModals" => true
        ]);
    }

    // Métodos auxiliares
    private function obtenerFactura($apertura_id, $tipo) {
        $method = ($tipo == 'inicial') ? 'consultar_factura_inicial' : 'consultar_factura_final';
        $result = aperturaCajaModelo::$method($apertura_id);
        
        if($result->num_rows > 0){
            $consulta = $result->fetch_assoc();
            return $consulta['prefijo']."".str_pad($consulta['numero'], $consulta['relleno'], "0", STR_PAD_LEFT);
        }
        return "";
    }

    private function calcularTotalesCaja($apertura_id) {
        $result = aperturaCajaModelo::consulta_facturas_electronicas_con_pagos($apertura_id);
        
        $totales = [
            'total' => 0,
            'descuentos' => 0,
            'isv_neto' => 0,
            'importe_gravado' => 0,
            'importe_excento' => 0,
            'subtotal' => 0,
            'total_despues_isv' => 0
        ];

        while($data = $result->fetch_assoc()){
            $detalles = aperturaCajaModelo::consulta_detalles_facturas($data['facturas_id']);
            
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

        $totales['subtotal'] = $totales['importe_gravado'] + $totales['importe_excento'];
        $totales['total_despues_isv'] = ($totales['total'] + $totales['isv_neto']) - $totales['descuentos'];
        
        return $totales;
    }

    private function registrarMovimientosContables($apertura_id, $totales) {
        $fecha = date('Y-m-d');
        $fecha_registro = date('Y-m-d H:i:s');
        $empresa_id = $_SESSION['empresa_id_sd'];
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $tipo_ingreso = 1; // INGRESOS POR VENTAS

        // Obtener montos por tipo de pago
        $montos = mainModel::getMontoTipoPago($apertura_id);

        while($monto = $montos->fetch_assoc()){
            $cuentas_id = $monto['cuentas_id'];
            $total = $monto['monto'];
            
            // Calcular ISV
            $porcentaje_isv = mainModel::getISV("Facturas")->fetch_assoc()['valor'];
            $total_antes_isv = $total / ((($porcentaje_isv/100) + 1));
            $isv_neto = $total - $total_antes_isv;

            // Registrar ingreso
            $datos_ingreso = [
                "clientes_id" => 2,
                "cuentas_id" => $cuentas_id,
                "empresa_id" => $empresa_id,
                "fecha" => $fecha,
                "factura" => $apertura_id,
                "subtotal" => $total_antes_isv,
                "isv" => $isv_neto,
                "descuento" => 0,
                "nc" => 0,
                "total" => $total,
                "observacion" => "Ingresos por venta Cierre de Caja",
                "estado" => 1,
                "fecha_registro" => $fecha_registro,
                "colaboradores_id" => $colaboradores_id,
                "tipo_ingreso" => $tipo_ingreso                
            ];
            
            aperturaCajaModelo::agregar_ingresos_contabilidad_modelo($datos_ingreso);

            // Registrar movimiento
            $saldo_actual = aperturaCajaModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc()['saldo'];
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
            
            aperturaCajaModelo::agregar_movimientos_contabilidad_modelo($datos_movimiento);
        }
    }
}