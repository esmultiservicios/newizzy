<?php
    if($peticionAjax){
        require_once "../modelos/menuAccesosModelo.php";
    }else{
        require_once "./modelos/menuAccesosModelo.php";
    }
	
	class menuAccesosControlador extends menuAccesosModelo{
		public function agregar_MenuAccesos_controlador(){
			$privilegio_id = $_POST['privilegio_id_accesos'];
			$privilegio_nombre = $_POST['privilegio'];
			$menus_seleccionados = $_POST['menus'];
			$estado = 1;
			$fecha_registro = date("Y-m-d H:i:s");        
			$alert = array();
		
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
						$alert = [
							"alert" => "simple",
							"title" => "Ocurrió un error inesperado",
							"text" => "No hemos podido procesar su solicitud",
							"type" => "error",
							"btn-class" => "btn-danger",                  
						];  
						break;  // Salir del bucle si hay un error
					}
				} else {
					$alert = [
						"alert" => "simple",
						"title" => "Registro ya existe",
						"text" => "Lo sentimos, este registro ya existe",
						"type" => "error",    
						"btn-class" => "btn-danger",                       
					];                  
					break;  // Salir del bucle si hay un error
				}
			}
		
			// Verificar si hay errores después del bucle
			if (empty($alert)) {
				// No hay errores, asignar mensaje de éxito
				$alert = [
					"alert" => "clear",
					"title" => "Registro almacenado",
					"text" => "El registro se ha almacenado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formMenuAccesos",
					"id" => "proceso_privilegios",
					"valor" => "Registro",	
					"funcion" => "listar_menuaccesos();getMenusPrivilegios();",
					"modal" => "",
				];
			}
		
			// Mostrar el mensaje de alerta
			return mainModel::sweetAlert($alert);         
		}			

		public function agregar_SubMenuAccesos_controlador(){
			$privilegio_id = $_POST['privilegio_id_accesos'];
			$privilegio_nombre = $_POST['privilegio'];
			$submenu_ids = $_POST['submenus'];
			$estado = 1;
			$fecha_registro = date("Y-m-d H:i:s");        
			$alert = array();
		
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
						$alert = [
							"alert" => "simple",
							"title" => "Ocurrió un error inesperado",
							"text" => "No hemos podido procesar su solicitud",
							"type" => "error",
							"btn-class" => "btn-danger",                  
						];  
						break;  // Salir del bucle si hay un error
					}
				} else {
					$alert = [
						"alert" => "simple",
						"title" => "Registro ya existe",
						"text" => "Lo sentimos, este registro ya existe",
						"type" => "error",    
						"btn-class" => "btn-danger",                       
					];                  
					break;  // Salir del bucle si hay un error
				}
			}
		
			// Verificar si hay errores después del bucle
			if (empty($alert)) {
				// No hay errores, asignar mensaje de éxito
				$alert = [
					"alert" => "clear",
					"title" => "Registro almacenado",
					"text" => "El registro se ha almacenado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formSubMenuAccesos",
					"id" => "proceso_privilegios",
					"valor" => "Registro",	
					"funcion" => "listar_submenuaccesos();getSubMenusPrivilegios();",
					"modal" => "",
				];						
			}
		
			// Mostrar el mensaje de alerta
			return mainModel::sweetAlert($alert);         
		}		
		
		public function agregar_SubMenu1Accesos_controlador(){
			$privilegio_id = $_POST['privilegio_id_accesos'];
			$privilegio_nombre = $_POST['privilegio'];
			$submenus_ids = $_POST['submenus'];
			$estado = 1;
			$fecha_registro = date("Y-m-d H:i:s");        
			$alert = array();
		
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
						$alert = [
							"alert" => "simple",
							"title" => "Ocurrió un error inesperado",
							"text" => "No hemos podido procesar su solicitud",
							"type" => "error",
							"btn-class" => "btn-danger",                  
						];  
						break;  // Salir del bucle si hay un error
					}
				} else {
					$alert = [
						"alert" => "simple",
						"title" => "Registro ya existe",
						"text" => "Lo sentimos, este registro ya existe",
						"type" => "error",    
						"btn-class" => "btn-danger",                       
					];                  
					break;  // Salir del bucle si hay un error
				}
			}
		
			// Verificar si hay errores después del bucle
			if (empty($alert)) {
				// No hay errores, asignar mensaje de éxito
				$alert = [
					"alert" => "clear",
					"title" => "Registro almacenado",
					"text" => "El registro se ha almacenado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formSubMenu1Accesos",
					"id" => "proceso_privilegios",
					"valor" => "Registro",	
					"funcion" => "listar_submenu1accesos();getSubMenusConsulta();",
					"modal" => "",
				];
			}
		
			// Mostrar el mensaje de alerta
			return mainModel::sweetAlert($alert);         
		}
				
	}