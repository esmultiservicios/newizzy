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
			
			if(tipoUsuarioModelo::valid_tipo_usuario_modelo($nombre)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de usuario",                
				]);                
			}

			if(!tipoUsuarioModelo::agregar_tipo_usuario_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el tipo de usuario",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Tipo de usuario registrado correctamente",           
				"form" => "formTipoUsuario",
				"funcion" => "listar_tipo_usuario();"
			]);			
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

			if(!tipoUsuarioModelo::edit_tipo_usuario_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el tipo de usuario",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Tipo de usuario actualizado correctamente",           
				"form" => "formTipoUsuario",
				"funcion" => "listar_tipo_usuario();"
			]);			
		}
		
		public function delete_tipo_usuario_controlador(){
			$tipo_user_id = $_POST['tipo_user_id'];
			
			$campos = ['tipo_user_id'];
			$tabla = "tipo_usuario";
			$condicion = "tipo_user_id = {$tipo_user_id}";

			$tipo_usuario = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($tipo_usuario)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Tipo de usuario no encontrado"
				]);
				exit();
			}
			
			$nombre = $tipo_usuario[0]['nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(tipoUsuarioModelo::valid_tipo_user_usuarios($tipo_user_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "El tipo de usuario {$nombre} tiene usuarios asociados"
				]);
				exit();                
			}

			if(!tipoUsuarioModelo::delete_tipo_usuario_modelo($tipo_user_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar el tipo de usuario {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Tipo de usuario {$nombre} eliminado correctamente"
			]);
			exit();			
		}
	}