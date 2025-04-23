<?php
    if($peticionAjax){
        require_once "../modelos/cambiarContraseñaModelo.php";
		require_once "../core/Database.php";
		require_once "../core/sendEmail.php";		
    }else{
        require_once "./modelos/cambiarContraseñaModelo.php";
		require_once "../core/Database.php";
		require_once "./core/sendEmail.php";		
    }
	
	class cambiarContraseñaControlador extends cambiarContraseñaModelo{		
		public function edit_contraseña_controlador(){
			$contraseña = mainModel::encryption($_POST['nuevacontra']);

			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}

			$database = new Database();
			$sendEmail = new sendEmail();			

			$users_id = $_SESSION['users_id_sd'];

			$datos = [
				"users_id" => $users_id,
				"contraseña" => $contraseña,				
			];		

			$query = cambiarContraseñaModelo::edit_contraseña_modelo($datos);
			
			if($query){	
				//OBTENEMOS EL NOMBRE DEL COLABORADOR
				$tablColaborador = "colaboradores";
				$camposColaborador = ["nombre", "apellido"];
				$condicionesColaborador = ["colaboradores_id" => $users_id];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];
				$resultadoColaborador = $database->consultarTabla($tablColaborador, $camposColaborador, $condicionesColaborador, $orderBy, $tablaJoin, $condicionesJoin);

				$colaborador_nombre = "";

				if (!empty($resultadoColaborador)) {
					$colaborador_nombre = trim($resultadoColaborador[0]['nombre'].' '.$resultadoColaborador[0]['apellido']);
				}
			
				//OBTENEMOS EL CORREO DEL USUARIO
				$tablaUsuario = "users";
				$camposUsuario = ["email", "server_customers_id"];
				$condicionesUsuario = ["users_id" => $users_id];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];
				$resultadoUsuario = $database->consultarTabla($tablaUsuario, $camposUsuario, $condicionesUsuario, $orderBy, $tablaJoin, $condicionesJoin);

				$correo_usuario = "";
				$estado = 1;
				if (!empty($resultadoUsuario)) {
					$correo_usuario = trim($resultadoUsuario[0]['email']);
					$server_customers_id = trim($resultadoUsuario[0]['server_customers_id']);
				}
				
				if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
					//ACTUALIZAMOS LA CONTASEÑA DEL USUARIO EN LA DB PRINCIPAL
					$updateDBMainUsers = "UPDATE users 
						SET 
							estado = '$estado'
						WHERE email = '$correo_usuario' AND server_customers_id = '$server_customers_id'";			

					mainModel::connectionLogin()->query($updateDBMainUsers);
				}

				//OBTENEMOS EL NOMBRE DE LA EMPRESA
				$empresa_id_sesion = $_SESSION['empresa_id_sd'];
				$tablaEmpresa = "empresa";
				$camposEmpresa = ["nombre"];
				$condicionesEmpresa = ["empresa_id" => $empresa_id_sesion];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];
				$resultadoEmpresa = $database->consultarTabla($tablaEmpresa, $camposEmpresa, $condicionesEmpresa, $orderBy, $tablaJoin, $condicionesJoin);
			
				$empresa_nombre = "";
			
				if (!empty($resultadoEmpresa)) {
					$empresa_nombre = strtoupper(trim($resultadoEmpresa[0]['nombre']));
				}		

				$correo_tipo_id = "1";//Notificaciones
				$destinatarios = array($correo_usuario => $colaborador_nombre);

				// Destinatarios en copia oculta (Bcc)
				$bccDestinatarios = [];

				$asunto = "¡Cambio de Contraseña Exitoso!";
				$mensaje = '
					<div style="padding: 20px;">
						<p style="margin-bottom: 10px;">
							¡Hola '.$colaborador_nombre.'!
						</p>
						
						<p style="margin-bottom: 10px;">
							Esperamos que te encuentres bien. Queremos informarte que se ha realizado con éxito el cambio de tu contraseña en nuestro sistema IZZY. Esta solicitud de cambio de contraseña fue iniciada por ti, por lo que no debes preocuparte.
						</p>								
						
						<p style="margin-bottom: 10px;">
							Si no realizaste esta acción personalmente, te sugerimos que inicies sesión primero. Posteriormente, te recomendamos cambiar tu contraseña por una que elijas en la sección de configuración de tu cuenta. Para acceder al Sistema IZZY, simplemente haz clic en el siguiente enlace:
						</p>					
						
						<p style="margin-bottom: 10px;">
							<a href='.SERVERURL.'>Clic para Acceder a IZZY<a>
						</p>
						
						<p style="margin-bottom: 10px;">
							La seguridad de tu cuenta es de suma importancia para nosotros. Si no reconoces esta acción o tienes alguna pregunta, por favor, contáctanos de inmediato. Estamos aquí para brindarte la ayuda que necesitas y asegurarnos de que tu experiencia sea segura y sin problemas.
						</p>
																				
						<p style="margin-bottom: 10px;">
							Agradecemos tu confianza en CLINICARE y esperamos seguir ofreciéndote un servicio excepcional.
						</p>
						
						<p style="margin-bottom: 10px;">
							Saludos cordiales,
						</p>
						
						<p>
							<b>El Equipo de '.$empresa_nombre.'</b>
						</p>                
					</div>
				';

				$archivos_adjuntos = [];
				$sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id_sesion, $archivos_adjuntos);
				
				$alert = [
					"alert" => "cerrar",
					"title" => "Registro modificado",
					"text" => "La contraseña se ha cambiado satisfactoriamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
				];
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Ocurrio un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}			
			
			return mainModel::sweetAlert($alert);
		}

		public function resetear_contraseña_controlador(){
			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}

			$database = new Database();
			$sendEmail = new sendEmail();
			
			$contraseña = cambiarContraseñaModelo::generar_pass_complejo();
			$users_id = $_POST['users_id'];
			$server_customers_id = $_POST['server_customers_id'];
			$contraseña_encriptada = mainModel::encryption($contraseña);

			$datos = [
				"users_id" => $users_id,
				"contraseña" => $contraseña_encriptada,			
			];		

			//OBTENEMOS EL NOMBRE DEL COLABORADOR
			$respuesta = 0;
			$tablColaborador = "colaboradores";
			$camposColaborador = ["nombre", "apellido"];
			$condicionesColaborador = ["colaboradores_id" => $users_id];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];
			$resultadoColaborador = $database->consultarTabla($tablColaborador, $camposColaborador, $condicionesColaborador, $orderBy, $tablaJoin, $condicionesJoin);

			$colaborador_nombre = "";

			if (!empty($resultadoColaborador)) {
				$colaborador_nombre = trim($resultadoColaborador[0]['nombre'].' '.$resultadoColaborador[0]['apellido']);
			}

			//OBTENEMOS EL CORREO DEL USUARUIO
			$tablaUsuario = "users";
			$camposUsuario = ["email"];
			$condicionesUsuario = ["users_id" => $users_id];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];
			$resultadoUsuario = $database->consultarTabla($tablaUsuario, $camposUsuario, $condicionesUsuario, $orderBy, $tablaJoin, $condicionesJoin);

			$correo_usuario = "";

			if (!empty($resultadoUsuario)) {
				$correo_usuario = trim($resultadoUsuario[0]['email']);
			}				

			$query = cambiarContraseñaModelo::edit_contraseña_modelo($datos);

			if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
				//ACTUALIZAMOS LA CONTASEÑA DEL USUARIO EN LA DB PRINCIPAL
				$updateDBMainUsers = "UPDATE users SET password = '$contraseña_encriptada' WHERE email = '$correo_usuario' AND server_customers_id = '$server_customers_id'";
				
				mainModel::connectionLogin()->query($updateDBMainUsers);
			}

			//OBTENEMOS EL NOMBRE DE LA EMPRESA
			$empresa_id_sesion = $_SESSION['empresa_id_sd'];
			$tablaEmpresa = "empresa";
			$camposEmpresa = ["nombre"];
			$condicionesEmpresa = ["empresa_id" => $empresa_id_sesion];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];
			$resultadoEmpresa = $database->consultarTabla($tablaEmpresa, $camposEmpresa, $condicionesEmpresa, $orderBy, $tablaJoin, $condicionesJoin);
		
			$empresa_nombre = "";
		
			if (!empty($resultadoEmpresa)) {
				$empresa_nombre = strtoupper(trim($resultadoEmpresa[0]['nombre']));
			}				

			$correo_tipo_id = "1";//Notificaciones
			$destinatarios = array($correo_usuario => $colaborador_nombre);

			// Destinatarios en copia oculta (Bcc)
			$bccDestinatarios = [];

			$asunto = "¡Cambio de Contraseña Exitoso!";
			$mensaje = '
				<div style="padding: 20px;">
					<p style="margin-bottom: 10px;">
						¡Hola '.$colaborador_nombre.'!
					</p>
					
					<p style="margin-bottom: 10px;">
						Esperamos que te encuentres bien. Queremos informarte que se ha realizado con éxito el cambio de tu contraseña en nuestro sistema IZZY. Esta solicitud de cambio de contraseña fue iniciada por ti, por lo que no debes preocuparte.
					</p>								
					
					<p style="margin-bottom: 10px;">
						Tu nueva contraseña temporal es: <b>'.$contraseña.'</b>
					</p>	

					<p style="margin-bottom: 10px;">
						Te recomendamos que inicies sesión usando esta contraseña temporal y luego cambies tu contraseña por una de tu elección en la sección de configuración de tu cuenta. Puedes acceder al Sistema IZZY haciendo clic en el siguiente enlace:
					</p>											
					
					<p style="margin-bottom: 10px;">
						<a href='.SERVERURL.'>Clic para Acceder a IZZY<a>
					</p>
					
					<p style="margin-bottom: 10px;">
						La seguridad de tu cuenta es de suma importancia para nosotros. Si no reconoces esta acción o tienes alguna pregunta, por favor, contáctanos de inmediato. Estamos aquí para brindarte la ayuda que necesitas y asegurarnos de que tu experiencia sea segura y sin problemas.
					</p>
																			
					<p style="margin-bottom: 10px;">
						Agradecemos tu confianza en CLINICARE y esperamos seguir ofreciéndote un servicio excepcional.
					</p>
					
					<p style="margin-bottom: 10px;">
						Saludos cordiales,
					</p>
					
					<p>
						<b>El Equipo de '.$empresa_nombre.'</b></b>
					</p>                
				</div>
			';
			
			$archivos_adjuntos = [];
			$respuesta = $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $empresa_id_sesion, $archivos_adjuntos);
			
			return $respuesta;
		}

		public function resetear_contraseña_login_controlador(){
			$database = new Database();
			$sendEmail = new sendEmail();

			$respuesta = 0;
			$contraseña = cambiarContraseñaModelo::generar_pass_complejo();			
			$usu_forgot = $_POST['usu_forgot'];
			$contraseña_encriptada = mainModel::encryption($contraseña); 

			//CONSULTAMOS LA DB DEL USUARIO
			$query = "SELECT u.users_id, u.server_customers_id, CONCAT(c.nombre,' ',c.apellido) AS 'colaborador_nombre', s.db
				FROM users AS u
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				LEFT JOIN server_customers AS s
				ON u.server_customers_id = s.server_customers_id
				WHERE email = '$usu_forgot'";

			$resultUser = mainModel::connectionLogin()->query($query);

			if($resultUser->num_rows>0){
				$ConsultaDBPrincipal = $resultUser->fetch_assoc();
				$db_cosulta = $ConsultaDBPrincipal['db'];
				$colaborador_nombre = $ConsultaDBPrincipal['colaborador_nombre'];
				$server_customers_id = $ConsultaDBPrincipal['server_customers_id'];

				//OBTENEMOS EL USUARIO ID
				$query = "SELECT u.users_id , e.nombre AS 'empresa_nombre'
					FROM users AS u
					INNER JOIN empresa AS e
					ON u.empresa_id = e.empresa_id
					WHERE email = '$usu_forgot' AND server_customers_id = '$server_customers_id'";
				$resultQuery = mainModel::connectionDBLocal($db_cosulta)->query($query);
				$ConsultaQuery = $resultQuery->fetch_assoc();
				$users_id = $ConsultaQuery['users_id'];	
				$empresa_nombre = strtoupper(trim($ConsultaQuery['empresa_nombre']));
					
				//ACTUALIZAMOS LOS DATOS DEL USUARIO
				$update = "UPDATE users 
					SET 
						password = '$contraseña_encriptada' 
					WHERE users_id = '$users_id'";

				mainModel::connectionDBLocal($db_cosulta)->query($update);

				if($db_cosulta !== $GLOBALS['DB_MAIN']) {
					//ACTUALIZAMOS LA CONTASEÑA DEL USUARIO EN LA DB PRINCIPAL
					$updateDBMainUsers = "UPDATE users 
						SET 
							password = '$contraseña_encriptada' 
						WHERE email = '$usu_forgot' AND server_customers_id = '$server_customers_id'";
					
					mainModel::connectionLogin()->query($updateDBMainUsers);
				}								

				$correo_tipo_id = "1";//Notificaciones
				$destinatarios = array($usu_forgot => $colaborador_nombre);

				// Destinatarios en copia oculta (Bcc)
				$bccDestinatarios = [];

				$asunto = "¡Cambio de Contraseña Exitoso!";
				$mensaje = '
					<div style="padding: 20px;">
						<p style="margin-bottom: 10px;">
							¡Hola '.$colaborador_nombre.'!
						</p>
						
						<p style="margin-bottom: 10px;">
							Esperamos que te encuentres bien. Queremos informarte que se ha realizado con éxito el cambio de tu contraseña en nuestro sistema IZZY. Esta solicitud de cambio de contraseña fue iniciada por ti, por lo que no debes preocuparte.
						</p>								
						
						<p style="margin-bottom: 10px;">
							Tu nueva contraseña temporal es: <b>'.$contraseña.'</b>
						</p>	

						<p style="margin-bottom: 10px;">
							Te recomendamos que inicies sesión usando esta contraseña temporal y luego cambies tu contraseña por una de tu elección en la sección de configuración de tu cuenta. Puedes acceder al Sistema IZZY haciendo clic en el siguiente enlace:
						</p>											
						
						<p style="margin-bottom: 10px;">
							<a href='.SERVERURL.'>Clic para Acceder a IZZY<a>
						</p>
						
						<p style="margin-bottom: 10px;">
							La seguridad de tu cuenta es de suma importancia para nosotros. Si no reconoces esta acción o tienes alguna pregunta, por favor, contáctanos de inmediato. Estamos aquí para brindarte la ayuda que necesitas y asegurarnos de que tu experiencia sea segura y sin problemas.
						</p>
																				
						<p style="margin-bottom: 10px;">
							Agradecemos tu confianza en CLINICARE y esperamos seguir ofreciéndote un servicio excepcional.
						</p>
						
						<p style="margin-bottom: 10px;">
							Saludos cordiales,
						</p>
						
						<p>
							<b>El Equipo de '.$empresa_nombre.'</b>
						</p>                
					</div>
				';

				$archivos_adjuntos = [];
				$respuesta = $sendEmail->enviarCorreo($destinatarios, $bccDestinatarios, $asunto, $mensaje, $correo_tipo_id, $users_id, $archivos_adjuntos);				
			}else{
				$respuesta = 3;//USUARIO O CORREO NO EXISTEN	
			}

			$result_valid_user = cambiarContraseñaModelo::valid_user($usu_forgot);
			
			return $respuesta;
		}			
	}