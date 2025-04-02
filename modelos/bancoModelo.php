<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class bancoModelo extends mainModel{
		protected function agregar_banco_modelo($datos){
			$banco_id = mainModel::correlativo("banco_id", "banco");
			$insert = "INSERT INTO banco VALUES('$banco_id','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_banco_modelo($nombre){
			$query = "SELECT banco_id FROM banco WHERE nombre = '$nombre'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_banco_modelo($datos){
			$update = "UPDATE banco
			SET 
				nombre = '".$datos['nombre']."',
				estado = '".$datos['estado']."'
			WHERE banco_id = '".$datos['banco_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_banco_modelo($banco_id){
			$delete = "DELETE FROM banco WHERE banco_id = '$banco_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_banco_pagos_modelo($banco_id){
			$query = "SELECT banco_id FROM pagos_detalles WHERE banco_id = '$banco_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>