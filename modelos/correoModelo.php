<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class correoModelo extends mainModel{		
		protected function edit_correo_modelo($datos){
			$update = "UPDATE correo
			SET 
				server = '".$datos['server']."',
				correo = '".$datos['correo']."',
				password = '".$datos['password']."',
				port = '".$datos['port']."',
				smtp_secure = '".$datos['smtp_secure']."'
			WHERE correo_id = '".$datos['correo_id']."'";

			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function agregar_destinatarios_modelo($datos){
			$notificaciones_id = mainModel::correlativo("notificaciones_id", "notificaciones");
		
			$insert = "INSERT INTO `notificaciones`(`notificaciones_id`, `correo`, `nombre`) VALUES ('{$notificaciones_id}','{$datos['correo']}','{$datos['nombre']}')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}		

		protected function valid_pdestinatarios_modelo($correo){
			$query = "SELECT notificaciones_id FROM notificaciones WHERE correo = '$correo'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>