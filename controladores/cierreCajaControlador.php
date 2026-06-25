<?php
//cierreCajaControlador.php
if($peticionAjax){
    require_once "../modelos/aperturaCajaModelo.php";
}else{
    require_once "./modelos/aperturaCajaModelo.php";
}

class cierreCajaControlador extends aperturaCajaModelo{

    public function cerrar_caja_controlador(){
        if(!isset($_SESSION)) {
            session_start(['name'=>'SD']);
        }

        $colaboradores_id_apertura = isset($_POST['colaboradores_id_apertura']) ? (int)$_POST['colaboradores_id_apertura'] : 0;
        $fecha_apertura = isset($_POST['fecha_apertura']) ? $_POST['fecha_apertura'] : date('Y-m-d');
        $fecha_registro = date("Y-m-d H:i:s");

        if($colaboradores_id_apertura <= 0){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Error al cerrar la caja",
                "text"  => "No se recibió el colaborador de la caja."
            ]);
        }

        $datos_apertura = [
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha" => $fecha_apertura,
            "empresa_id" => isset($_SESSION['empresa_id_sd']) ? $_SESSION['empresa_id_sd'] : 0
        ];

        $res = $this->valid_apertura_caja_modelo($datos_apertura);

        if(!$res || $res->num_rows == 0){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Error al cerrar la caja",
                "text"  => "Lo sentimos, la caja no se encuentra abierta."
            ]);
        }

        $apRow = $res->fetch_assoc();
        $apertura_id = (int)$apRow['apertura_id'];

        if($this->validar_cierre_contabilizado_modelo($apertura_id)){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Caja ya contabilizada",
                "text"  => "Esta caja ya tiene ingresos registrados por cierre. No se puede cerrar nuevamente."
            ]);
        }

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
            - El retiro NO se contabiliza al momento de retirarlo.
            - El retiro queda en caja_retiros.
            - En el cierre se registra:
                1. ingreso por ventas completas
                2. egreso por retiros activos pendientes
                3. inversión automática si está configurada
                4. movimientos de todas las cuentas
        */
        $neto_caja = $total_vendido - $total_retiros - $total_inversion_automatica;

        if($neto_caja < 0){
            $neto_caja = 0;
        }

        $datos = [
            "apertura_id"      => $apertura_id,
            "colaboradores_id" => $colaboradores_id_apertura,
            "fecha"            => $fecha_apertura,
            "factura_inicial"  => $factura_inicial,
            "factura_final"    => $factura_final,
            "monto"            => 0,
            "neto"             => $neto_caja,
            "estado"           => 2,
            "fecha_registro"   => $fecha_registro
        ];

        try{
            $this->registrar_movimientos_contables_cierre_modelo($apertura_id);
        }catch(Throwable $e){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Error contable",
                "text"  => "No se pudo registrar la contabilidad del cierre: ".$e->getMessage()
            ]);
        }

        $ok = $this->cerrar_caja_modelo($datos);

        if(!$ok){
            return mainModel::showNotification([
                "type"  => "error",
                "title" => "Ocurrió un error inesperado",
                "text"  => "La contabilidad fue registrada, pero no hemos podido cerrar la caja."
            ]);
        }

        return mainModel::showNotification([
            "type"          => "success",
            "title"         => "Cierre de caja",
            "text"          => "La caja se ha cerrado correctamente. Factura normal: L. ".number_format($total_factura_normal, 2)." (".$cantidad_factura_normal.") | Proforma: L. ".number_format($total_proforma, 2)." (".$cantidad_proforma.") | ISV: L. ".number_format($total_isv, 2)." | Retiros: L. ".number_format($total_retiros, 2)." | Inversión/reposición: L. ".number_format($total_inversion_automatica, 2)." | Neto físico: L. ".number_format($neto_caja, 2),
            "form"          => "formColaboradores",
            "funcion"       => "validarAperturaCajaUsuario();getCajero();printComprobanteCajas($apertura_id);listar_registro_cajas();",
            "closeAllModals"=> true
        ]);
    }
}