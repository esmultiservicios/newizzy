<?php
if($peticionAjax){
    require_once "../modelos/contratoModelo.php";
}else{
    require_once "./modelos/contratoModelo.php";
}

class contratoControlador extends contratoModelo{
    public function agregar_contrato_controlador(){
        // Configurar cabecera JSON primero
        header('Content-Type: application/json');
        
        // Validar sesión primero
        $validacion = mainModel::validarSesion();
        if($validacion['error']) {
            return json_encode([
                "status" => "error",
                "title" => "Error de sesión",
                "message" => $validacion['mensaje'],
                "redirect" => $validacion['redireccion']
            ]);
        }

        // Validar campos requeridos
        $requiredFields = [
            'contrato_colaborador_id' => 'Empleado',
            'contrato_tipo_contrato_id' => 'Tipo Contrato',
            'contrato_pago_planificado_id' => 'Pago Planificado',
            'contrato_tipo_empleado_id' => 'Tipo Empleado',
            'contrato_salario_mensual' => 'Salario Mensual',
            'contrato_fecha_inicio' => 'Fecha Inicio'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $name) {
            if (!isset($_POST[$field]) || $_POST[$field] === '') {
                $missingFields[] = $name;
            }
        }

        if (!empty($missingFields)) {
            return json_encode([
                "status" => "error",
                "title" => "Error de validación",
                "message" => "Faltan los siguientes campos obligatorios: " . implode(", ", $missingFields),
                "missing_fields" => $missingFields
            ]);
        }

        // Procesar datos del formulario
        $colaborador_id = mainModel::cleanString($_POST['contrato_colaborador_id']);
        $tipo_contrato_id = mainModel::cleanString($_POST['contrato_tipo_contrato_id']);
        $pago_planificado_id = mainModel::cleanString($_POST['contrato_pago_planificado_id']);
        $tipo_empleado_id = mainModel::cleanString($_POST['contrato_tipo_empleado_id']);
        $salario_mensual = mainModel::cleanString($_POST['contrato_salario_mensual']);
        $salario = mainModel::cleanString($_POST['contrato_salario']);
        $fecha_inicio = mainModel::cleanString($_POST['contrato_fecha_inicio']);
        $fecha_fin = mainModel::cleanString($_POST['contrato_fecha_fin']);
        $notas = mainModel::cleanString($_POST['contrato_notas']);
        $usuario = $_SESSION['colaborador_id_sd'];
        $estado = 1;
        $fecha_registro = date("Y-m-d H:i:s");    
        $semanal = ($_POST['contrato_pago_planificado_id'] == 1) ? 1 : 0;
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "tipo_contrato_id" => $tipo_contrato_id,
            "pago_planificado_id" => $pago_planificado_id,
            "tipo_empleado_id" => $tipo_empleado_id,
            "salario_mensual" => $salario_mensual,
            "salario" => $salario,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "notas" => $notas,
            "usuario" => $usuario,                
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,                
            "semanal" => $semanal,
        ];
        
        // Validar si el colaborador ya tiene contrato activo
        if(contratoModelo::valid_contrato_modelo($colaborador_id)->num_rows > 0){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "El colaborador ya tiene un contrato activo"
            ]);               
        }
        
        // Validar límites del plan
        $mainModel = new mainModel();
        $planConfig = $mainModel->getPlanConfiguracionMainModel();
        
        if (!empty($planConfig)) {
            $limiteContratos = (int)($planConfig['contratos'] ?? 0);
            
            if ($limiteContratos === 0) {
                return json_encode([
                    "status" => "error",
                    "title" => "Acceso restringido",
                    "message" => "Su plan actual no permite registrar contratos."
                ]);
            }
            
            $totalRegistrados = (int)contratoModelo::getTotalContratosRegistrados();
            
            if ($totalRegistrados >= $limiteContratos) {
                return json_encode([
                    "status" => "error",
                    "title" => "Límite alcanzado",
                    "message" => "Límite de contratos alcanzado (Máximo: $limiteContratos). Actualiza tu plan."
                ]);
            }
        }    

        // Intentar registrar el contrato
        if(!contratoModelo::agregar_contrato_modelo($datos)){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo registrar el contrato"
            ]);
        }
                    
        // Éxito
        return json_encode([
            "status" => "success",
            "title" => "Registro exitoso",
            "message" => "Contrato registrado correctamente",
            "funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();",
            "clearForm" => true
        ]);
    }
    
    public function edit_contrato_controlador(){
        // Configurar cabecera JSON primero
        header('Content-Type: application/json');
        
        // Validar campos requeridos
        $requiredFields = [
            'contrato_id' => 'ID del Contrato',
            'contrato_colaborador_id' => 'Empleado',
            'contrato_salario' => 'Salario',
            'contrato_fecha_inicio' => 'Fecha Inicio'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field => $name) {
            if (!isset($_POST[$field])) {
                $missingFields[] = $name;
            } elseif ($_POST[$field] === '') {
                $missingFields[] = $name;
            }
        }

        if (!empty($missingFields)) {
            return json_encode([
                "status" => "error",
                "title" => "Error de validación",
                "message" => "Faltan los siguientes campos obligatorios: " . implode(", ", $missingFields),
                "missing_fields" => $missingFields
            ]);
        }

        // Procesar datos del formulario
        $contrato_id = mainModel::cleanString($_POST['contrato_id']);
        $salario = mainModel::cleanString($_POST['contrato_salario']);
        $fecha_inicio = mainModel::cleanString($_POST['contrato_fecha_inicio']);
        $fecha_fin = mainModel::cleanString($_POST['contrato_fecha_fin']);
        $notas = mainModel::cleanString($_POST['contrato_notas']);
        $estado = isset($_POST['contrato_activo']) ? $_POST['contrato_activo'] : 2;
        
        $datos = [
            "contrato_id" => $contrato_id,
            "salario" => $salario,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "notas" => $notas,
            "estado" => $estado,                            
        ];    
        
        // Intentar actualizar el contrato
        if(!contratoModelo::edit_contrato_modelo($datos)){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo actualizar el contrato"
            ]);
        }
                    
        // Éxito
        return json_encode([
            "status" => "success",
            "title" => "Actualización exitosa",
            "message" => "Contrato actualizado correctamente",
            "funcion" => "listar_contratos();getTipoContrato();getPagoPlanificado();getTipoEmpleado();getEmpleado();"
        ]);
    }
    
    public function delete_contrato_controlador(){
        // Configurar cabecera JSON primero
        header('Content-Type: application/json');
        
        // Validar campo requerido
        if (!isset($_POST['contrato_id']) || $_POST['contrato_id'] === '') {
            return json_encode([
                "status" => "error",
                "title" => "Error de validación",
                "message" => "Falta el ID del contrato a eliminar"
            ]);
        }

        $contrato_id = mainModel::cleanString($_POST['contrato_id']);
        
        // Verificar existencia del contrato
        $campos = ['contrato_id'];
        $tabla = "contrato";
        $condicion = "contrato_id = {$contrato_id}";

        $contrato = mainModel::consultar_tabla($tabla, $campos, $condicion);
        
        if (empty($contrato)) {
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Contrato no encontrado"
            ]);
        }
        
        $contratoConsulta = $contrato[0]['contrato_id'] ?? '';

        // Validar si el contrato tiene información relacionada
        if(contratoModelo::valid_contrato_nomina_modelo($contrato_id)->num_rows > 0){
            return json_encode([
                "status" => "error",
                "title" => "No se puede eliminar",
                "message" => "El registro {$contratoConsulta} tiene información almacenada"
            ]);                
        }

        // Intentar eliminar el contrato
        if(!contratoModelo::delete_contrato_modelo($contrato_id)){
            return json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el registro"
            ]);
        }

        // Éxito
        return json_encode([
            "status" => "success",
            "title" => "Eliminación exitosa",
            "message" => "Contrato eliminado correctamente",
            "funcion" => "listar_contratos();"
        ]);
    }
}