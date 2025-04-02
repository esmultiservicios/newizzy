<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class puestosModelo extends mainModel{
		protected function agregar_puestos_modelo($datos){
			$puestos_id = mainModel::correlativo("puestos_id", "puestos");
			$insert = "INSERT INTO puestos VALUES('$puestos_id','".$datos['puesto']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_puestos_modelo($puesto){
			$query = "SELECT puestos_id FROM puestos WHERE nombre = '$puesto'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_puestos_modelo($datos){
			$update = "UPDATE puestos
			SET 
				nombre = '".$datos['puesto']."',
				estado = '".$datos['estado']."'
			WHERE puestos_id = '".$datos['puestos_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_puestos_modelo($puestos_id){
			$delete = "DELETE FROM puestos WHERE puestos_id = '$puestos_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_puestos_colaborador_modelo($puestos_id){
			$query = "SELECT puestos_id FROM colaboradores WHERE puestos_id = '$puestos_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	