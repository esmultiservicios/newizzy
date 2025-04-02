<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class ubicacionModelo extends mainModel{
		protected function agregar_ubicacion_modelo($datos){
			$ubicacion_id = mainModel::correlativo("ubicacion_id", "ubicacion");
			$insert = "INSERT INTO ubicacion (ubicacion_id, empresa_id, nombre, estado, fecha_registro)
			VALUES ($ubicacion_id, '".$datos['empresa']."','".$datos['ubicacion']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			return $sql;			
		}
		
		protected function valid_ubicacion_modelo($ubicacion){
			$query = "SELECT ubicacion_id FROM ubicacion WHERE nombre = '$ubicacion'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_ubicacion_modelo($datos){
			$update = "UPDATE ubicacion
			SET 
				nombre = '".$datos['ubicacion']."',				
				estado = '".$datos['estado']."'
			WHERE ubicacion_id = '".$datos['ubicacion_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_ubicacion_modelo($ubicacion_id){
			$delete = "DELETE FROM ubicacion WHERE ubicacion_id = '$ubicacion_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_ubicacion_almacen_modelo($ubicacion_id){
			$query = "SELECT almacen_id FROM almacen WHERE ubicacion_id = '$ubicacion_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}		
	}
?>	