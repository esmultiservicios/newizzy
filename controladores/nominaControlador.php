<?php
if($peticionAjax){
    require_once "../modelos/nominaModelo.php";
}else{
    require_once "./modelos/nominaModelo.php";
}

class nominaControlador extends nominaModelo{
    /*----------  Controlador agregar nómina  ----------*/
    public function agregar_nomina_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
                    
        $pago_planificado_id = mainModel::cleanString($_POST['nomina_pago_planificado_id']);
        $empresa_id = mainModel::cleanString($_POST['nomina_empresa_id']);
        $tipo_nomina = mainModel::cleanString($_POST['tipo_nomina']);
        $fecha_inicio = mainModel::cleanString($_POST['nomina_fecha_inicio']);                        
        $fecha_fin = mainModel::cleanString($_POST['nomina_fecha_fin']);
        $detalle = mainModel::cleanString($_POST['nomina_detale']);
        $importe = mainModel::cleanString($_POST['nomina_importe'] === "" ? 0 : $_POST['nomina_importe']);            
        $notas = mainModel::cleanString($_POST['nomina_notas']);
        $cuentas_id = mainModel::cleanString($_POST['pago_nomina']);
        $usuario = $_SESSION['colaborador_id_sd'];
        $estado = 0;//SIN GENERAR
        $fecha_registro = date("Y-m-d H:i:s");            

        $datos = [
            "pago_planificado_id" => $pago_planificado_id,
            "empresa_id" => $empresa_id,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "detalle" => $detalle,
            "importe" => $importe,
            "notas" => $notas,
            "usuario" => $usuario,
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,
            "tipo_nomina" => $tipo_nomina,
            "cuentas_id" => $cuentas_id,
        ];
        
        if(nominaModelo::valid_nomina_modelo($detalle)->num_rows > 0){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Ya existe una nómina con este detalle",
            ]);               
        }
        
        if(!nominaModelo::agregar_nomina_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar la nómina",
                "type" => "error"
            ]);
        }
                    
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Nómina registrada",
            "text" => "La nómina se registró correctamente",
            "funcion" => "listar_nominas();getPagoPlanificado();getEmpresa();getTipoNomina();",
        ]);            
    }

    /*----------  Controlador agregar vale  ----------*/
    public function agregar_vale_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
                    
        $vale_empleado = mainModel::cleanString($_POST['vale_empleado']);
        $vale_monto = mainModel::cleanString($_POST['vale_monto']);
        $vale_fecha = mainModel::cleanString($_POST['vale_fecha']);
        $vale_notas = mainModel::cleanString($_POST['vale_notas']);
        $usuario = $_SESSION['colaborador_id_sd'];
        $empresa_id = $_SESSION['empresa_id_sd'];
        $estado = 0;//SIN GENERAR
        $fecha_registro = date("Y-m-d H:i:s");            

        $datos = [
            "nomina_id" => 0,
            "colaboradores_id" => $vale_empleado,
            "monto" => $vale_monto,
            "fecha" => $vale_fecha,
            "nota" => $vale_notas,
            "usuario" => $usuario,
            "estado" => $estado,
            "empresa_id" => $empresa_id,
            "fecha_registro" => $fecha_registro
        ];
        
        if(nominaModelo::valid_vale_modelo($vale_empleado)->num_rows > 0){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Este empleado ya tiene un vale registrado",
            ]);               
        }
        
        if(!nominaModelo::agregar_vale_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar el vale",
                "type" => "error"
            ]);
        }
                    
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Vale registrado",
            "text" => "El vale se registró correctamente",
            "funcion" => "listar_vales();getEmpleadoVales();",
        ]);            
    }

    /*----------  Controlador agregar detalles nómina  ----------*/
    public function agregar_nomina_detalles_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }
                    
        $nomina_id = $_POST['nomina_id'];
        $colaboradores_id = mainModel::cleanString($_POST['nominad_empleados']);
        $salario_mensual = mainModel::cleanString($_POST['nominad_salario']);
        $salario_diario = mainModel::cleanString($_POST['nominad_sueldo_diario']);
        $salario_hora = mainModel::cleanString($_POST['nominad_sueldo_hora']);
        $salario = mainModel::cleanString($_POST['salario']);

        //INGRESOS
        $dias_trabajados = mainModel::cleanString($_POST['nominad_diast']);
        $hrse25 = mainModel::cleanString($_POST['nominad_horas25']);
        $hrse50 = mainModel::cleanString($_POST['nominad_horas50']);            
        $hrse75 = mainModel::cleanString($_POST['nominad_horas75']);
        $hrse100 = mainModel::cleanString($_POST['nominad_horas100']);                        
        $retroactivo = mainModel::cleanString($_POST['nominad_retroactivo']);
        $bono = mainModel::cleanString($_POST['nominad_bono']);
        $otros_ingresos = mainModel::cleanString($_POST['nominad_otros_ingresos']);
        
        //EGRESOS
        $deducciones = mainModel::cleanString($_POST['nominad_deducciones']);
        $prestamo = mainModel::cleanString($_POST['nominad_prestamo']);
        $ihss = mainModel::cleanString($_POST['nominad_ihss']);
        $rap = mainModel::cleanString($_POST['nominad_rap']);
        $isr = mainModel::cleanString($_POST['nominad_isr']);
        $vales = mainModel::cleanString($_POST['nominad_vale']);
        $incapacidad_ihss = mainModel::cleanString($_POST['nominad_incapacidad_ihss']);

        //RESUMEN
        $neto_ingresos = mainModel::cleanString($_POST['nominad_neto_ingreso']);
        $neto_egresos = mainModel::cleanString($_POST['nominad_neto_egreso']);
        $neto = mainModel::cleanString($_POST['nominad_neto']);
        
        $hrse25_valor = mainModel::cleanString($_POST['hrse25_valor']);
        $hrse50_valor = mainModel::cleanString($_POST['hrse50_valor']);
        $hrse75_valor = mainModel::cleanString($_POST['hrse75_valor']);
        $hrse100_valor = mainModel::cleanString($_POST['hrse100_valor']);

        $usuario = $_SESSION['colaborador_id_sd'];
        $estado = 0;//SIN GENERAR
        $notas = mainModel::cleanString($_POST['nomina_detalles_notas']);
        $fecha_registro = date("Y-m-d H:i:s");    

        $datos = [
            "nomina_id" => $nomina_id,
            "colaboradores_id" => $colaboradores_id,
            "salario_mensual" => $salario_mensual,
            "dias_trabajados" => $dias_trabajados,
            "hrse25" => $hrse25,
            "hrse50" => $hrse50,
            "hrse75" => $hrse75,
            "hrse100" => $hrse100,
            "retroactivo" => $retroactivo,
            "bono" => $bono,
            "otros_ingresos" => $otros_ingresos,
            "deducciones" => $deducciones,
            "prestamo" => $prestamo,
            "ihss" => $ihss,
            "rap" => $rap,
            "isr" => $isr,
            "vales" => $vales,
            "incapacidad_ihss" => $incapacidad_ihss,
            "neto_ingresos" => $neto_ingresos,
            "neto_egresos" => $neto_egresos,
            "neto" => $neto,                    
            "usuario" => $usuario,
            "estado" => $estado,
            "notas" => $notas,
            "fecha_registro" => $fecha_registro,
            "hrse25_valor" => $hrse25_valor,
            "hrse50_valor" => $hrse50_valor,
            "hrse75_valor" => $hrse75_valor,
            "hrse100_valor" => $hrse100_valor,
            "salario" => $salario
        ];
        
        if(nominaModelo::valid_nomina_detalles_modelo($nomina_id, $colaboradores_id)->num_rows > 0){
            return mainModel::showNotification([
                "type" => "error",
                "title" => "Error",
                "text" => "Este empleado ya está registrado en la nómina",
            ]);
        }
        
        if(!nominaModelo::agregar_nomina_detalles_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo registrar el detalle de nómina",
                "type" => "error"
            ]);
        }
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Detalle registrado",
            "text" => "El detalle de nómina se registró correctamente",
            "funcion" => "listar_nominas_detalles();getEmpleado();",
        ]);
    }
    
    /*----------  Controlador editar nómina  ----------*/
    public function edit_nomina_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $nomina_id = $_POST['nomina_id'];
        $fecha_inicio = mainModel::cleanString($_POST['nomina_fecha_inicio']);                        
        $fecha_fin = mainModel::cleanString($_POST['nomina_fecha_fin']);
        $notas = mainModel::cleanString($_POST['nomina_notas']);
        
        $datos = [
            "nomina_id" => $nomina_id,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,            
            "notas" => $notas,
        ];        

        if(!nominaModelo::edit_nomina_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo actualizar la nómina",
                "type" => "error"
            ]);
        }
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Nómina actualizada",
            "text" => "La nómina se actualizó correctamente",
            "funcion" => "listar_nominas();getPagoPlanificado();getEmpresa();getTipoNomina();",
        ]);
    }

    /*----------  Controlador editar detalles nómina  ----------*/
    public function edit_nomina_detalles_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return mainModel::showNotification([
                "title" => "Error de sesión",
                "text" => $validacion['mensaje'],
                "type" => "error",
                "funcion" => "window.location.href = '".$validacion['redireccion']."'"
            ]);
        }

        $nomina_id = $_POST['nomina_id'];
        $colaboradores_id = mainModel::cleanString($_POST['colaboradores_id']);
        $salario = mainModel::cleanString($_POST['nominad_salario']);    
        $nomina_detalles_id = mainModel::cleanString($_POST['nomina_detalles_id']);                    
    
        //INGRESOS
        $dias_trabajados = mainModel::cleanString($_POST['nominad_diast']);
        $hrse25 = mainModel::cleanString($_POST['nominad_horas25']);
        $hrse50 = mainModel::cleanString($_POST['nominad_horas50']);            
        $hrse75 = mainModel::cleanString($_POST['nominad_horas75']);
        $hrse100 = mainModel::cleanString($_POST['nominad_horas100']);                        
        $retroactivo = mainModel::cleanString($_POST['nominad_retroactivo']);
        $bono = mainModel::cleanString($_POST['nominad_bono']);
        $otros_ingresos = mainModel::cleanString($_POST['nominad_otros_ingresos']);
        
        //EGRESOS
        $deducciones = mainModel::cleanString($_POST['nominad_deducciones']);
        $prestamo = mainModel::cleanString($_POST['nominad_prestamo']);
        $ihss = mainModel::cleanString($_POST['nominad_ihss']);
        $rap = mainModel::cleanString($_POST['nominad_rap']);
        $isr = mainModel::cleanString($_POST['nominad_isr']);
        $vales = mainModel::cleanString($_POST['nominad_vale']);
        $incapacidad_ihss = mainModel::cleanString($_POST['nominad_incapacidad_ihss']);            
        
        //RESUMEN
        $neto_ingresos = mainModel::cleanString($_POST['nominad_neto_ingreso']);
        $neto_egresos = mainModel::cleanString($_POST['nominad_neto_egreso']);
        $neto = mainModel::cleanString($_POST['nominad_neto']);

        $hrse25_valor = mainModel::cleanString($_POST['hrse25_valor']);
        $hrse50_valor = mainModel::cleanString($_POST['hrse50_valor']);
        $hrse75_valor = mainModel::cleanString($_POST['hrse75_valor']);
        $hrse100_valor = mainModel::cleanString($_POST['hrse100_valor']);            

        $estado = 1;//ACTIVAS
        $notas = mainModel::cleanString($_POST['nomina_detalles_notas']);
        $fecha_registro = date("Y-m-d H:i:s");    

        $datos = [
            "nomina_id" => $nomina_id ?? 0,
            "colaboradores_id" => $colaboradores_id ?? 0,
            "salario" => $salario ?? 0,
            "hrse25" => $hrse25  ?? 0,
            "hrse50" => $hrse50 ?? 0,
            "hrse75" => $hrse75 ?? 0,
            "hrse100" => $hrse100 ?? 0,
            "retroactivo" => $retroactivo ?? 0,
            "bono" => $bono ?? 0,
            "otros_ingresos" => $otros_ingresos ?? 0,
            "deducciones" => $deducciones ?? 0,
            "prestamo" => $prestamo ?? 0,
            "rap" => $rap ?? 0,
            "ihss" => $ihss ?? 0,
            "isr" => $isr ?? 0,
            "vales" => $vales ?? 0,
            "incapacidad_ihss" => $incapacidad_ihss ?? 0,
            "neto_ingresos" => $neto_ingresos ?? 0,
            "neto_egresos" => $neto_egresos ?? 0,
            "neto" => $neto ?? 0,                    
            "estado" => $estado ?? 0,
            "notas" => $notas,
            "fecha_registro" => $fecha_registro,                
            "dias_trabajados" => $dias_trabajados ?? 0,
            "nomina_detalles_id" => $nomina_detalles_id ?? 0,
            "hrse25_valor" => $hrse25_valor,
            "hrse50_valor" => $hrse50_valor,
            "hrse75_valor" => $hrse75_valor,
            "hrse100_valor" => $hrse100_valor,
        ];    

        if(!nominaModelo::edit_nomina_detalles_modelo($datos)){
            return mainModel::showNotification([
                "title" => "Error",
                "text" => "No se pudo actualizar el detalle de nómina",
                "type" => "error"
            ]);
        }
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Detalle actualizado",
            "text" => "El detalle de nómina se actualizó correctamente",
            "funcion" => "listar_nominas_detalles();getEmpleado();",
        ]);
    }        
    
    /*----------  Controlador eliminar nómina  ----------*/
    public function delete_nomina_controlador(){
        $nomina_id = $_POST['nomina_id'];

        $campos = ['detalle'];
        $tabla = "nomina";
        $condicion = "nomina_id = {$nomina_id}";

        $nominaConsulta = mainModel::consultar_tabla($tabla, $campos, $condicion);
        
        if (empty($nominaConsulta)) {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Nómina #{$nomina_id} no encontrada"
            ]);
        }

        $detalle = $nominaConsulta[0]['detalle'] ?? '';

        if(nominaModelo::valid_nomina_detalles_delete_modelo($nomina_id)->num_rows > 0){
            return json_encode([
                "status" => "error",
                "title" => "No se puede eliminar",
                "message" => "La nómina {$detalle} tiene detalles asociados"
            ]);                
        }

        if(!nominaModelo::delete_nomina_modelo($nomina_id)){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar la nómina {$detalle}"
            ]);
        }
        
        return json_encode([
            "status" => "success",
            "title" => "Nómina eliminada",
            "message" => "La nómina {$detalle} se eliminó correctamente"
        ]);
    }

    /*----------  Controlador eliminar detalles nómina  ----------*/
    public function delete_nomina_detalles_controlador(){
        $nomina_detalles_id = $_POST['nomina_detalles_id'];
        
        $campos = ['nomina_id'];
        $tabla = "nomina_detalles";
        $condicion = "nomina_detalles_id = {$nomina_detalles_id}";

        $detalleNomina = mainModel::consultar_tabla($tabla, $campos, $condicion);
        
        if (empty($detalleNomina)) {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Detalle de nómina no encontrado"
            ]);
        }
        
        $nomina_id = $detalleNomina[0]['nomina_id'] ?? '';

        if(!nominaModelo::delete_nomina_detalles_modelo($nomina_detalles_id)){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar la nómina número {$nomina_id}"
            ]);
        }
        
        return json_encode([
            "status" => "success",
            "title" => "Detalle eliminado",
            "message" => "El detalle de nómina se eliminó correctamente"
        ]);                    
    }    
}