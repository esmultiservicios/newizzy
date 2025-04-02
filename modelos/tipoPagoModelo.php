<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class tipoPagoModelo extends mainModel{
		protected function agregar_tipo_pago_modelo($datos){
			$tipo_pago_id = mainModel::correlativo("tipo_pago_id", "tipo_pago");
			$insert = "INSERT INTO tipo_pago VALUES('$tipo_pago_id','".$datos['tipo_cuenta']."','".$datos['nombre']."','".$datos['cuentas_id']."','".$datos['estado']."','".$datos['fecha_registro']."')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_tipo_pago_modelo($nombre){
			$query = "SELECT tipo_pago_id FROM tipo_pago WHERE nombre = '$nombre'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_tipo_pago_modelo($datos){
			$update = "UPDATE tipo_pago
			SET 
				nombre = '".$datos['nombre']."',
				cuentas_id = '".$datos['cuentas_id']."',				
				estado = '".$datos['estado']."'
			WHERE tipo_pago_id = '".$datos['tipo_pago_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_tipo_pago_modelo($tipo_pago_id){
			$delete = "DELETE FROM tipo_pago WHERE tipo_pago_id = '$tipo_pago_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_tipo_pagos_on_pagos_modelo($tipo_pago_id){
			$query = "SELECT tipo_pago_id FROM pagos_detalles WHERE tipo_pago_id = '$tipo_pago_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	