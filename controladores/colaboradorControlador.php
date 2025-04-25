<?php
if($peticionAjax){
    require_once "../modelos/colaboradorModelo.php";
}else{
    require_once "./modelos/colaboradorModelo.php";
}

class colaboradorControlador extends colaboradorModelo{
    public function agregar_colaborador_controlador(){
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
        
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);      
        $identidad = mainModel::cleanString($_POST['identidad_colaborador']);    
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        $puesto = mainModel::cleanString($_POST['puesto_colaborador']);            
        $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
        $fecha_egreso = mainModel::cleanString($_POST['fecha_egreso_colaborador']);
        $empresa_id = $_SESSION['empresa_id_sd'];
    
        $fecha_registro = date("Y-m-d H:i:s");    
        $estado = 1;
    
        // Si la identidad está vacía, generamos una única
        if (empty($identidad) || $identidad == "0") {
            do {
                $identidad = "C-" . rand(10000000, 99999999); // Puedes ajustar el formato
            } while (colaboradorModelo::valid_colaborador_modelo($identidad)->num_rows > 0);
        }
    
        $datos = [
            "nombre" => $nombre,              
            "identidad" => $identidad,
            "telefono" => $telefono,                
            "puesto" => $puesto,                
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,    
            "empresa" => $empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso                
        ];
    
        // Validamos si existe el registro
        if (colaboradorModelo::valid_colaborador_modelo($identidad)->num_rows > 0){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "No se puede registrar",
                "message" => "La identidad {$identidad} del colaborador {$nombre}, ya existe"
            ]);
            exit();                
        }
    
        if (!colaboradorModelo::agregar_colaborador_modelo($datos)) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se puede registrar el colaborador {$nombre}"
            ]);
            exit();
        }
    
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Colaborador {$nombre} registrado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();listar_colaboradores_buscar_factura();listar_colaboradores_buscar_cotizacion();"
        ]);        
    }
    
    
    public function editar_colaborador_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);             
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
        $fecha_egreso = mainModel::cleanString($_POST['fecha_egreso_colaborador']);
        $identidad = mainModel::cleanString($_POST['identidad_colaborador']);
        $colaborador_empresa_id = mainModel::cleanString($_POST['colaborador_empresa_id']);

        if(isset($_POST['puesto_colaborador'])){
            if($_POST['puesto_colaborador'] == ""){
                $puesto = 0;
            }else{
                $puesto = mainModel::cleanStringConverterCase($_POST['puesto_colaborador']);
            }
        }else{
            $puesto = 0;
        }           
        
        $estado = isset($_POST['colaboradores_activo']) ? 1 : 0;
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "telefono" => $telefono,                
            "puesto" => $puesto,
            "estado" => $estado,
            "empresa_id" => $colaborador_empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso        
        ];

        if(!colaboradorModelo::editar_colaborador_modelo($datos)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se puede editar el colaborador {$nombre}"
            ]);
            exit();
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Colaborador {$nombre} registrado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
        ]); 
    }
    
    public function editar_colaborador_perfil_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);               
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        
        $fecha_registro = date("Y-m-d H:i:s");    
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "telefono" => $telefono,
        ];

        if(!colaboradorModelo::editar_colaborador_perfil_modelo($datos)){
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "no se puede editar el colaborador {$nombre}"
            ]);
            exit();
        }

        return mainModel::showNotification([
            "type" => "success",
            "title" => "Actualización exitosa",
            "text" => "Colaborador {$nombre} editado correctamente",
            "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
        ]);         
    }        
    
    public function delete_colaborador_controlador() {
        $colaborador_id = $_POST['colaborador_id'];        
        
        // Validar si el colaborador existe
        $result_valid = colaboradorModelo::valid_colaborador_bitacora($colaborador_id);
        
        if (empty($result_valid)) {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "Colaborador no encontrado"
            ]);
            exit();
        }
    
        // Verificar si tiene registros asociados
        if($result_valid->num_rows > 0) {
            echo json_encode([
                "status" => "error",
                "title" => "Registro con información asociada",
                "message" => "No se puede eliminar porque tiene registros en bitácora"
            ]);
            exit();                
        }
        
        // Intentar eliminar
        $query = colaboradorModelo::delete_colaborador_modelo($colaborador_id);
                                
        if($query) {
            echo json_encode([
                "status" => "success",
                "title" => "Eliminado",
                "message" => "Colaborador eliminado correctamente",                    
                "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el colaborador"                    
            ]);
        }
        exit();
    }
}