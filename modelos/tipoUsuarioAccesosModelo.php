<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class tipoUsuarioAccesosModelo extends mainModel{
		protected function agregar_tipoUsuarioAccesos_modelo($datos){
			$permisos_id = mainModel::correlativo("permisos_id", "permisos");
			$insert = "INSERT INTO permisos VALUES('$permisos_id','".$datos['tipo_user_id']."','".$datos['tipo_permiso']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_tipoUsuarioAccesos_modelo($datos){
			$query = "SELECT permisos_id FROM permisos WHERE tipo_user_id = '".$datos['tipo_user_id']."' AND tipo_permiso = '".$datos['tipo_permiso']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}	
		
		protected function edit_tipoUsuarioAccesos_modelo($datos){
			$update = "UPDATE permisos
			SET
				estado = '".$datos['estado']."'				
			WHERE tipo_user_id = '".$datos['tipo_user_id']."' AND tipo_permiso = '".$datos['tipo_permiso']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	