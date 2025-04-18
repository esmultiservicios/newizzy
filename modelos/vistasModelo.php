<?php
    $peticionAjax = false;
    
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";    
    }

    class vistasModelo extends mainModel{
        
        // Método para obtener la lista blanca desde la base de datos
		protected function obtenerListaBlanca($nombre_config) {
			$conexion = $this->connection(); // Obtener la conexión a la base de datos
		
			// Consulta para obtener los datos de la tabla 'config_lista_blanca'
			$query = "SELECT modulos FROM config_lista_blanca WHERE nombre_config = ?";
			
			// Preparamos la consulta
			if ($stmt = $conexion->prepare($query)) {
				$stmt->bind_param("s", $nombre_config); // Enlazamos el parámetro para evitar inyección SQL
				$stmt->execute(); // Ejecutamos la consulta
		
				// Obtenemos el resultado de la consulta
				$stmt->bind_result($modulosJson);
				$stmt->fetch();
		
				// Convertimos el JSON de 'modulos' a un array PHP
				$stmt->close(); // Cerrar el statement al final, antes de retornar
				if ($modulosJson) {
					return json_decode($modulosJson, true); // Retornamos el array de módulos
				} else {
					return null; // Si no encontramos el registro, retornamos null
				}
			} else {
				// Si ocurre algún error con la consulta, podemos manejarlo aquí
				return false;
			}
		}        

        // Método para obtener las vistas y validarlas con la lista blanca
        protected function getVistasModelo($vistas, $nombre_config = 'configuracion_principal') {
            $listaBlanca = $this->obtenerListaBlanca($nombre_config);
        
            // Verificamos si la vista está en la lista blanca
            if ($listaBlanca && in_array($vistas, $listaBlanca)) {
                // Verificamos si el archivo de vista existe
                if(is_file("./vistas/contenido/".$vistas."-view.php")){
                    // Determinamos el tipo de vista y validamos permisos
                    $tipoVista = $this->determinarTipoVista($vistas);
                    
                    switch($tipoVista) {
                        case 'menu':
                            $result = mainModel::getMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);
                            break;
                        case 'submenu':
                            $result = mainModel::getSubMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);
                            break;
                        case 'submenu1':
                            $result = mainModel::getSubMenu1AccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);
                            break;
                        default:
                            // Si no es ninguno de los tipos conocidos, permitimos el acceso
                            return "./vistas/contenido/".$vistas."-view.php";
                    }
        
                    if(isset($result) && $result->num_rows == 0){
                        return "./vistas/contenido/401-view.php";
                    }
                    
                    return "./vistas/contenido/".$vistas."-view.php";
                } else {
                    return "login";
                }
            } elseif ($vistas == "login" || $vistas == "index") {
                return "login";
            } else {
                return "404";
            }
        }
        
        protected function determinarTipoVista($vista) {
            // Primero verificamos si es un menú principal
            $consultaMenu = mainModel::ejecutar_consulta_simple("SELECT menu_id FROM menu WHERE name = '$vista'");
            if($consultaMenu && $consultaMenu->num_rows > 0) {
                return 'menu';
            }
            
            // Luego verificamos si es un submenú de nivel 1
            $consultaSubmenu = mainModel::ejecutar_consulta_simple("SELECT submenu_id FROM submenu WHERE name = '$vista'");
            if($consultaSubmenu && $consultaSubmenu->num_rows > 0) {
                return 'submenu';
            }
            
            // Finalmente verificamos si es un submenú de nivel 2
            $consultaSubmenu1 = mainModel::ejecutar_consulta_simple("SELECT submenu1_id FROM submenu1 WHERE name = '$vista'");
            if($consultaSubmenu1 && $consultaSubmenu1->num_rows > 0) {
                return 'submenu1';
            }
            
            // Si no coincide con ninguno, retornamos null
            return null;
        }
    }