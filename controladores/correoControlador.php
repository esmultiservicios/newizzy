<?php
    if($peticionAjax){
        require_once "../modelos/correoModelo.php";
    }else{
        require_once "./modelos/correoModelo.php";
    }
	
	class correoControlador extends correoModelo{
		public function edit_correo_controlador(){
			$correo_id = $_POST['correo_id'];
			$puserverConfEmailsto = mainModel::cleanString($_POST['serverConfEmail']);
			$correoConfEmail = mainModel::cleanString($_POST['correoConfEmail']);
			$passConfEmail = mainModel::cleanString($_POST['passConfEmail']);
			$puertoConfEmail = mainModel::cleanString($_POST['puertoConfEmail']);
			$smtpSecureConfEmail = mainModel::cleanString($_POST['smtpSecureConfEmail']);
		
			$datos = [
				"correo_id" => $correo_id,
				"server" => $puserverConfEmailsto,
				"correo" => $correoConfEmail,
				"password" => $passConfEmail,
				"port" => $puertoConfEmail,
				"smtp_secure" => $smtpSecureConfEmail,				
			];		

			if(!correoModelo::edit_correo_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar el correo",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Correo actualizado correctamente",           
				"form" => "formConfEmails",
				"funcion" => "listar_correos_configuracion();getSMTPSecure();getTipoCorreo();"
			]);
		}

		public function registrar_destinatarios_correo_controlador(){
			$correo = mainModel::cleanString($_POST['correo']);
			$nombre = mainModel::cleanString($_POST['nombre']);

			$datos = [
				"correo" => $correo,
				"nombre" => $nombre,				
			];
			
			if(correoModelo::valid_pdestinatarios_modelo($correo)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar el destinatario",                
				]);                
			}

			if(!correoModelo::agregar_destinatarios_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el destinatario",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Destinatario registrado correctamente",           
				"form" => "formDestinatarios",
				"funcion" => "listar_destinatarios();"
			]);
		}
	}