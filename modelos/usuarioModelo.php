<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }

    class usuarioModelo extends mainModel{
		protected function agregar_usuario_modelo($datos){
			$users_id = mainModel::correlativo("users_id", "users");
			$insert = "INSERT INTO users VALUES('$users_id','".$datos['colaborador_id']."','".$datos['privilegio_id']."','".$datos['nickname']."','".$datos['pass']."','".$datos['correo']."','".$datos['tipo_user']."','".$datos['estado']."','".$datos['fecha_registro']."','".$datos['empresa']."','".$datos['server_customers_id']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_user_modelo($colaborador_id){
			$query = "SELECT users_id 
				FROM users 
				WHERE colaboradores_id = '$colaborador_id'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
			return $sql;
		}	

		protected function valid_correo_modelo($email){
			$query = "SELECT users_id FROM users WHERE email = '$email'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);			
			return $sql;
		}			

		protected function edit_user_modelo($datos){
			$update = "UPDATE users
			SET 
				tipo_user_id = '".$datos['tipo_user']."',
				privilegio_id = '".$datos['privilegio_id']."',
				empresa_id = '".$datos['empresa_id']."',
				estado = '".$datos['estado']."'
			WHERE users_id = '".$datos['usuarios_id']."'";

			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_user_modelo($users_id){
			$delete = "DELETE FROM users WHERE users_id = '$users_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_user_bitacora($user_id){
			$query = "SELECT b.colaboradores_id
				FROM bitacora as b
				INNER JOIN users AS u
				ON b.colaboradores_id = u.colaboradores_id
				WHERE u.users_id = '$user_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function getTotalUsuarios(){
			$query = "SELECT COUNT(*) AS 'total_usuarios'
				FROM users
				WHERE estado = 1 AND tipo_user_id NOT IN(1)";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}		

		protected function cantidad_usuarios_modelo(){
			$result = mainModel::getCantidadUsuariosPlan();
			
			return $result;			
		}	
		
		protected function send_email_usuarios_modelo($datos){
			$result = mainModel::sendMail($datos['servidor'], $datos['puerto'], $datos['contraseña'], $datos['CharSet'], $datos['SMTPSecure'], $datos['de'], $datos['para'], $datos['from'], $datos['asunto'], $datos['mensaje'], $datos['URL']);
			
			return $result;			
		}	
		
		protected function get_email_usuarios_modelo($correo_tipo_id){
			$result = mainModel::getCorreoServer($correo_tipo_id);
			
			return $result;			
		}
		
		protected function get_empresa_factura_correo_usuarios_modelo($usuario){
			$result = mainModel::getEmpresaFacturaCorreo($usuario);
			
			return $result;			
		}	
		
		protected function getCorrreosAdmin(){
			$query = "SELECT users.email, CONCAT(colaboradores.nombre, ' ', colaboradores.apellido) AS nombre_completo
			FROM colaboradores
			INNER JOIN users ON colaboradores.colaboradores_id = users.colaboradores_id
			WHERE users.privilegio_id IN(1,2) AND users.estado = 1";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
    }
?>