<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class cambiarContraseñaModelo extends mainModel{		
		protected function edit_contraseña_modelo($datos){
			$update = "UPDATE users
			SET 
				password = '".$datos['contraseña']."'
			WHERE users_id = '".$datos['users_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}	

		protected function edit_contraseña_login_modelo($datos){
			$update = "UPDATE users
			SET 
				password = '".$datos['contraseña']."'
			WHERE email = '".$datos['correo']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}			

		protected function getCorreoUsuario($users_id){
			$query = "SELECT u.email AS 'email', CONCAT(c.nombre, ' ', c.apellido) AS 'usuario'
				FROM users AS u
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE u.users_id = '$users_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function valid_user($usu_forgot){
			$query = "SELECT u.users_id AS 'users_id', u.email AS 'email', CONCAT(c.nombre, ' ', c.apellido) AS 'usuario'
				FROM users AS u
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE u.email = '$usu_forgot'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}			

		protected function encryptionPass($string){
			$result = mainModel::encryption($string);
			
			return $result;			
		}
		
		protected function generar_pass_complejo(){
			$result = mainModel::generar_password_complejo();
			
			return $result;			
		}	

		protected function get_email_usuarios_modelo($correo_tipo_id){
			$result = mainModel::getCorreoServer($correo_tipo_id);
			
			return $result;			
		}
		
		protected function get_empresa_factura_correo_usuarios_modelo($users_id){
			$result = mainModel::getEmpresaFacturaCorreoUsuario($users_id);
			
			return $result;			
		}
		
		protected function send_email_usuarios_modelo($datos){
			$result = mainModel::sendMailAjax($datos['servidor'], $datos['puerto'], $datos['contraseña'], $datos['CharSet'], $datos['SMTPSecure'], $datos['de'], $datos['para'], $datos['from'], $datos['asunto'], $datos['mensaje'], $datos['URL']);
			
			return $result;			
		}	
	}
?>