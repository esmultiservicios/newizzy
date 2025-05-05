<?php
if($peticionAjax){
    require_once "../modelos/egresosContabilidadModelo.php";
}else{
    require_once "./modelos/egresosContabilidadModelo.php";
}

class egresosContabilidadControlador extends egresosContabilidadModelo{
    public function agregar_egresos_contabilidad_controlador(){
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
        
        $proveedores_id = $_POST['proveedor_egresos'];
        $cuentas_id = mainModel::cleanStringConverterCase($_POST['cuenta_egresos']);
        $empresa_id = $_SESSION['empresa_id_sd'];
        $tipo_egreso = 2;//GASTOS
        $fecha = $_POST['fecha_egresos'];
        $factura = mainModel::cleanString($_POST['factura_egresos']);
        $subtotal = mainModel::cleanStringConverterCase($_POST['subtotal_egresos'] === "" ? 0 : $_POST['subtotal_egresos']);
        $isv = mainModel::cleanStringConverterCase($_POST['isv_egresos'] === "" ? 0 : $_POST['isv_egresos']);
        $descuento = mainModel::cleanStringConverterCase($_POST['descuento_egresos'] === "" ? 0 : $_POST['descuento_egresos']);
        $nc = mainModel::cleanStringConverterCase($_POST['nc_egresos'] === "" ? 0 : $_POST['nc_egresos']);
        $total = mainModel::cleanStringConverterCase($_POST['total_egresos'] === "" ? 0 : $_POST['total_egresos']);
        $observacion = mainModel::cleanString($_POST['observacion_egresos']);
        $categoria_gastos = mainModel::cleanString($_POST['categoria_gastos']);
        $estado = 1;
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");    
        $egresos_id = mainModel::correlativo("egresos_id", "egresos");

        $datos = [
            "egresos_id" => $egresos_id,
            "proveedores_id" => $proveedores_id === "" ? 0 : $proveedores_id,
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
            "tipo_egreso" => $tipo_egreso,
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
            "categoria_gastos" => $categoria_gastos
        ];
        
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        
        // Solo validar si existe configuración de plan
        if (!empty($planConfig)) {
            $limiteEgresos = (int)($planConfig['egresos'] ?? 0);
            
            // Caso 1: Límite es 0 (sin permisos)
            if ($limiteEgresos === 0) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Acceso restringido",
                    "text" => "Su plan no incluye la creación de egresos contables."
                ]);
            }
            
            // Caso 2: Validar disponibilidad
            $totalRegistradas = (int)egresosContabilidadModelo::getTotalEgresosRegistrados();
            
            if ($totalRegistradas >= $limiteEgresos) {
                return $mainModel->showNotification([
                    "type" => "error",
                    "title" => "Límite alcanzado",
                    "text" => "Ha excedido el límite mensual de egresos contables (Máximo: $limiteEgresos)."
                ]);
            }
        }

        $resultEgresos = egresosContabilidadModelo::valid_egresos_cuentas_modelo($datos);
        
        if($resultEgresos->num_rows == 0){
            $query = egresosContabilidadModelo::agregar_egresos_contabilidad_modelo($datos);
            
            if($query){
                //CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
                $consulta_ingresos_contabilidad = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
                $saldo_consulta = isset($consulta_ingresos_contabilidad['saldo']) && $consulta_ingresos_contabilidad['saldo'] !== "" ? $consulta_ingresos_contabilidad['saldo'] : 0;
                
                $ingreso = 0;
                $egreso = $total;
                $saldo = $saldo_consulta - $egreso;
                
                //AGREGAMOS LOS MOVIMIENTOS DE LA CUENTA
                $datos_movimientos = [
                    "cuentas_id" => $cuentas_id,
                    "empresa_id" => $empresa_id === "" ? 1 : $empresa_id,
                    "fecha" => $fecha,
                    "ingreso" => $ingreso,
                    "egreso" => $egreso,
                    "saldo" => $saldo,
                    "colaboradores_id" => $colaboradores_id,
                    "fecha_registro" => $fecha_registro,                
                ];
                
                egresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);

                // Registrar en historial
                mainModel::guardarHistorial([
                    "modulo" => 'Egresos Contabilidad',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                    "status" => "Registro",
                    "observacion" => "Se registró egreso contable ID: {$egresos_id} por {$total}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ]);
            
                return mainModel::showNotification([
                    "type" => "success",
                    "title" => "Registro almacenado",
                    "text" => "El registro se ha almacenado correctamente",
                    "form" => "formEgresosContables",
                    "funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();printGastos(".$egresos_id.");total_gastos_footer();"
                ]);
            }else{
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo registrar el egreso contable",
                    "type" => "error"
                ]);                
            }                
        }else{
            return mainModel::showNotification([
                "title" => "Registro ya existe",
                "text" => "Ya existe un egreso con estos datos",
                "type" => "error"
            ]);                
        }
    }

    public function agregar_categoria_egresos_controlador(){
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            echo json_encode([
                'success' => false,
                'title' => 'Error de sesión',
                'text' => $validacion['mensaje'],
                'type' => 'error'
            ]);
            exit();
        }
        
        $categoria = $_POST['categoria'];    
        $estado = 1;
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");    
        $categoria_gastos_id = mainModel::correlativo("categoria_gastos_id", "categoria_gastos");
    
        $datos = [
            "categoria_gastos_id" => $categoria_gastos_id,
            "nombre" => $categoria,
            "estado" => $estado,
            "usuario" => $colaboradores_id,
            "date_write" => $fecha_registro                            
        ];
        
        $resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_modelo($datos);
        
        if($resultCategoriaEgresos->num_rows == 0){
            $query = egresosContabilidadModelo::agregar_categoria_egresos_modelo($datos);
            
            if($query){
                // Registrar en historial
                mainModel::guardarHistorial([
                    "modulo" => 'Categoría Egresos',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                    "status" => "Registro",
                    "observacion" => "Se registró categoría de egresos: {$categoria}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ]);
    
                echo json_encode([
                    'success' => true,
                    'title' => 'Registro almacenado',
                    'text' => 'El registro se ha almacenado correctamente',
                    'type' => 'success'
                ]);
            }else{
                echo json_encode([
                    'success' => false,
                    'title' => 'Error',
                    'text' => 'No se pudo registrar la categoría',
                    'type' => 'error'
                ]);                
            }                
        }else{
            echo json_encode([
                'success' => false,
                'title' => 'Registro ya existe',
                'text' => 'Ya existe una categoría con este nombre',
                'type' => 'error'
            ]);                
        }
        exit();
    }

    public function edit_egresos_contabilidad_controlador(){
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

        $egresos_id = $_POST['egresos_id'];
        $proveedores_id = $_POST['proveedor_egresos'];
        $factura = mainModel::cleanString($_POST['factura_egresos']);
        $observacion = mainModel::cleanString($_POST['observacion_egresos']);
        $fecha = $_POST['fecha_egresos'];

        $datos = [
            "egresos_id" => $egresos_id,
            "proveedores_id" => $proveedores_id,
            "factura" => $factura,
            "fecha" => $fecha,
            "observacion" => $observacion,                            
        ];        
        
        $query = egresosContabilidadModelo::edit_egresos_contabilidad_modelo($datos);

        if($query){
            // Registrar en historial
            mainModel::guardarHistorial([
                "modulo" => 'Egresos Contabilidad',
                "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                "status" => "Edición",
                "observacion" => "Se editó egreso contable ID: {$egresos_id}",
                "fecha_registro" => date("Y-m-d H:i:s")
            ]);

            return mainModel::showNotification([
                "type" => "success",
                "title" => "Registro editado",
                "text" => "Registro editado correctamente",
                "form" => "formEgresosContables",
                "funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();printGastos(".$egresos_id.")",
                "modal" => "modalEgresosContables"
            ]);
        }else{
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo editar el egreso contable",
                "type" => "error"
            ]);    
        }
    }

    public function edit_categoria_egresos_contabilidad_controlador() {
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            echo json_encode([
                'success' => false,
                'title' => 'Error de sesión',
                'text' => $validacion['mensaje'],
                'type' => 'error',
                'redirect' => $validacion['redireccion']
            ]);
            exit();
        }
    
        $categoria_gastos_id = $_POST['categoria_gastos_id'];
        $categoria = $_POST['categoria'];
    
        $datos = [
            "categoria_gastos_id" => $categoria_gastos_id,
            "nombre" => $categoria                            
        ];        
        
        $resultCategoriaEgresos = egresosContabilidadModelo::valid_categoria_egresos_modelo($datos);
        
        if($resultCategoriaEgresos->num_rows == 0) {
            $query = egresosContabilidadModelo::edit_categoria_egresos_contabilidad_modelo($datos);
    
            if($query) {
                // Registrar en historial
                mainModel::guardarHistorial([
                    "modulo" => 'Categoría Egresos',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                    "status" => "Edición",
                    "observacion" => "Se editó categoría de egresos ID: {$categoria_gastos_id}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ]);
    
                echo json_encode([
                    'success' => true,
                    'title' => 'Registro editado',
                    'text' => 'Registro editado correctamente',
                    'type' => 'success',
                    'form' => 'formUpdateCategoriaEgresos',
                    'function' => 'listar_categoria_egresos();'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'title' => 'Error',
                    'text' => 'No se pudo editar la categoría',
                    'type' => 'error'
                ]);    
            }
        } else {
            echo json_encode([
                'success' => false,
                'title' => 'Registro ya existe',
                'text' => 'Ya existe una categoría con este nombre',
                'type' => 'error'
            ]);    
        }
        exit();
    }

    public function cancel_egresos_contabilidad_controlador(){
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
        
        $egresos_id = $_POST['egresos_id'];
        $proveedores_id = $_POST['proveedor_egresos'];
        $cuentas_id = mainModel::cleanStringConverterCase($_POST['cuenta_egresos']);
        $empresa_id = $_SESSION['empresa_id_sd'];
        $fecha = mainModel::cleanString($_POST['fecha_egresos']);
        $factura = mainModel::cleanStringConverterCase($_POST['factura_egresos']);
        $subtotal = mainModel::cleanStringConverterCase($_POST['subtotal_egresos']);
        $isv = mainModel::cleanStringConverterCase($_POST['isv_egresos']);
        $descuento = mainModel::cleanStringConverterCase($_POST['descuento_egresos']);
        $nc = mainModel::cleanStringConverterCase($_POST['nc_egresos']);
        $total = mainModel::cleanStringConverterCase($_POST['total_egresos']);
        $observacion = mainModel::cleanString($_POST['observacion_egresos']);
        $estado = 2;
        $tipo_egreso = 2;//GASTOS
        $colaboradores_id = $_SESSION['colaborador_id_sd'];
        $fecha_registro = date("Y-m-d H:i:s");    
        
        $datos = [
            "egresos_id" => $egresos_id,
            "proveedores_id" => $proveedores_id,
            "cuentas_id" => $cuentas_id,
            "empresa_id" => $empresa_id,
            "tipo_egreso" => $tipo_egreso,
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
        ];
        
        $result_valid_egresos = egresosContabilidadModelo::valid_egresos_cuentas_modelo($egresos_id);
        
        if($result_valid_egresos->num_rows > 0){
            $query = egresosContabilidadModelo::cancel_egresos_contabilidad_modelo($egresos_id);
                                
            if($query){
                //CONSULTAMOS EL SALDO DISPONIBLE PARA LA CUENTA
                $consulta_ingresos_contabilidad = egresosContabilidadModelo::consultar_saldo_movimientos_cuentas_contabilidad($cuentas_id)->fetch_assoc();
                $saldo_consulta = $consulta_ingresos_contabilidad['saldo'];    
                $ingreso = $total;
                $egreso = 0;
                $saldo = $saldo_consulta + $ingreso;
                
                //AGREGAMOS LOS MOVIMIENTOS DE LA CUENTA
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
                
                egresosContabilidadModelo::agregar_movimientos_contabilidad_modelo($datos_movimientos);

                // Registrar en historial
                mainModel::guardarHistorial([
                    "modulo" => 'Egresos Contabilidad',
                    "colaboradores_id" => $_SESSION['colaborador_id_sd'],
                    "status" => "Cancelación",
                    "observacion" => "Se canceló egreso contable ID: {$egresos_id}",
                    "fecha_registro" => date("Y-m-d H:i:s")
                ]);
                
                return mainModel::showNotification([
                    "type" => "success",
                    "title" => "Registro cancelado",
                    "text" => "El registro se ha cancelado correctamente",
                    "form" => "formEgresosContables",
                    "funcion" => "listar_gastos_contabilidad();getEmpresaEgresos(); getCuentaEgresos(); getProveedorEgresos();",
                    "modal" => "modalEgresosContables"
                ]);
            }else{
                return mainModel::showNotification([
                    "title" => "Error",
                    "text" => "No se pudo cancelar el egreso contable",
                    "type" => "error"
                ]);                
            }                
        }else{
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se puede cancelar este registro",
                "type" => "error"
            ]);                
        }
    }
}