<?php
if($peticionAjax){
    require_once "../modelos/colaboradorModelo.php";
}else{
    require_once "./modelos/colaboradorModelo.php";
}

class colaboradorControlador extends colaboradorModelo{
    public function agregar_colaborador_controlador(){
        if(!isset($_SESSION['user_sd'])){ 
            session_start(['name'=>'SD']); 
        }
        
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);
        $apellido = mainModel::cleanStringConverterCase($_POST['apellido_colaborador']);            
        $identidad = mainModel::cleanString($_POST['identidad_colaborador']);    
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        $puesto = mainModel::cleanString($_POST['puesto_colaborador']);            
        $fecha_ingreso = mainModel::cleanString($_POST['fecha_ingreso_colaborador']);
        $fecha_egreso = mainModel::cleanString($_POST['fecha_egreso_colaborador']);
        $empresa_id = $_SESSION['empresa_id_sd'];

        $fecha_registro = date("Y-m-d H:i:s");    
        $estado = 1;
        
        $datos = [
            "nombre" => $nombre,
            "apellido" => $apellido,                
            "identidad" => $identidad,
            "telefono" => $telefono,                
            "puesto" => $puesto,                
            "estado" => $estado,
            "fecha_registro" => $fecha_registro,    
            "empresa" => $empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso                
        ];

        $result = colaboradorModelo::valid_colaborador_modelo($identidad);
        
        if($result->num_rows==0){
            $query = colaboradorModelo::agregar_colaborador_modelo($datos);
            
            if($query){
                $alert = [
                    "type" => "success",
                    "title" => "Registro almacenado",
                    "text" => "El registro se ha almacenado correctamente",                    
                    "form" => "formColaboradores",
                    "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();listar_colaboradores_buscar_factura();listar_colaboradores_buscar_cotizacion();"                    
                ];
            }else{
                $alert = [
                    "type" => "error",
                    "title" => "Ocurrió un error inesperado",
                    "text" => "No hemos podido procesar su solicitud"                    
                ];
            }                
        }else{
            $alert = [
                "type" => "error",
                "title" => "Registro ya existe",
                "text" => "Lo sentimos, este registro ya existe"
            ];
        }
        
        return mainModel::showNotification($alert);
    }
    
    public function editar_colaborador_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);
        $apellido = mainModel::cleanStringConverterCase($_POST['apellido_colaborador']);                
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
        
        $fecha_registro = date("Y-m-d H:i:s");    
        
        if (isset($_POST['colaboradores_activo'])){
            $estado = $_POST['colaboradores_activo'];
        }else{
            $estado = 2;
        }
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "telefono" => $telefono,                
            "puesto" => $puesto,                
            "estado" => $estado,
            "empresa_id" => $colaborador_empresa_id,
            "fecha_ingreso" => $fecha_ingreso,    
            "fecha_egreso" => $fecha_egreso        
        ];

        $query = colaboradorModelo::editar_colaborador_modelo($datos);
        
        if($query){    
            if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
                $updateDBMainUsers = "UPDATE colaboradores 
                    SET 
                        estado = '$estado'
                    WHERE nombre = '$nombre' AND apellido = '$apellido' AND identidad = '$identidad'";
                
                mainModel::connectionLogin()->query($updateDBMainUsers);
            }

            $alert = [
                "type" => "success",
                "title" => "Registro modificado",
                "text" => "El registro se ha modificado correctamente",                
                "form" => "formColaboradores",
                "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
            ];
        }else{
            $alert = [
                "type" => "error",
                "title" => "Ocurrió un error inesperado",
                "text" => "No hemos podido procesar su solicitud"                
            ];
        }
        
        return mainModel::showNotification($alert);
    }
    
    public function editar_colaborador_perfil_controlador(){
        $colaborador_id = mainModel::cleanStringConverterCase($_POST['colaborador_id']);
        $nombre = mainModel::cleanStringConverterCase($_POST['nombre_colaborador']);
        $apellido = mainModel::cleanStringConverterCase($_POST['apellido_colaborador']);                
        $telefono = mainModel::cleanString($_POST['telefono_colaborador']);                
        
        $fecha_registro = date("Y-m-d H:i:s");    
        
        $datos = [
            "colaborador_id" => $colaborador_id,
            "nombre" => $nombre,
            "apellido" => $apellido,
            "telefono" => $telefono,
        ];

        $query = colaboradorModelo::editar_colaborador_perfil_modelo($datos);
        
        if($query){                
            $alert = [
				"type" => "success",
                "title" => "Registro modificado",
                "text" => "El registro se ha modificado correctamente",                
                "funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();"
            ];
        }else{
            $alert = [
                "type" => "error",
                "title" => "Ocurrió un error inesperado",
                "text" => "No hemos podido procesar su solicitud"
            ];
        }
        
        return mainModel::showNotification($alert);
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