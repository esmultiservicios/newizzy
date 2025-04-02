<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class privilegioModelo extends mainModel{
		protected function agregar_privilegios_modelo($datos){
			$privilegio_id = mainModel::correlativo("privilegio_id ", "privilegio");
			$insert = "INSERT INTO privilegio VALUES('$privilegio_id ','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_privilegios_modelo($datos){
			$query = "SELECT privilegio_id FROM privilegio WHERE nombre = '".$datos['nombre']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_privilegio_modelo($datos){
			$update = "UPDATE privilegio
				SET
					nombre = '".$datos['nombre']."',
					estado = '".$datos['estado']."'					
				WHERE privilegio_id = '".$datos['privilegio_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_privilegio_modelo($privilegio_id){
			$delete = "DELETE FROM privilegio WHERE privilegio_id = '$privilegio_id' AND privilegio_id NOT IN(1,2)";
		
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_privilegio_usuarios($privilegio_id){
			$query = "SELECT users_id FROM users WHERE privilegio_id = '$privilegio_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	