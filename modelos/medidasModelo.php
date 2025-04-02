<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class medidasModelo extends mainModel{
		protected function agregar_medidas_modelo($datos){
			$medida_id = mainModel::correlativo("medida_id", "medida");
			$insert = "INSERT INTO medida VALUES('$medida_id','".$datos['medidas_medidas']."','".$datos['descripcion_medidas']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_medidas_modelo($medida){
			$query = "SELECT medida_id FROM medida WHERE nombre = '$medida'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_medidas_modelo($datos){
			$update = "UPDATE medida
			SET 
				nombre = '".$datos['medidas_medidas']."',	
				descripcion = '".$datos['descripcion_medidas']."',				
				estado = '".$datos['estado']."'
			WHERE medida_id = '".$datos['medida_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_medidas_modelo($medida_id){
			$delete = "DELETE FROM medida WHERE medida_id = '$medida_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_medidas_producto_modelo($medida_id){
			$query = "SELECT productos_id FROM productos WHERE medida_id = '$medida_id'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}		
	}
?>	