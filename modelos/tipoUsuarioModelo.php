<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class tipoUsuarioModelo extends mainModel{
		protected function agregar_tipo_usuario_modelo($datos){
			// Verificar conexión antes de cada consulta
			if (!mainModel::connection()->ping()) {
				mainModel::connection()->close();
				mainModel::connection()->connect();
			}
			
			$tipo_user_id  = mainModel::correlativo("tipo_user_id", "tipo_user");
			$insert = "INSERT INTO tipo_user VALUES('$tipo_user_id  ','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_tipo_usuario_modelo($datos){
			// Verificar conexión antes de cada consulta
			if (!mainModel::connection()->ping()) {
				mainModel::connection()->close();
				mainModel::connection()->connect();
			}
			
			// Usar consulta normal (no preparada) para evitar problemas de conexión
			$nombre = mainModel::connection()->real_escape_string($datos['nombre']);
			$query = "SELECT tipo_user_id FROM tipo_user WHERE nombre = '$nombre'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_tipo_usuario_modelo($datos){
			// Verificar conexión antes de cada consulta
			if (!mainModel::connection()->ping()) {
				mainModel::connection()->close();
				mainModel::connection()->connect();
			}
			
			$update = "UPDATE tipo_user
			SET
				nombre = '".$datos['nombre']."',
				estado = '".$datos['estado']."'				
			WHERE tipo_user_id = '".$datos['tipo_user_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_tipo_usuario_modelo($tipo_user_id){
			// Verificar conexión antes de cada consulta
			if (!mainModel::connection()->ping()) {
				mainModel::connection()->close();
				mainModel::connection()->connect();
			}
			
			$delete = "DELETE FROM tipo_user WHERE tipo_user_id = '$tipo_user_id' AND tipo_user_id NOT IN(1,2)";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_tipo_user_usuarios($tipo_user_id){
			// Verificar conexión antes de cada consulta
			if (!mainModel::connection()->ping()) {
				mainModel::connection()->close();
				mainModel::connection()->connect();
			}
			
			$query = "SELECT users_id FROM users WHERE tipo_user_id = '$tipo_user_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>