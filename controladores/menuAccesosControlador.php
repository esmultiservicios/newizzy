<?php
if($peticionAjax){
    require_once "../modelos/menuAccesosModelo.php";
}else{
    require_once "./modelos/menuAccesosModelo.php";
}

class menuAccesosControlador extends menuAccesosModelo{
    public function agregar_MenuAccesos_controlador(){
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
        
        $privilegio_id = $_POST['privilegio_id_accesos'];
        $menus_seleccionados = $_POST['menus'];
        $estado = 1;
        $fecha_registro = date("Y-m-d H:i:s");        
        
        foreach ($menus_seleccionados as $menu_id) {
            $datos = [
                "menu_id" => $menu_id,
                "privilegio_id" => $privilegio_id,
                "estado" => $estado,
                "fecha_registro" => $fecha_registro,               
            ];
    
            $resultVarios = menuAccesosModelo::valid_menuAccesos_modelo($datos);
    
            if ($resultVarios->num_rows == 0) {            
                $query = menuAccesosModelo::agregar_menuAccesos_modelo($datos);
    
                if (!$query) {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "No se pudo registrar el acceso al menú",
                        "type" => "error"
                    ]);
                }
            } else {
                return mainModel::showNotification([
                    "title" => "Registro duplicado",
                    "text" => "Este acceso al menú ya existe",
                    "type" => "error"
                ]);               
            }
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Accesos',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Registro",
            "observacion" => "Se registraron accesos al menú para el privilegio ID: $privilegio_id",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Accesos al menú registrados correctamente",           
            "form" => "formMenuAccesos",
            "funcion" => "listar_menuaccesos();getMenusPrivilegios();"
        ]);         
    }            

    public function agregar_SubMenuAccesos_controlador(){
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
        
        $privilegio_id = $_POST['privilegio_id_accesos'];
        $submenu_ids = $_POST['submenus'];
        $estado = 1;
        $fecha_registro = date("Y-m-d H:i:s");        
        
        foreach ($submenu_ids as $submenu_id) {
            $datos = [
                "submenu_id" => $submenu_id,
                "privilegio_id" => $privilegio_id,
                "estado" => $estado,
                "fecha_registro" => $fecha_registro,               
            ];
    
            $resultVarios = menuAccesosModelo::valid_subMenuAccesos_modelo($datos);
    
            if ($resultVarios->num_rows == 0) {            
                $query = menuAccesosModelo::agregar_subMenuAccesos_modelo($datos);
    
                if (!$query) {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "No se pudo registrar el acceso al submenú",
                        "type" => "error"
                    ]);
                }
            } else {
                return mainModel::showNotification([
                    "title" => "Registro duplicado",
                    "text" => "Este acceso al submenú ya existe",
                    "type" => "error"
                ]);               
            }
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Accesos',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Registro",
            "observacion" => "Se registraron accesos al submenú para el privilegio ID: $privilegio_id",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Accesos al submenú registrados correctamente",           
            "form" => "formSubMenuAccesos",
            "funcion" => "listar_submenuaccesos();getSubMenusPrivilegios();"
        ]);         
    }        
        
    public function agregar_SubMenu1Accesos_controlador(){
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
        
        $privilegio_id = $_POST['privilegio_id_accesos'];
        $privilegio_nombre = $_POST['privilegio'];
        $submenus_ids = $_POST['submenus'];
        $estado = 1;
        $fecha_registro = date("Y-m-d H:i:s");        
        
        foreach ($submenus_ids as $submenu_id) {
            $datos = [
                "submenus_id" => $submenu_id,
                "privilegio_id" => $privilegio_id,
                "estado" => $estado,
                "fecha_registro" => $fecha_registro,               
            ];
    
            $resultVarios = menuAccesosModelo::valid_sub1MenuAccesos_modelo($datos);
    
            if ($resultVarios->num_rows == 0) {            
                $query = menuAccesosModelo::agregar_subMenu1Accesos_modelo($datos);
    
                if (!$query) {
                    return mainModel::showNotification([
                        "title" => "Error",
                        "text" => "No se pudo registrar el acceso al submenú nivel 1",
                        "type" => "error"
                    ]);
                }
            } else {
                return mainModel::showNotification([
                    "title" => "Registro duplicado",
                    "text" => "Este acceso al submenú nivel 1 ya existe",
                    "type" => "error"
                ]);               
            }
        }
        
        // Registrar en historial
        mainModel::guardarHistorial([
            "modulo" => 'Accesos',
            "colaboradores_id" => $_SESSION['colaborador_id_sd'],
            "status" => "Registro",
            "observacion" => "Se registraron accesos al submenú nivel 1 para el privilegio: $privilegio_nombre",
            "fecha_registro" => date("Y-m-d H:i:s")
        ]);
        
        return mainModel::showNotification([
            "type" => "success",
            "title" => "Registro exitoso",
            "text" => "Accesos al submenú nivel 1 registrados correctamente",           
            "form" => "formSubMenu1Accesos",
            "funcion" => "listar_submenu1accesos();getSubMenusConsulta();"
        ]);         
    }
            
}