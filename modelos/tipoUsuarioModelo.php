<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class tipoUsuarioModelo extends mainModel{
		protected function agregar_tipo_usuario_modelo($datos){
			$tipo_user_id  = mainModel::correlativo("tipo_user_id", "tipo_user");
			$insert = "INSERT INTO tipo_user VALUES('$tipo_user_id  ','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_tipo_usuario_modelo($datos){
			$query = "SELECT tipo_user_id FROM tipo_user WHERE nombre = '".$datos['nombre']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_tipo_usuario_modelo($datos){
			$update = "UPDATE tipo_user
			SET
				nombre = '".$datos['nombre']."',
				estado = '".$datos['estado']."'				
			WHERE tipo_user_id = '".$datos['tipo_user_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_tipo_usuario_modelo($tipo_user_id){
			$delete = "DELETE FROM tipo_user WHERE tipo_user_id = '$tipo_user_id' AND tipo_user_id NOT IN(1,2)";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_tipo_user_usuarios($tipo_user_id){
			$query = "SELECT users_id FROM users WHERE tipo_user_id = '$tipo_user_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	