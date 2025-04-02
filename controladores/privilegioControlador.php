<?php
    if($peticionAjax){
        require_once "../modelos/privilegioModelo.php";
    }else{
        require_once "./modelos/privilegioModelo.php";
    }
	
	class privilegioControlador extends privilegioModelo{
		public function agregar_privilegio_controlador(){
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultVarios = privilegioModelo::valid_privilegios_modelo($datos);
			
			if($resultVarios->num_rows==0){
				$query = privilegioModelo::agregar_privilegios_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formPrivilegios",
						"id" => "proceso_privilegios",
						"valor" => "Registro",
						"funcion" => "listar_privilegio();",
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
		
		public function edit_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			$nombre = mainModel::cleanStringConverterCase($_POST['privilegios_nombre']);
			
			if (isset($_POST['privilegio_activo'])){
				$estado = $_POST['privilegio_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"privilegio_id" => $privilegio_id,
				"nombre" => $nombre,
				"estado" => $estado,
			];		

			$query = privilegioModelo::edit_privilegio_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formPrivilegios",	
					"id" => "proceso_privilegios",
					"valor" => "Editar",
					"funcion" => "listar_privilegio();",
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
		
		public function delete_privilegio_controlador(){
			$privilegio_id = $_POST['privilegio_id_'];
			
			$result_valid_privilegio = privilegioModelo::valid_privilegio_usuarios($privilegio_id);
			
			if($result_valid_privilegio->num_rows==0 ){
				$query = privilegioModelo::delete_privilegio_modelo($privilegio_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formPrivilegios",	
						"id" => "proceso_privilegios",
						"valor" => "Eliminar",
						"funcion" => "listar_privilegio();",
						"modal" => "modal_registrar_privilegios",
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