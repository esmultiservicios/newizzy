<?php
    if($peticionAjax){
        require_once "../modelos/tipoUsuarioModelo.php";
    }else{
        require_once "./modelos/tipoUsuarioModelo.php";
    }
	
	class tipoUsuarioControlador extends tipoUsuarioModelo{
		public function agregar_tipo_usuario_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['tipo_usuario_nombre']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,					
			];
			
			$resultVarios = tipoUsuarioModelo::valid_tipo_usuario_modelo($datos);
			
			if($resultVarios->num_rows==0){
				$query = tipoUsuarioModelo::agregar_tipo_usuario_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formTipoUsuario",
						"id" => "proceso_tipo_usuario",
						"valor" => "Registro",
						"funcion" => "listar_tipo_usuario();",
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
		
		public function edit_tipo_usuario_controlador(){
			$tipo_user_id = $_POST['tipo_user_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['tipo_usuario_nombre']);
			
			if (isset($_POST['tipo_usuario_activo'])){
				$estado = $_POST['tipo_usuario_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"tipo_user_id" => $tipo_user_id,
				"nombre" => $nombre,
				"estado" => $estado,				
			];		

			$query = tipoUsuarioModelo::edit_tipo_usuario_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formTipoUsuario",	
					"id" => "proceso_tipo_usuario",
					"valor" => "Editar",
					"funcion" => "listar_tipo_usuario();",
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
		
		public function delete_tipo_usuario_controlador(){
			$tipo_user_id = $_POST['tipo_user_id'];
			
			$result_valid_tipo_usuario = tipoUsuarioModelo::valid_tipo_user_usuarios($tipo_user_id);
			
			if($result_valid_tipo_usuario->num_rows==0 ){
				$query = tipoUsuarioModelo::delete_tipo_usuario_modelo($tipo_user_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formTipoUsuario",	
						"id" => "proceso_tipo_usuario",
						"valor" => "Eliminar",
						"funcion" => "listar_tipo_usuario();",
						"modal" => "modal_registrar_tipoUsuario",
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