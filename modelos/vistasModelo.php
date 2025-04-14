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
        protected function getVistasModelo($vistas, $nombre_config = 'configuracion_principal'){
            $listaBlanca = $this->obtenerListaBlanca($nombre_config); // Obtenemos la lista blanca desde la base de datos

            if ($listaBlanca && in_array($vistas, $listaBlanca)) { // Verificamos si la vista está en la lista blanca
                if(is_file("./vistas/contenido/".$vistas."-view.php")){
                    // VERIFICAMOS QUE EL USUARIO TENGA PERMISOS AL MENU A CONSULTAR
                    if($vistas == 'dashboard'){                        
                        $resultVarios = mainModel::getMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

                        if($resultVarios->num_rows == 0){
                            $vistas = "401";
                        }
                    }else if($vistas == 'historialAccesos' || $vistas == 'bitacora' || $vistas == 'reporteVentas' || $vistas == 'reporteCotizacion' || $vistas == 'cobrarClientes' || $vistas == 'reporteCompras' || $vistas == 'pagarProveedores'){
                        $resultVarios = mainModel::getSubMenu1AccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

                        if($resultVarios->num_rows == 0){
                            $vistas = "401";
                        }
                    }else{
                        $resultVarios = mainModel::getSubMenuAccesoLoginConsulta($_SESSION['privilegio_sd'], $vistas);

                        if($resultVarios->num_rows == 0){
                            $vistas = "401";
                        }
                    }
                    
                    $contenido = "./vistas/contenido/".$vistas."-view.php";
                } else {
                    $contenido = "login";
                }
            } elseif ($vistas == "login" || $vistas == "index") {
                $contenido = "login";
            } else {
                $contenido = "404";
            }
            return $contenido;
        }
    }