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
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formColaboradores",
						"id" => "proceso_colaboradores",
						"valor" => "Registro",
						"funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();listar_colaboradores_buscar_factura();listar_colaboradores_buscar_cotizacion();",
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
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Resgistro ya existe",
					"text" => "Lo sentimos este registro ya existe",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);
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

			if(isset($_POST['puesto_colaborador'])){//COMPRUEBO SI LA VARIABLE ESTA DIFINIDA
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
					//ACTUALIZAMOS LA CONTASEÑA DEL USUARIO EN LA DB PRINCIPAL
					$updateDBMainUsers = "UPDATE colaboradores 
						SET 
							estado = '$estado'
						WHERE nombre = '$nombre' AND apellido = '$apellido' AND identidad = '$identidad'";
					
					mainModel::connectionLogin()->query($updateDBMainUsers);
				}

				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formColaboradores",	
					"id" => "proceso_colaboradores",
					"valor" => "Editar",
					"funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();",
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
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formColaboradores",	
					"id" => "proceso_colaboradores",
					"valor" => "Editar",
					"funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();",
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
		
		public function delete_colaborador_controlador(){
			$colaborador_id = $_POST['colaborador_id'];
			
			$result_valid_colaboradores = colaboradorModelo::valid_colaborador_bitacora($colaborador_id);
			
			if($result_valid_colaboradores->num_rows==0){
				$query = colaboradorModelo::delete_colaborador_modelo($colaborador_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formColaboradores",	
						"id" => "proceso_colaboradores",
						"valor" => "Eliminar",
						"funcion" => "listar_colaboradores();getEmpresaColaboradores();getPuestoColaboradores();",
						"modal" => "modal_registrar_colaboradores",
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