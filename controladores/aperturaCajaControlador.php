<?php
//aperturaCajaControlador.php
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
        $config = $this->valid_config_apertura_modelo("Validar Apertura Caja");

        if($config && $config->num_rows > 0){
            $row = $config->fetch_assoc();
            return $row['validar'] == 0;
        }

        return false;
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
                "text" => "Todos los campos son obligatorios.",
                "type" => "error"
            ]);
        }

        $colaboradores_id = (int)$_POST['colaboradores_id_apertura'];
        $fecha_apertura = isset($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : date('Y-m-d');
        $monto_apertura = !empty($_POST['monto_apertura']) ? (float)$_POST['monto_apertura'] : 0;

        if($monto_apertura < 0){
            $monto_apertura = 0;
        }

        $datos = [
            "colaboradores_id" => $colaboradores_id,
            "fecha" => $fecha_apertura,
            "factura_inicial" => "",
            "factura_final" => "",
            "monto" => $monto_apertura,
            "neto" => 0,
            "estado" => 1,
            "fecha_registro" => date("Y-m-d H:i:s"),
            "empresa_id_sd" => $_SESSION['empresa_id_sd'],
            "empresa_id" => $_SESSION['empresa_id_sd']
        ];

        if($this->validarConfigApertura() || !$this->cajaAbierta($datos)){
            $apertura_ok = $this->agregar_apertura_caja_modelo($datos);

            if($apertura_ok){
                $this->registrarHistorial("Apertura", "Se aperturó la caja.");

                return mainModel::showNotification([
                    "title" => "Caja aperturada",
                    "text" => "La caja se ha aperturado correctamente.",
                    "type" => "success",
                    "form" => "formAperturaCaja",
                    "funcion" => "validarAperturaCajaUsuario();getCajero();listar_registro_cajas();",
                    "closeAllModals" => true
                ]);
            }

            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo aperturar la caja.",
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

        $colaboradores_id_apertura = isset($_POST['colaboradores_id_apertura']) ? (int)$_POST['colaboradores_id_apertura'] : 0;
        $fecha_apertura = isset($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : date('Y-m-d');

        if($colaboradores_id_apertura <= 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se recibió el colaborador de la caja.",
                "type" => "error"
            ]);
        }

        $datos_apertura = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha" => $fecha_apertura,
            "empresa_id" => isset($_SESSION['empresa_id_sd']) ? $_SESSION['empresa_id_sd'] : 0
        ];

        $result = $this->valid_apertura_caja_modelo($datos_apertura);

        if(!$result || $result->num_rows == 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "La caja no se encuentra abierta.",
                "type" => "error"
            ]);
        }

        $consultaApertura = $result->fetch_assoc();
        $apertura_id = (int)$consultaApertura['apertura_id'];

        if($this->validar_cierre_contabilizado_modelo($apertura_id)){
            return mainModel::showNotification([
                "title" => "Caja ya contabilizada",
                "text" => "Esta caja ya tiene ingresos registrados por cierre. No se puede cerrar nuevamente.",
                "type" => "error"
            ]);
        }

        $factura_inicial = $this->obtenerFactura($apertura_id, 'inicial');
        $factura_final = $this->obtenerFactura($apertura_id, 'final');

        $total_vendido = $this->obtener_total_ventas_caja_modelo($apertura_id);
        $total_retiros = $this->obtener_total_retiros_caja_modelo($apertura_id);
        $total_inversion_automatica = $this->obtener_monto_inversion_automatico_cierre_modelo($apertura_id);
        $resumen_cierre = $this->obtener_resumen_ventas_cierre_caja_modelo($apertura_id);

        $total_factura_normal = isset($resumen_cierre['total_factura_normal']) ? (float)$resumen_cierre['total_factura_normal'] : 0;
        $total_proforma = isset($resumen_cierre['total_proforma']) ? (float)$resumen_cierre['total_proforma'] : 0;
        $total_isv = isset($resumen_cierre['total_isv']) ? (float)$resumen_cierre['total_isv'] : 0;
        $cantidad_factura_normal = isset($resumen_cierre['cantidad_factura_normal']) ? (int)$resumen_cierre['cantidad_factura_normal'] : 0;
        $cantidad_proforma = isset($resumen_cierre['cantidad_proforma']) ? (int)$resumen_cierre['cantidad_proforma'] : 0;

        /*
            LÓGICA FINAL:
            - El retiro solo queda en caja_retiros mientras la caja está abierta.
            - En el cierre se registra ingreso completo y egreso por retiros pendientes.
            - Si existe categoría y cuenta de inversión, se aparta automáticamente el costo de productos vendidos.
        */
        $neto_caja = $total_vendido - $total_retiros - $total_inversion_automatica;

        if($neto_caja < 0){
            $neto_caja = 0;
        }

        $datos_cierre = [
            "apertura_id" => $apertura_id,
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha" => $fecha_apertura,
            "factura_inicial" => $factura_inicial,
            "factura_final" => $factura_final,
            "monto" => 0,
            "neto" => $neto_caja,
            "estado" => 2,
            "fecha_registro" => date("Y-m-d H:i:s")
        ];

        try{
            $this->registrar_movimientos_contables_cierre_modelo($apertura_id);
        }catch(Throwable $e){
            return mainModel::showNotification([
                "title" => "Error contable",
                "text" => "No se pudo registrar la contabilidad del cierre: ".$e->getMessage(),
                "type" => "error"
            ]);
        }

        $query = $this->cerrar_caja_modelo($datos_cierre);

        if(!$query){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "La contabilidad fue registrada, pero no se pudo cerrar la caja.",
                "type" => "error"
            ]);
        }

        $this->registrarHistorial(
            "Cierre",
            "Se cerró la caja. Factura normal: ".$total_factura_normal." (".$cantidad_factura_normal.") | Proforma: ".$total_proforma." (".$cantidad_proforma.") | ISV: ".$total_isv." | Retiros: ".$total_retiros." | Neto físico: ".$neto_caja
        );

        return mainModel::showNotification([
            "title" => "Cierre exitoso",
            "text" => "La caja se ha cerrado correctamente. Factura normal: L. ".number_format($total_factura_normal, 2)." (".$cantidad_factura_normal.") | Proforma: L. ".number_format($total_proforma, 2)." (".$cantidad_proforma.") | ISV: L. ".number_format($total_isv, 2)." | Retiros: L. ".number_format($total_retiros, 2)." | Neto físico: L. ".number_format($neto_caja, 2),
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
}