<?php
    if($peticionAjax){
        require_once "../modelos/proveedoresModelo.php";
    }else{
        require_once "./modelos/proveedoresModelo.php";
    }
	
	class proveedoresControlador extends proveedoresModelo{
		public function agregar_proveedores_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			$nombre = mainModel::cleanString($_POST['nombre_proveedores']);
			$rtn = mainModel::cleanString($_POST['rtn_proveedores']);
			$fecha = mainModel::cleanString($_POST['fecha_proveedores']);			
			$departamento_id = isset($_POST['departamento_proveedores']) ? intval($_POST['departamento_proveedores']) : 0;
			$municipio_id = isset($_POST['municipio_proveedores']) ? intval($_POST['municipio_proveedores']) : 0;	
			$localidad = mainModel::cleanString($_POST['dirección_proveedores']);
			$telefono = mainModel::cleanString($_POST['telefono_proveedores']);
			$correo = mainModel::cleanStringStrtolower($_POST['correo_proveedores']);
			$colaborador_id = $_SESSION['colaborador_id_sd'];
			$fecha_registro = date("Y-m-d H:i:s");
			$estado = 1;			

			$datos = [
				"nombre" => $nombre,
				"rtn" => $rtn,
				"fecha" => $fecha,
				"departamento_id" => $departamento_id,
				"municipio_id" => $municipio_id,
				"localidad" => $localidad,
				"telefono" => $telefono,
				"correo" => $correo,
				"colaborador_id" => $colaborador_id,
				"fecha_registro" => $fecha_registro,
				"estado" => $estado,				
			];
			
			$query = proveedoresModelo::agregar_proveedores_model($datos);
			
			if($query){
				//GUARDAR HISTORIAL
				$datos = [
					"modulo" => 'Proveedor',
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
					"status" => "Registro",
					"observacion" => "Se registro el proveedor {$nombre} con el RTN {$rtn}",
					"fecha_registro" => date("Y-m-d H:i:s")
				];	
				
				mainModel::guardarHistorial($datos);
								
				$alert = [
					"alert" => "clear",
					"title" => "Registro almacenado",
					"text" => "El registro se ha almacenado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formProveedores",
					"id" => "proceso_proveedores",
					"valor" => "Registro",	
					"funcion" => "listar_proveedores();getDepartamentoProveedores();getMunicipiosProveedores(0);getProveedorIngresos();getProveedorEgresos();listar_proveedores_ingresos_contabilidad_buscar();listar_proveedores_compras_buscar();listar_proveedores_egresos_contabilidad_buscar();",
					"modal" => "",						
				];
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Ocurrio un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}
			
			return mainModel::sweetAlert($alert);				
		}
		
		public function edit_proveedores_controlador(){
			$proveedores_id = $_POST['proveedores_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['nombre_proveedores']);		
			$departamento_id = isset($_POST['departamento_proveedores']) ? intval($_POST['departamento_proveedores']) : 0;
			$municipio_id = isset($_POST['municipio_proveedores']) ? intval($_POST['municipio_proveedores']) : 0;
			$localidad = mainModel::cleanString($_POST['dirección_proveedores']);
			$telefono = mainModel::cleanString($_POST['telefono_proveedores']);
			$correo = mainModel::cleanStringStrtolower($_POST['correo_proveedores']);
			$rtn = mainModel::cleanString($_POST['rtn_proveedores']);

			if (isset($_POST['proveedores_activo'])){
				$estado = $_POST['proveedores_activo'];
			}else{
				$estado = 2;
			}	
			
			$datos = [
				"proveedores_id" => $proveedores_id,
				"nombre" => $nombre,
				"departamento_id" => $departamento_id,
				"municipio_id" => $municipio_id,
				"localidad" => $localidad,
				"telefono" => $telefono,
				"correo" => $correo,
				"estado" => $estado,
				"rtn" => $rtn,
			];

			$query = proveedoresModelo::edit_proveedores_modelo($datos);
			
			if($query){		
				//GUARDAR HISTORIAL
				$datos = [
					"modulo" => 'Proveedor',
					"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
					"status" => "Edición",
					"observacion" => "Se edito el proveedor {$nombre} con el RTN {$rtn}",
					"fecha_registro" => date("Y-m-d H:i:s")
				];	
				
				mainModel::guardarHistorial($datos);

				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formProveedores",
					"id" => "proceso_proveedores",
					"valor" => "Editar",	
					"funcion" => "listar_proveedores();getDepartamentoProveedores();getMunicipiosProveedores(0);",
					"modal" => "",
				];
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Ocurrio un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}
			
			return mainModel::sweetAlert($alert);
		}
		
		public function delete_proveedores_controlador(){
			$proveedores_id = $_POST['proveedores_id'];
			
			$campos = ['nombre', 'rtn'];
			$resultados = mainModel::consultar_tabla('proveedores', $campos, "proveedores_id = {$proveedores_id}");
			
			// Verifica si hay resultados antes de intentar acceder a los campos
			if (!empty($resultados)) {
				// Obtén el primer resultado (puedes ajustar según tus necesidades)
				$primerResultado = $resultados[0];
			
				// Verifica si las claves existen antes de acceder a ellas
				$nombre = isset($primerResultado['nombre']) ? $primerResultado['nombre'] : null;
				$rtn = isset($primerResultado['rtn']) ? $primerResultado['rtn'] : null;
			
				// Ahora puedes usar $nombre y $rtn de forma segura
			} else {
				// No se encontraron resultados
				$nombre = null;
				$rtn = null;
			}

			$result_valid_proveedores = proveedoresModelo::valid_proveedores_compras($proveedores_id);
			
			if($result_valid_proveedores->num_rows==0){
				$query = proveedoresModelo::delete_proveedores_modelo($proveedores_id);
								
				if($query){
					//GUARDAR HISTORIAL
					$datos = [
						"modulo" => 'Proveedor',
						"colaboradores_id" => $_SESSION['colaborador_id_sd'],		
						"status" => "Eliminar",
						"observacion" => "Se elimino el proveedor {$nombre} con el RTN {$rtn}",
						"fecha_registro" => date("Y-m-d H:i:s")
					];	
					
					mainModel::guardarHistorial($datos);

					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formProveedores",
						"id" => "proceso_proveedores",
						"valor" => "Eliminar",	
						"funcion" => "listar_proveedores();getDepartamentoProveedores();getMunicipiosProveedores(0);",
						"modal" => "modal_registrar_proveedores",
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Este registro cuenta con información almacenada",
					"text" => "No se puede eliminar este registro",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);
		}		
	}
?>