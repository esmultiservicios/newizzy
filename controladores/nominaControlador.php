<?php
if($peticionAjax){
    require_once "../modelos/nominaModelo.php";
}else{
    require_once "./modelos/nominaModelo.php";
}

class nominaControlador extends nominaModelo{

    /*========== Agregar nómina (JSON) ==========*/
    public function agregar_nomina_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        // Sanitizado
        $pago_planificado_id = mainModel::cleanString($_POST['nomina_pago_planificado_id'] ?? '');
        $empresa_id          = mainModel::cleanString($_POST['nomina_empresa_id'] ?? '');
        $tipo_nomina         = mainModel::cleanString($_POST['tipo_nomina'] ?? '');
        $fecha_inicio        = mainModel::cleanString($_POST['nomina_fecha_inicio'] ?? '');
        $fecha_fin           = mainModel::cleanString($_POST['nomina_fecha_fin'] ?? '');
        $detalle             = mainModel::cleanString($_POST['nomina_detale'] ?? '');
        $importe_raw         = (isset($_POST['nomina_importe']) && $_POST['nomina_importe'] !== "") ? $_POST['nomina_importe'] : 0;
        $importe             = mainModel::cleanString($importe_raw);
        $notas               = mainModel::cleanString($_POST['nomina_notas'] ?? '');
        $cuentas_id          = mainModel::cleanString($_POST['pago_nomina'] ?? '');
        $usuario             = $_SESSION['colaborador_id_sd'] ?? 0;
        $estado              = 0; // SIN GENERAR
        $fecha_registro      = date("Y-m-d H:i:s");

        // Requeridos
        if($detalle === '' || $pago_planificado_id === '' || $empresa_id === '' || $tipo_nomina === '' || $fecha_inicio === '' || $fecha_fin === ''){
            return json_encode([
                "status"  => "error",
                "title"   => "Campos incompletos",
                "message" => "Completa los campos obligatorios."
            ]);
        }

        $datos = [
            "pago_planificado_id" => $pago_planificado_id,
            "empresa_id"          => $empresa_id,
            "fecha_inicio"        => $fecha_inicio,
            "fecha_fin"           => $fecha_fin,
            "detalle"             => $detalle,
            "importe"             => $importe,
            "notas"               => $notas,
            "usuario"             => $usuario,
            "estado"              => $estado,
            "fecha_registro"      => $fecha_registro,
            "tipo_nomina"         => $tipo_nomina,
            "cuentas_id"          => $cuentas_id,
        ];

        // Duplicado
        $dup = $this->valid_nomina_modelo($detalle);
        if ($dup && $dup->num_rows > 0){
            return json_encode([
                "status"  => "error",
                "title"   => "Detalle duplicado",
                "message" => "Ya existe una nómina con este detalle."
            ]);
        }

        // Insertar (el modelo devuelve el ID)
        $nomina_id = $this->agregar_nomina_modelo($datos);
        if (!$nomina_id){
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo registrar la nómina."
            ]);
        }

        return json_encode([
            "status"    => "success",
            "title"     => "Nómina registrada",
            "message"   => "La nómina se registró correctamente.",
            "nomina_id" => $nomina_id,
            "run"       => "listar_nominas();getPagoPlanificado();getEmpresa();getTipoNomina();"
        ]);
    }

    /*========== Agregar detalles nómina (JSON) ==========*/
    public function agregar_nomina_detalles_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        $nomina_id         = mainModel::cleanString($_POST['nomina_id'] ?? '');
        $colaboradores_id  = mainModel::cleanString($_POST['nominad_empleados'] ?? '');
        $salario_mensual   = mainModel::cleanString($_POST['nominad_salario'] ?? 0);
        $salario           = mainModel::cleanString($_POST['salario'] ?? 0);

        // INGRESOS
        $dias_trabajados   = mainModel::cleanString($_POST['nominad_diast'] ?? 0);
        $hrse25            = mainModel::cleanString($_POST['nominad_horas25'] ?? 0);
        $hrse50            = mainModel::cleanString($_POST['nominad_horas50'] ?? 0);
        $hrse75            = mainModel::cleanString($_POST['nominad_horas75'] ?? 0);
        $hrse100           = mainModel::cleanString($_POST['nominad_horas100'] ?? 0);
        $retroactivo       = mainModel::cleanString($_POST['nominad_retroactivo'] ?? 0);
        $bono              = mainModel::cleanString($_POST['nominad_bono'] ?? 0);
        $otros_ingresos    = mainModel::cleanString($_POST['nominad_otros_ingresos'] ?? 0);

        // EGRESOS
        $deducciones       = mainModel::cleanString($_POST['nominad_deducciones'] ?? 0);
        $prestamo          = mainModel::cleanString($_POST['nominad_prestamo'] ?? 0);
        $ihss              = mainModel::cleanString($_POST['nominad_ihss'] ?? 0);
        $rap               = mainModel::cleanString($_POST['nominad_rap'] ?? 0);
        $isr               = mainModel::cleanString($_POST['nominad_isr'] ?? 0);
        $vales             = mainModel::cleanString($_POST['nominad_vale'] ?? 0);
        $incapacidad_ihss  = mainModel::cleanString($_POST['nominad_incapacidad_ihss'] ?? 0);

        // RESUMEN
        $neto_ingresos     = mainModel::cleanString($_POST['nominad_neto_ingreso'] ?? 0);
        $neto_egresos      = mainModel::cleanString($_POST['nominad_neto_egreso'] ?? 0);
        $neto              = mainModel::cleanString($_POST['nominad_neto'] ?? 0);

        $hrse25_valor      = mainModel::cleanString($_POST['hrse25_valor'] ?? 0);
        $hrse50_valor      = mainModel::cleanString($_POST['hrse50_valor'] ?? 0);
        $hrse75_valor      = mainModel::cleanString($_POST['hrse75_valor'] ?? 0);
        $hrse100_valor     = mainModel::cleanString($_POST['hrse100_valor'] ?? 0);

        $usuario           = $_SESSION['colaborador_id_sd'] ?? 0;
        $estado            = 0; // SIN GENERAR
        $notas             = mainModel::cleanString($_POST['nomina_detalles_notas'] ?? '');
        $fecha_registro    = date("Y-m-d H:i:s");

        if($nomina_id === '' || $colaboradores_id === ''){
            return json_encode([
                "status"  => "error",
                "title"   => "Campos incompletos",
                "message" => "Selecciona nómina y empleado."
            ]);
        }

        $datos = [
            "nomina_id"         => $nomina_id,
            "colaboradores_id"  => $colaboradores_id,
            "salario_mensual"   => $salario_mensual,
            "dias_trabajados"   => $dias_trabajados,
            "hrse25"            => $hrse25,
            "hrse50"            => $hrse50,
            "hrse75"            => $hrse75,
            "hrse100"           => $hrse100,
            "retroactivo"       => $retroactivo,
            "bono"              => $bono,
            "otros_ingresos"    => $otros_ingresos,
            "deducciones"       => $deducciones,
            "prestamo"          => $prestamo,
            "ihss"              => $ihss,
            "rap"               => $rap,
            "isr"               => $isr,
            "vales"             => $vales,
            "incapacidad_ihss"  => $incapacidad_ihss,
            "neto_ingresos"     => $neto_ingresos,
            "neto_egresos"      => $neto_egresos,
            "neto"              => $neto,
            "usuario"           => $usuario,
            "estado"            => $estado,
            "notas"             => $notas,
            "fecha_registro"    => $fecha_registro,
            "hrse25_valor"      => $hrse25_valor,
            "hrse50_valor"      => $hrse50_valor,
            "hrse75_valor"      => $hrse75_valor,
            "hrse100_valor"     => $hrse100_valor,
            "salario"           => $salario
        ];

        if($this->valid_nomina_detalles_modelo($nomina_id, $colaboradores_id)->num_rows > 0){
            return json_encode([
                "status"  => "error",
                "title"   => "Duplicado",
                "message" => "Este empleado ya está registrado en la nómina."
            ]);
        }

        $nomina_detalles_id = $this->agregar_nomina_detalles_modelo($datos);
        if(!$nomina_detalles_id){
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo registrar el detalle de nómina."
            ]);
        }

        return json_encode([
            "status"              => "success",
            "title"               => "Detalle registrado",
            "message"             => "El detalle de nómina se registró correctamente.",
            "nomina_detalles_id"  => $nomina_detalles_id,
            "run"                 => "listar_nominas_detalles();getEmpleado();"
        ]);
    }

    /*========== Editar nómina (JSON) ==========*/
    public function edit_nomina_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        $nomina_id    = mainModel::cleanString($_POST['nomina_id'] ?? '');
        $fecha_inicio = mainModel::cleanString($_POST['nomina_fecha_inicio'] ?? '');
        $fecha_fin    = mainModel::cleanString($_POST['nomina_fecha_fin'] ?? '');
        $notas        = mainModel::cleanString($_POST['nomina_notas'] ?? '');

        if($nomina_id === '' || $fecha_inicio === '' || $fecha_fin === ''){
            return json_encode([
                "status"  => "error",
                "title"   => "Campos incompletos",
                "message" => "Selecciona la nómina y el rango de fechas."
            ]);
        }

        $datos = [
            "nomina_id"    => $nomina_id,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin"    => $fecha_fin,
            "notas"        => $notas,
        ];

        if(!$this->edit_nomina_modelo($datos)){
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo actualizar la nómina."
            ]);
        }

        return json_encode([
            "status" => "success",
            "title"  => "Nómina actualizada",
            "message"=> "La nómina se actualizó correctamente.",
            "run"    => "listar_nominas();getPagoPlanificado();getEmpresa();getTipoNomina();"
        ]);
    }

    /*========== Editar detalles nómina (JSON) ==========*/
    public function edit_nomina_detalles_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        $nomina_detalles_id = mainModel::cleanString($_POST['nomina_detalles_id'] ?? '');

        // INGRESOS
        $dias_trabajados = mainModel::cleanString($_POST['nominad_diast'] ?? 0);
        $hrse25          = mainModel::cleanString($_POST['nominad_horas25'] ?? 0);
        $hrse50          = mainModel::cleanString($_POST['nominad_horas50'] ?? 0);
        $hrse75          = mainModel::cleanString($_POST['nominad_horas75'] ?? 0);
        $hrse100         = mainModel::cleanString($_POST['nominad_horas100'] ?? 0);
        $retroactivo     = mainModel::cleanString($_POST['nominad_retroactivo'] ?? 0);
        $bono            = mainModel::cleanString($_POST['nominad_bono'] ?? 0);
        $otros_ingresos  = mainModel::cleanString($_POST['nominad_otros_ingresos'] ?? 0);

        // EGRESOS
        $deducciones      = mainModel::cleanString($_POST['nominad_deducciones'] ?? 0);
        $prestamo         = mainModel::cleanString($_POST['nominad_prestamo'] ?? 0);
        $ihss             = mainModel::cleanString($_POST['nominad_ihss'] ?? 0);
        $rap              = mainModel::cleanString($_POST['nominad_rap'] ?? 0);
        $isr              = mainModel::cleanString($_POST['nominad_isr'] ?? 0);
        $vales            = mainModel::cleanString($_POST['nominad_vale'] ?? 0);
        $incapacidad_ihss = mainModel::cleanString($_POST['nominad_incapacidad_ihss'] ?? 0);

        // RESUMEN
        $neto_ingresos = mainModel::cleanString($_POST['nominad_neto_ingreso'] ?? 0);
        $neto_egresos  = mainModel::cleanString($_POST['nominad_neto_egreso'] ?? 0);
        $neto          = mainModel::cleanString($_POST['nominad_neto'] ?? 0);

        $hrse25_valor  = mainModel::cleanString($_POST['hrse25_valor'] ?? 0);
        $hrse50_valor  = mainModel::cleanString($_POST['hrse50_valor'] ?? 0);
        $hrse75_valor  = mainModel::cleanString($_POST['hrse75_valor'] ?? 0);
        $hrse100_valor = mainModel::cleanString($_POST['hrse100_valor'] ?? 0);

        $notas          = mainModel::cleanString($_POST['nomina_detalles_notas'] ?? '');
        $fecha_registro = date("Y-m-d H:i:s");

        if($nomina_detalles_id === ''){
            return json_encode([
                "status"  => "error",
                "title"   => "Falta ID",
                "message" => "No se pudo identificar el detalle a editar."
            ]);
        }

        $datos = [
            "dias_trabajados"    => $dias_trabajados ?: 0,
            "hrse25"             => $hrse25 ?: 0,
            "hrse50"             => $hrse50 ?: 0,
            "hrse75"             => $hrse75 ?: 0,
            "hrse100"            => $hrse100 ?: 0,
            "retroactivo"        => $retroactivo ?: 0,
            "bono"               => $bono ?: 0,
            "otros_ingresos"     => $otros_ingresos ?: 0,
            "deducciones"        => $deducciones ?: 0,
            "prestamo"           => $prestamo ?: 0,
            "ihss"               => $ihss ?: 0,
            "rap"                => $rap ?: 0,
            "isr"                => $isr ?: 0,
            "vales"              => $vales ?: 0,
            "incapacidad_ihss"   => $incapacidad_ihss ?: 0,
            "neto_ingresos"      => $neto_ingresos ?: 0,
            "neto_egresos"       => $neto_egresos ?: 0,
            "neto"               => $neto ?: 0,
            "notas"              => $notas,
            "fecha_registro"     => $fecha_registro,
            "nomina_detalles_id" => $nomina_detalles_id,
            "hrse25_valor"       => $hrse25_valor ?: 0,
            "hrse50_valor"       => $hrse50_valor ?: 0,
            "hrse75_valor"       => $hrse75_valor ?: 0,
            "hrse100_valor"      => $hrse100_valor ?: 0,
        ];

        if(!$this->edit_nomina_detalles_modelo($datos)){
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo actualizar el detalle de nómina."
            ]);
        }

        return json_encode([
            "status" => "success",
            "title"  => "Detalle actualizado",
            "message"=> "El detalle de nómina se actualizó correctamente.",
            "run"    => "listar_nominas_detalles();getEmpleado();"
        ]);
    }

    /*========== Eliminar nómina (JSON) ==========*/
    public function delete_nomina_controlador(){
        // 1) Sesión
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        // 2) Entrada
        $nomina_id = mainModel::cleanString($_POST['nomina_id'] ?? '');
        if($nomina_id === '' || !ctype_digit($nomina_id)){
            return json_encode([
                "status"  => "error",
                "title"   => "Falta ID",
                "message" => "No se pudo identificar la nómina a eliminar."
            ]);
        }
        $nomina_id = (int)$nomina_id;

        // 3) Conexión
        try{
            $cn = method_exists('mainModel','staticConnection') ? mainModel::staticConnection() : (new mainModel())->connection();
            if(!($cn instanceof mysqli)) throw new Exception('Conexión inválida.');
        }catch(Throwable $e){
            return json_encode([
                "status"  => "error",
                "title"   => "Conexión",
                "message" => "No fue posible conectar con la base de datos: ".$e->getMessage()
            ]);
        }

        // 4) Obtener datos de la nómina (detalle/estado)
        $detalle = '';
        $estado_nomina = 0;
        $stmtN = $cn->prepare("SELECT detalle, estado FROM nomina WHERE nomina_id = ? LIMIT 1");
        $stmtN->bind_param("i", $nomina_id);
        $stmtN->execute();
        $stmtN->bind_result($detalle_db, $estado_db);
        if($stmtN->fetch()){
            $detalle = (string)$detalle_db;
            $estado_nomina = (int)$estado_db;
        }else{
            $stmtN->close();
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "Nómina #{$nomina_id} no encontrada."
            ]);
        }
        $stmtN->close();

        // 5) Regla: si nomina.estado == 1 → no se elimina
        if($estado_nomina == 1){
            return json_encode([
                "status"  => "error",
                "title"   => "No permitido",
                "message" => "La nómina {$detalle} está confirmada y no puede eliminarse."
            ]);
        }

        // 6) Regla: si existen detalles (confirmados o no) → no se elimina
        $count_detalles = 0; $count_confirmados = 0;
        $stmtC = $cn->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN estado=1 THEN 1 ELSE 0 END) AS confirmados FROM nomina_detalles WHERE nomina_id = ?");
        $stmtC->bind_param("i", $nomina_id);
        $stmtC->execute();
        $stmtC->bind_result($total_det, $total_conf);
        if($stmtC->fetch()){
            $count_detalles = (int)$total_det;
            $count_confirmados = (int)($total_conf ?? 0);
        }
        $stmtC->close();

        if($count_detalles > 0){
            $msgExtra = ($count_confirmados > 0)
                ? " Hay {$count_confirmados} detalle(s) confirmados."
                : " Existen empleados registrados en la nómina.";
            return json_encode([
                "status"  => "error",
                "title"   => "No se puede eliminar",
                "message" => "La nómina {$detalle} tiene detalles asociados.".$msgExtra
            ]);
        }

        // 7) Eliminar
        $stmtD = $cn->prepare("DELETE FROM nomina WHERE nomina_id = ? LIMIT 1");
        $stmtD->bind_param("i", $nomina_id);
        if(!$stmtD->execute() || $stmtD->affected_rows < 1){
            $stmtD->close();
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo eliminar la nómina {$detalle}."
            ]);
        }
        $stmtD->close();

        return json_encode([
            "status"  => "success",
            "title"   => "Nómina eliminada",
            "message" => "La nómina {$detalle} se eliminó correctamente.",
            "run"     => "listar_nominas();"
        ]);
    }

    /*========== Eliminar detalle nómina (JSON) ==========*/
    public function delete_nomina_detalles_controlador(){
        // 1) Sesión
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        // 2) Entrada
        $nomina_detalles_id = mainModel::cleanString($_POST['nomina_detalles_id'] ?? '');
        if($nomina_detalles_id === '' || !ctype_digit($nomina_detalles_id)){
            return json_encode([
                "status"  => "error",
                "title"   => "Falta ID",
                "message" => "No se pudo identificar el detalle a eliminar."
            ]);
        }
        $nomina_detalles_id = (int)$nomina_detalles_id;

        // 3) Conexión
        try{
            $cn = method_exists('mainModel','staticConnection') ? mainModel::staticConnection() : (new mainModel())->connection();
            if(!($cn instanceof mysqli)) throw new Exception('Conexión inválida.');
        }catch(Throwable $e){
            return json_encode([
                "status"  => "error",
                "title"   => "Conexión",
                "message" => "No fue posible conectar con la base de datos: ".$e->getMessage()
            ]);
        }

        // 4) Obtener detalle (nomina_id, estado del detalle)
        $nomina_id = 0; $estado_detalle = 0;
        $stmtD = $cn->prepare("SELECT nomina_id, estado FROM nomina_detalles WHERE nomina_detalles_id = ? LIMIT 1");
        $stmtD->bind_param("i", $nomina_detalles_id);
        $stmtD->execute();
        $stmtD->bind_result($nomina_id_db, $estado_det_db);
        if($stmtD->fetch()){
            $nomina_id = (int)$nomina_id_db;
            $estado_detalle = (int)$estado_det_db;
        }else{
            $stmtD->close();
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "Detalle de nómina no encontrado."
            ]);
        }
        $stmtD->close();

        // 5) Si el detalle está confirmado → no se elimina
        if($estado_detalle == 1){
            return json_encode([
                "status"  => "error",
                "title"   => "No permitido",
                "message" => "El detalle de nómina está confirmado y no puede eliminarse."
            ]);
        }

        // 6) Verificar estado de la nómina padre
        $estado_nomina = 0;
        $stmtN = $cn->prepare("SELECT estado FROM nomina WHERE nomina_id = ? LIMIT 1");
        $stmtN->bind_param("i", $nomina_id);
        $stmtN->execute();
        $stmtN->bind_result($estado_nomina_db);
        if($stmtN->fetch()){
            $estado_nomina = (int)$estado_nomina_db;
        }else{
            $stmtN->close();
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "Nómina asociada no encontrada."
            ]);
        }
        $stmtN->close();

        // 7) Si la nómina está confirmada → no se elimina el detalle
        if($estado_nomina == 1){
            return json_encode([
                "status"  => "error",
                "title"   => "No permitido",
                "message" => "La nómina está confirmada. No es posible eliminar detalles."
            ]);
        }

        // 8) Eliminar detalle
        $stmtDel = $cn->prepare("DELETE FROM nomina_detalles WHERE nomina_detalles_id = ? LIMIT 1");
        $stmtDel->bind_param("i", $nomina_detalles_id);
        if(!$stmtDel->execute() || $stmtDel->affected_rows < 1){
            $stmtDel->close();
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo eliminar el detalle de la nómina #{$nomina_id}."
            ]);
        }
        $stmtDel->close();

        return json_encode([
            "status"  => "success",
            "title"   => "Detalle eliminado",
            "message" => "El detalle de nómina se eliminó correctamente.",
            "run"     => "listar_nominas_detalles();"
        ]);
    }

    /*========== Agregar vale (JSON) ==========*/
    public function agregar_vale_controlador(){
        $validacion = mainModel::validarSesion();
        if($validacion['error']){
            return json_encode([
                "status"   => "unauthorized",
                "title"    => "Error de sesión",
                "message"  => $validacion['mensaje'],
                "redirect" => $validacion['redireccion'] ?? null
            ]);
        }

        $fecha          = mainModel::cleanString($_POST['vale_fecha'] ?? '');
        $empleado_id    = mainModel::cleanString($_POST['vale_empleado'] ?? '');
        $monto          = mainModel::cleanString($_POST['vale_monto'] ?? '');
        $nota           = mainModel::cleanString($_POST['vale_notas'] ?? '');
        $usuario        = $_SESSION['colaborador_id_sd'] ?? 0;
        $estado         = 0;
        $empresa_id     = mainModel::cleanString($_POST['nomina_empresa_id'] ?? ($_SESSION['empresa_id'] ?? 0));
        $fecha_registro = date("Y-m-d H:i:s");

        if($fecha==='' || $empleado_id==='' || $monto===''){
            return json_encode([
                "status"  => "error",
                "title"   => "Campos incompletos",
                "message" => "Fecha, empleado y monto son obligatorios."
            ]);
        }

        $datos = [
            "nomina_id"       => 0, // si no aplica, va 0
            "colaboradores_id"=> $empleado_id,
            "monto"           => $monto,
            "fecha"           => $fecha,
            "nota"            => $nota,
            "usuario"         => $usuario,
            "estado"          => $estado,
            "empresa_id"      => $empresa_id,
            "fecha_registro"  => $fecha_registro
        ];

        // opcional: validar vale abierto
        $dup = $this->valid_vale_modelo($empleado_id);
        if ($dup && $dup->num_rows > 0){
            return json_encode([
                "status"  => "error",
                "title"   => "Vale pendiente",
                "message" => "El empleado ya tiene un vale sin cancelar."
            ]);
        }

        $vale_id = $this->agregar_vale_modelo($datos);
        if(!$vale_id){
            return json_encode([
                "status"  => "error",
                "title"   => "Error",
                "message" => "No se pudo registrar el vale."
            ]);
        }

        return json_encode([
            "status"  => "success",
            "title"   => "Vale registrado",
            "message" => "El vale se registró correctamente.",
            "vale_id" => $vale_id,
            "run"     => "listar_vales();"
        ]);
    }
}