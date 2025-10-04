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

        $clientes_id = mainModel::cleanStringConverterCase(isset($_POST['cliente_ingresos']) ? $_POST['cliente_ingresos'] : "");
        $cuentas_id = mainModel::cleanStringConverterCase($_POST['cuenta_ingresos']);
        $empresa_id = $_SESSION['empresa_id_sd'];
        $fecha = $_POST['fecha_ingresos'];
        $factura = mainModel::cleanString($_POST['factura_ingresos']);
        $subtotal = mainModel::cleanStringConverterCase($_POST['subtotal_ingresos'] === "" ? 0 : $_POST['subtotal_ingresos']);
        $isv = mainModel::cleanStringConverterCase($_POST['isv_ingresos'] === "" ? 0 : $_POST['isv_ingresos']);
        $descuento = mainModel::cleanStringConverterCase($_POST['descuento_ingresos'] === "" ? 0 : $_POST['descuento_ingresos']);
        $nc = 0;
        $total = mainModel::cleanStringConverterCase($_POST['total_ingresos'] === "" ? 0 : $_POST['total_ingresos']);
        $observacion = mainModel::cleanString($_POST['observacion_ingresos']);
        $recibide = mainModel::cleanString($_POST['recibide_ingresos']);
        $estado = 1;
        $tipo_ingreso = 2;//OTROS INGRESOS
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");
        $ingresos_id = mainModel::correlativo("ingresos_id", "ingresos");

        //GUARDAMOS EL CLIENTE SI NO EXISTE Y GENERAMOS SU CODIGO DE CLIENTE
        //VALIDAMOS SI EXISTE EL CLIENTE
        $resultCliente = ingresosContabilidadModelo::valid_clientes_cuentas_contabilidad($recibide);

        if ($resultCliente->num_rows === 0) {
            $mainModel = new mainModel();
            $planConfig = $mainModel->getPlanConfiguracionMainModel();
            
            // Solo validar si existe configuración de plan
			if (isset($planConfig['ingresos'])) {
				$limiteIngresos = (int)$planConfig['ingresos']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
				
                // Caso 1: Límite es 0 (sin permisos)
                if ($limiteIngresos === 0) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Acceso restringido",
                        "text" => "Su plan no incluye la creación de ingresos contables."
                    ]);
                }
                
                // Caso 2: Validar disponibilidad
                $totalRegistradas = (int)ingresosContabilidadModelo::getTotalIngresosRegistrados();
                
                if ($totalRegistradas >= $limiteIngresos) {
                    return $mainModel->showNotification([
                        "type" => "error",
                        "title" => "Límite alcanzado",
                        "text" => "Ha excedido el límite mensual de ingresos contables (Máximo: $limiteIngresos)."
                    ]);
                }
			}
        }else{
            //CONSULTAMOS EL CLIENTE_ID
            $cliente_consulta = ingresosContabilidadModelo::valid_clientes_cuentas_contabilidad($recibide)->fetch_assoc();
            $clientes_id = $cliente_consulta['clientes_id'];
        }
        
        $datos_ingresos = [
            "ingresos_id" => $ingresos_id,
            "clientes_id" => $clientes_id === "" ? 0 : $clientes_id,
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
            "tipo_ingreso" => $tipo_ingreso,
            "recibide" => $recibide
        ];

        // Verifica si la factura está vacía
        if ($factura === "") {
            // Agrega ingresos contabilidad
            $query = ingresosContabilidadModelo::agregar_ingresos_contabilidad_modelo($datos_ingresos);
    
            // Si la inserción fue exitosa
            if ($query) {
                // Consulta el saldo disponible para la cuenta
                $consulta_ingresos_contabilidad = ingresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
                $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) && $consulta_ingresos_contabilidad['saldo'] !== "" ? $consulta_ingresos_contabilidad['saldo'] : 0;
                            
                $ingreso = $total;
                $egreso = 0;
                $saldo = $saldo_consulta + $ingreso;
    
                // Agrega los movimientos de la cuenta
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

                // Registrar en historial
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
            } else {
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo registrar el ingreso contable",
                    "type" => "error"
                ]);
            }
        } else {
            $resultIngresos = ingresosContabilidadModelo::valid_ingreso_cuentas_modelo($datos_ingresos);
        
            // Si no hay resultados en la validación
            if ($resultIngresos->num_rows === 0) {
                // Agrega ingresos contabilidad sin verificar la factura
                $query = ingresosContabilidadModelo::agregar_ingresos_contabilidad_modelo($datos_ingresos);
                                
                // Si la inserción fue exitosa
                if ($query) {
                    // Consulta el saldo disponible para la cuenta
                    $consulta_ingresos_contabilidad = ingresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
                    $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) && $consulta_ingresos_contabilidad['saldo'] !== "" ? $consulta_ingresos_contabilidad['saldo'] : 0;

                    $ingreso = $total;
                    $egreso = 0;
                    $saldo = $saldo_consulta + $ingreso;

                    // Agrega los movimientos de la cuenta
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

                    // Registrar en historial
                    mainModel::guardarHistorial([
                        "modulo" => 'Ingresos Contabilidad',
                        "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                        "status" => "Registro",
                        "observacion" => "Se registró ingreso con factura {$factura} por {$total}",
                        "fecha_registro" => date("Y-m-d H:i:s")
                    ]);

                    return mainModel::showNotification([
                        "type" => "success",
                        "title" => "Registro almacenado",
                        "text" => "El registro se ha almacenado correctamente",
                        "form" => "formIngresosContables",
                        "funcion" => "listar_ingresos_contabilidad();getClientesIngresos(); getCuentaIngresos(); getEmpresaIngresos();printIngresos(" . $ingresos_id . ");total_ingreso_footer();"
                    ]);
                } else {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "No se pudo registrar el ingreso contable",
                        "type" => "error"
                    ]);
                }
            } else {
                return mainModel::showNotification([
                    "title" => "Registro ya existe",
                    "text" => "Ya existe un ingreso con esta factura",
                    "type" => "error"
                ]);
            }            
        }
    }

    public function edit_ingresos_contabilidad_controlador(){
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

        $ingresos_id = $_POST['ingresos_id'];
        $clientes_id = $_POST['cliente_ingresos'];
        $factura = mainModel::cleanString($_POST['factura_ingresos']);
        $fecha = $_POST['fecha_ingresos'];
        $observacion = mainModel::cleanString($_POST['observacion_ingresos']);

        $datos = [
            "ingresos_id" => $ingresos_id,
            "clientes_id" => $clientes_id,
            "factura" => $factura,
            "fecha" => $fecha,
            "observacion" => $observacion,                            
        ];        

        $query = ingresosContabilidadModelo::edit_ingresos_contabilidad_modelo($datos);

        if($query){
            // Registrar en historial
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
        // 1) Validar sesión
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title"   => "Error de sesión",
                "text"    => $validacion['mensaje'],
                "type"    => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
    
        // 2) Entradas (POST)
        $ingresos_id = $_POST['ingresos_id'];
        $cuentas_id  = mainModel::cleanString($_POST['cuenta_ingresos']);
        $empresa_id  = $_SESSION['empresa_id_sd'];
        $fecha       = mainModel::cleanString($_POST['fecha_ingresos']);
        $factura     = mainModel::cleanString($_POST['factura_ingresos']);
    
        $subtotal    = (float) mainModel::cleanString($_POST['subtotal_ingresos'] === "" ? 0 : $_POST['subtotal_ingresos']);
        $isv         = (float) mainModel::cleanString($_POST['isv_ingresos'] === "" ? 0 : $_POST['isv_ingresos']);
        $descuento   = (float) mainModel::cleanString($_POST['descuento_ingresos'] === "" ? 0 : $_POST['descuento_ingresos']);
        $nc          = (float) mainModel::cleanString($_POST['nc_ingresos'] === "" ? 0 : $_POST['nc_ingresos']);
        $total       = (float) mainModel::cleanString($_POST['total_ingresos'] === "" ? 0 : $_POST['total_ingresos']);
    
        $observacionIn       = mainModel::cleanString($_POST['observacion_ingresos']);
        $proveedores_id_ajus = isset($_POST['proveedor_anulacion_id']) ? (int) $_POST['proveedor_anulacion_id'] : 1;
    
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro   = date("Y-m-d H:i:s");
    
        // 3) Verificar existencia del ingreso
        $cn = mainModel::connection();
        $rs_valid = $cn->query("SELECT ingresos_id FROM ingresos WHERE ingresos_id = '{$ingresos_id}' LIMIT 1");
        if(!$rs_valid || $rs_valid->num_rows === 0){
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se encontró el ingreso a anular.",
                "type"  => "error"
            ]);
        }
    
        // 4) Observaciones claras
        $obsIngreso = "[ANULACIÓN] Ingreso #{$ingresos_id} anulado."
                    . ($factura ? " Factura: {$factura}." : "")
                    . ($observacionIn ? " Motivo: {$observacionIn}" : "");
    
        $obsEgresoAjuste = "[AJUSTE POR ANULACIÓN] Reversión del ingreso #{$ingresos_id} por L {$total}."
                         . ($factura ? " Factura original: {$factura}." : "")
                         . ($observacionIn ? " Motivo: {$observacionIn}" : "");
    
        // 5) Transacción
        $cn->begin_transaction();
        try {
            // 5.1) Marcar el INGRESO como anulado (estado=0) + observación
            $datosCancel = [
                "ingresos_id" => $ingresos_id,
                "estado"      => 0,            // 0 = anulado
                "observacion" => $obsIngreso
            ];
            if(!$this->cancel_ingresos_contabilidad_modelo($datosCancel)){ // <- usamos método del MISMO modelo (this->)
                throw new Exception("No se pudo marcar el ingreso como anulado.");
            }
    
            // 5.2) Insertar el EGRESO de ajuste (salida real de la cuenta)
            $egresos_id_ajuste = mainModel::correlativo("egresos_id","egresos");
            $tipo_egreso       = 2;   // GASTOS/OTROS (ajusta si tu catálogo usa otro código)
    
            $datosEgreso = [
                "egresos_id"       => $egresos_id_ajuste,
                "cuentas_id"       => $cuentas_id,
                "proveedores_id"   => $proveedores_id_ajus,
                "empresa_id"       => $empresa_id,
                "tipo_egreso"      => $tipo_egreso,
                "fecha"            => $fecha,
                "factura"          => $factura ?: "",
                "factura_pdf"      => "",
                "subtotal"         => $subtotal,
                "descuento"        => $descuento,
                "nc"               => $nc,
                "isv"              => $isv,
                "total"            => $total,
                "observacion"      => $obsEgresoAjuste,
                "estado"           => 1, // activo
                "colaboradores_id" => $colaboradores_id,
                "fecha_registro"   => $fecha_registro,
                "categoria_gastos" => 0
            ];
            if(!$this->agregar_egreso_por_anulacion_modelo($datosEgreso)){ // <- reusamos el INSERT estándar de egresos
                throw new Exception("No se pudo registrar el egreso de ajuste.");
            }
    
            // 5.3) Registrar MOVIMIENTO (egreso) por el ajuste
            $lastSaldo = $this->consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
            $saldoAnterior = isset($lastSaldo['saldo']) ? (float)$lastSaldo['saldo'] : 0.00;
    
            $ingresoMov = 0.00;
            $egresoMov  = $total;
            $nuevoSaldo = $saldoAnterior - $egresoMov;
    
            $datosMov = [
                "cuentas_id"       => $cuentas_id,
                "empresa_id"       => $empresa_id,
                "fecha"            => $fecha,
                "ingreso"          => $ingresoMov,
                "egreso"           => $egresoMov,
                "saldo"            => $nuevoSaldo,
                "colaboradores_id" => $colaboradores_id,
                "fecha_registro"   => $fecha_registro
            ];
            if(!$this->agregar_movimientos_contabilidad_modelo($datosMov)){
                throw new Exception("No se pudo insertar el movimiento de cuenta.");
            }
    
            // 5.4) Historial
            mainModel::guardarHistorial([
                "modulo"           => 'Ingresos Contabilidad',
                "colaboradores_id" => $colaboradores_id,
                "status"           => "Cancelación",
                "observacion"      => "Ingreso ID {$ingresos_id} anulado; egreso de ajuste ID {$egresos_id_ajuste} por L {$total}.",
                "fecha_registro"   => date("Y-m-d H:i:s")
            ]);
    
            // 5.5) OK
            $cn->commit();
    
            return mainModel::showNotification([
                "type"    => "success",
                "title"   => "Ingreso anulado",
                "text"    => "Se registró el ajuste y el movimiento de cuenta.",
                "form"    => "formIngresosContables",
                "funcion" => "listar_ingresos_contabilidad();total_ingreso_footer();",
                "modal"   => "modalIngresosContables"
            ]);
    
        } catch (Exception $e) {
            $cn->rollback();
            return mainModel::showNotification([
                "title" => "Error",
                "text"  => "No se pudo anular el ingreso: ".$e->getMessage(),
                "type"  => "error"
            ]);
        }
    }       
}