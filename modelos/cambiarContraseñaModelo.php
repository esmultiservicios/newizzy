<?php
	// cambiarContraseñaModelo.php

    if($peticionAjax){
        require_once "../core/mainModel.php";
		require_once "../core/correo/sendEmail.php";
    }else{
        require_once "./core/mainModel.php";
		require_once "./core/correo/sendEmail.php";
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
			$query = "SELECT 
					u.email AS email,
					c.nombre AS usuario
				FROM users AS u
				INNER JOIN colaboradores AS c
					ON u.colaboradores_id = c.colaboradores_id
				WHERE u.users_id = '$users_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function valid_user($usu_forgot){
			$query = "SELECT 
					u.users_id AS users_id,
					u.email AS email,
					c.nombre AS usuario
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
			/*
				IMPORTANTE:
				Este método antes llamaba mainModel::sendMailAjax(),
				pero ese método ya no existe en tu flujo actual.

				Ahora el envío de correos se hace con:
				core/correo/sendEmail.php
				Clase: sendEmail
				Método: enviarCorreo()
			*/

			$correo_tipo_id = isset($datos['correo_tipo_id']) ? (int)$datos['correo_tipo_id'] : 1;
			$empresa_id = isset($datos['empresa_id']) ? (int)$datos['empresa_id'] : 0;

			$para = isset($datos['para']) ? trim($datos['para']) : "";
			$nombre_para = isset($datos['nombre_para']) ? trim($datos['nombre_para']) : "";
			$asunto = isset($datos['asunto']) ? trim($datos['asunto']) : "";
			$mensaje = isset($datos['mensaje']) ? $datos['mensaje'] : "";

			if ($empresa_id <= 0 && isset($_SESSION['empresa_id_sd'])) {
				$empresa_id = (int)$_SESSION['empresa_id_sd'];
			}

			if ($para == "" || !filter_var($para, FILTER_VALIDATE_EMAIL)) {
				return 0;
			}

			if ($asunto == "") {
				$asunto = "Notificación del sistema";
			}

			if ($mensaje == "") {
				$mensaje = "<p>Notificación generada por el sistema.</p>";
			}

			if ($nombre_para == "") {
				$nombre_para = "Usuario";
			}

			$destinatarios = [
				$para => $nombre_para
			];

			$bccDestinatarios = [];
			$archivos_adjuntos = [];

			$sendEmail = new sendEmail();

			$result = $sendEmail->enviarCorreo(
				$destinatarios,
				$bccDestinatarios,
				$asunto,
				$mensaje,
				$correo_tipo_id,
				$empresa_id,
				$archivos_adjuntos
			);
			
			return $result;			
		}	
	}