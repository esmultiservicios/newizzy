<?php
if($peticionAjax){
    require_once "../modelos/ingresosContabilidadModelo.php";
}else{
    require_once "./modelos/ingresosContabilidadModelo.php";
}

class ingresosContabilidadControlador extends ingresosContabilidadModelo{

    public function agregar_ingresos_contabilidad_controlador(){
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        /*
            IMPORTANTE:
            En el modal el select se llama recibide_ingresos,
            pero el VALUE que manda es el ID del cliente.
            Por eso lo usamos como clientes_id.
        */
        $clientes_id = mainModel::cleanStringConverterCase(isset($_POST['recibide_ingresos']) ? $_POST['recibide_ingresos'] : "");

        $cuentas_id = mainModel::cleanStringConverterCase(isset($_POST['cuenta_ingresos']) ? $_POST['cuenta_ingresos'] : "");
        $empresa_id = $_SESSION['empresa_id_sd'];
        $fecha = isset($_POST['fecha_ingresos']) ? $_POST['fecha_ingresos'] : "";
        $factura = mainModel::cleanString(isset($_POST['factura_ingresos']) ? $_POST['factura_ingresos'] : "");
        $subtotal = mainModel::cleanStringConverterCase(isset($_POST['subtotal_ingresos']) && $_POST['subtotal_ingresos'] !== "" ? $_POST['subtotal_ingresos'] : 0);
        $isv = mainModel::cleanStringConverterCase(isset($_POST['isv_ingresos']) && $_POST['isv_ingresos'] !== "" ? $_POST['isv_ingresos'] : 0);
        $descuento = mainModel::cleanStringConverterCase(isset($_POST['descuento_ingresos']) && $_POST['descuento_ingresos'] !== "" ? $_POST['descuento_ingresos'] : 0);
        $nc = mainModel::cleanStringConverterCase(isset($_POST['nc_ingresos']) && $_POST['nc_ingresos'] !== "" ? $_POST['nc_ingresos'] : 0);
        $total = mainModel::cleanStringConverterCase(isset($_POST['total_ingresos']) && $_POST['total_ingresos'] !== "" ? $_POST['total_ingresos'] : 0);
        $observacion = mainModel::cleanString(isset($_POST['observacion_ingresos']) ? $_POST['observacion_ingresos'] : "");
        $estado = 1;
        $tipo_ingreso = 2; // OTROS INGRESOS
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");

        if($clientes_id === "" || !is_numeric($clientes_id)){
            return mainModel::showNotification([
                "title" => "Cliente requerido",
                "text" => "Debe seleccionar un cliente válido.",
                "type" => "error"
            ]);
        }

        if($cuentas_id === "" || !is_numeric($cuentas_id)){
            return mainModel::showNotification([
                "title" => "Cuenta requerida",
                "text" => "Debe seleccionar una cuenta contable válida.",
                "type" => "error"
            ]);
        }

        if($fecha === ""){
            return mainModel::showNotification([
                "title" => "Fecha requerida",
                "text" => "Debe seleccionar la fecha del ingreso.",
                "type" => "error"
            ]);
        }

        if((float)$total <= 0){
            return mainModel::showNotification([
                "title" => "Total inválido",
                "text" => "El total del ingreso debe ser mayor a cero.",
                "type" => "error"
            ]);
        }

        // Validamos que el cliente exista por ID
        $resultCliente = ingresosContabilidadModelo::valid_cliente_id_cuentas_contabilidad($clientes_id);

        if(!$resultCliente || $resultCliente->num_rows === 0){
            return mainModel::showNotification([
                "title" => "Cliente no encontrado",
                "text" => "No se encontró el cliente seleccionado.",
                "type" => "error"
            ]);
        }

        $cliente_consulta = $resultCliente->fetch_assoc();
        $clientes_id = $cliente_consulta['clientes_id'];

        // Validación de plan
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();

        if (isset($planConfig['ingresos'])) {
            $limiteIngresos = (int)$planConfig['ingresos'];

            if ($limiteIngresos === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan no incluye la creación de ingresos contables."
                ]);
            }

            $totalRegistradas = (int)ingresosContabilidadModelo::getTotalIngresosRegistrados();

            if ($totalRegistradas >= $limiteIngresos) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Ha excedido el límite mensual de ingresos contables (Máximo: $limiteIngresos)."
                ]);
            }
        }

        $datos_ingresos = [
            "ingresos_id" => $ingresos_id,
            "clientes_id" => $clientes_id,
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "factura" => $factura,
            "subtotal" => $subtotal,
            "isv" => $isv,
            "descuento" => $descuento,
            "nc" => $nc,
            "total" => $total,
            "observacion" => $observacion,
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,
            "colaboradores_id" => $colaboradores_id,
            "tipo_ingreso" => $tipo_ingreso
        ];

        if ($factura !== "") {
            $resultIngresos = ingresosContabilidadModelo::valid_ingreso_cuentas_modelo($datos_ingresos);

            if ($resultIngresos && $resultIngresos->num_rows > 0) {
                return mainModel::showNotification([
                    "title" => "Registro ya existe",
                    "text" => "Ya existe un ingreso con esta factura",
                    "type" => "error"
                ]);
            }
        }

        $query = ingresosContabilidadModelo::agregar_ingresos_contabilidad_modelo($datos_ingresos);

        if ($query) {
            $consulta_ingresos_contabilidad = ingresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);
            $saldo_consulta = 0;

            if($consulta_ingresos_contabilidad && $consulta_ingresos_contabilidad->num_rows > 0){
                $saldoData = $consulta_ingresos_contabilidad->fetch_assoc();
                $saldo_consulta = isset($saldoData['saldo']) && $saldoData['saldo'] !== "" ? (float)$saldoData['saldo'] : 0;
            }

            $ingreso = (float)$total;
            $egreso = 0;
            $saldo = $saldo_consulta + $ingreso;

            $datos_movimientos = [
                "cuentas_id" => $cuentas_id,
                "empresa_id" => $empresa_id,
                "fecha" => $fecha,
                "ingreso" => $ingreso,
                "egreso" => $egreso,
                "saldo" => $saldo,
                "colaboradores_id" => $colaboradores_id,
                "fecha_registro" => $fecha_registro,
            ];

            ingresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);

            mainModel::guardarHistorial([
                "modulo" => 'Ingresos Contabilidad',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status" => "Registro",
                "observacion" => "Se registró ingreso contable ID: {$ingresos_id} por {$total}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ]);

            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro almacenado",
                "text" => "El registro se ha almacenado correctamente",
                "form" => "formIngresosContables",
                "funcion" => "listar_ingresos_contabilidad();getClientesIngresos(); getCuentaIngresos(); getEmpresaIngresos();printIngresos(" . $ingresos_id . ");total_ingreso_footer();"
            ]);
        }

        return mainModel::showNotification([
            "title" => "Error",
            "text" => "No se pudo registrar el ingreso contable",
            "type" => "error"
        ]);
    }

    public function edit_ingresos_contabilidad_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $ingresos_id = isset($_POST['ingresos_id']) ? $_POST['ingresos_id'] : "";
        $clientes_id = isset($_POST['recibide_ingresos']) ? $_POST['recibide_ingresos'] : "";
        $factura = mainModel::cleanString(isset($_POST['factura_ingresos']) ? $_POST['factura_ingresos'] : "");
        $fecha = isset($_POST['fecha_ingresos']) ? $_POST['fecha_ingresos'] : "";
        $observacion = mainModel::cleanString(isset($_POST['observacion_ingresos']) ? $_POST['observacion_ingresos'] : "");

        $datos = [
            "ingresos_id" => $ingresos_id,
            "clientes_id" => $clientes_id,
            "factura" => $factura,
            "fecha" => $fecha,
            "observacion" => $observacion,
        ];

        $query = ingresosContabilidadModelo::edit_ingresos_contabilidad_modelo($datos);

        if($query){
            mainModel::guardarHistorial([
                "modulo" => 'Ingresos Contabilidad',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status" => "Edición",
                "observacion" => "Se editó ingreso contable ID: {$ingresos_id}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ]);

            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro editado",
                "text" => "Registro editado correctamente",
                "form" => "formIngresosContables",
                "funcion" => "listar_ingresos_contabilidad();getClientesIngresos(); getCuentaIngresos(); getEmpresaIngresos();printIngresos(".$ingresos_id.");total_ingreso_footer();",
                "modal" => "modalIngresosContables"
            ]);
        }else{
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo editar el ingreso contable",
                "type" => "error"
            ]);
        }
    }

    public function cancel_ingresos_contabilidad_controlador(){
        $validacion = mainModel::validarSesion();

        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $ingresos_id = isset($_POST['ingresos_id']) ? mainModel::cleanString($_POST['ingresos_id']) : "";
        $proveedores_id_ajus = isset($_POST['proveedor_anulacion_id']) ? (int) $_POST['proveedor_anulacion_id'] : 1;

        if($ingresos_id === "" || !is_numeric($ingresos_id)){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se recibió el ingreso a anular.",
                "type"  => "error"
            ]);
        }

        // Consultamos el ingreso original directamente desde BD
        $resultIngreso = ingresosContabilidadModelo::obtener_ingreso_anulacion_modelo($ingresos_id);

        if(!$resultIngreso || $resultIngreso->num_rows === 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se encontró el ingreso a anular.",
                "type"  => "error"
            ]);
        }

        $ingresoData = $resultIngreso->fetch_assoc();

        if((int)$ingresoData['estado'] === 0){
            return mainModel::showNotification([
                "title" => "Ingreso ya anulado",
                "text"  => "Este ingreso ya se encuentra anulado.",
                "type"  => "warning"
            ]);
        }

        $cuentas_id = (int)$ingresoData['cuentas_id'];
        $empresa_id = (int)$ingresoData['empresa_id'];
        $fecha = $ingresoData['fecha'];
        $factura = $ingresoData['factura'];
        $subtotal = (float)$ingresoData['subtotal'];
        $descuento = (float)$ingresoData['descuento'];
        $nc = (float)$ingresoData['nc'];
        $isv = (float)$ingresoData['impuesto'];
        $total = (float)$ingresoData['total'];
        $observacionOriginal = $ingresoData['observacion'];

        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");

        $obsIngreso = "[ANULACIÓN] Ingreso #{$ingresos_id} anulado.";
        if($factura != ""){
            $obsIngreso .= " Factura: {$factura}.";
        }

        $obsEgresoAjuste = "[REINTEGRO INGRESO] Reversión del ingreso #{$ingresos_id} por L {$total}.";
        if($factura != ""){
            $obsEgresoAjuste .= " Factura original: {$factura}.";
        }

        if($observacionOriginal != ""){
            $obsEgresoAjuste .= " Obs: {$observacionOriginal}";
        }

        $obsIngreso = substr($obsIngreso, 0, 150);
        $obsEgresoAjuste = substr($obsEgresoAjuste, 0, 150);

        // Consultar saldo actual
        $consultaSaldo = ingresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id);
        $saldoAnterior = 0;

        if($consultaSaldo && $consultaSaldo->num_rows > 0){
            $lastSaldo = $consultaSaldo->fetch_assoc();
            $saldoAnterior = isset($lastSaldo['saldo']) ? (float)$lastSaldo['saldo'] : 0;
        }

        $ingresoMov = 0;
        $egresoMov = $total;
        $nuevoSaldo = $saldoAnterior - $egresoMov;

        $egresos_id_ajuste = mainModel::correlativo("egresos_id","egresos");

        $datosCancel = [
            "ingresos_id" => (int)$ingresos_id,
            "estado" => 0,
            "observacion" => $obsIngreso
        ];

        $datosEgreso = [
            "egresos_id" => $egresos_id_ajuste,
            "cuentas_id" => $cuentas_id,
            "proveedores_id" => $proveedores_id_ajus,
            "empresa_id" => $empresa_id,
            "tipo_egreso" => 2,
            "fecha" => $fecha,
            "factura" => $factura ?: "",
            "factura_pdf" => "",
            "subtotal" => $subtotal,
            "descuento" => $descuento,
            "nc" => $nc,
            "isv" => $isv,
            "total" => $total,
            "observacion" => $obsEgresoAjuste,
            "estado" => 1,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro,
            "categoria_gastos" => 0,
            "categoria_gastos_id" => 0
        ];

        $datosMov = [
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id,
            "fecha" => $fecha,
            "ingreso" => $ingresoMov,
            "egreso" => $egresoMov,
            "saldo" => $nuevoSaldo,
            "colaboradores_id" => $colaboradores_id,
            "fecha_registro" => $fecha_registro
        ];

        $procesar = ingresosContabilidadModelo::anular_ingreso_con_reintegro_modelo(
            $datosCancel,
            $datosEgreso,
            $datosMov
        );

        if(!$procesar){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se pudo anular el ingreso ni registrar el reintegro. Revise el log del servidor.",
                "type"  => "error"
            ]);
        }

        mainModel::guardarHistorial([
            "modulo"           => 'Ingresos Contabilidad',
            "colaboradores_id" => $colaboradores_id,
            "status"           => "Cancelación",
            "observacion"      => "Ingreso ID {$ingresos_id} anulado; egreso de reintegro ID {$egresos_id_ajuste} por L {$total}.",
            "fecha_registro"   => date("Y-m-d H:i:s")
        ]);

        return mainModel::showNotification([
            "type"    => "success",
            "title"   => "Ingreso anulado",
            "text"    => "Se anuló el ingreso, se registró el egreso de reintegro y el movimiento de cuenta.",
            "form"    => "formIngresosContables",
            "funcion" => "listar_ingresos_contabilidad();total_ingreso_footer();",
            "modal"   => "modalIngresosContables"
        ]);
    }
}