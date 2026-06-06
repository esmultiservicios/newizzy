<?php
	// cambiarContraseñaControlador.php

    if($peticionAjax){
        require_once "../modelos/cambiarContraseñaModelo.php";
		require_once "../core/Database.php";
		require_once "../core/correo/sendEmail.php";
    }else{
        require_once "./modelos/cambiarContraseñaModelo.php";
		require_once "./core/Database.php";
		require_once "./core/correo/sendEmail.php";
    }
	
	class cambiarContraseñaControlador extends cambiarContraseñaModelo{

		public function edit_contraseña_controlador(){
			$contraseña = mainModel::encryption($_POST['nuevacontra']);

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

			$users_id = $_SESSION['users_id_sd'];

			$datos = [
				"users_id" => $users_id,
				"contraseña" => $contraseña
			];

			$query = cambiarContraseñaModelo::edit_contraseña_modelo($datos);
			
			if($query){

				/* =========================================================
				   OBTENER NOMBRE DEL COLABORADOR
				   OJO: La tabla colaboradores NO tiene apellido
				========================================================= */

				$tablColaborador = "colaboradores";
				$camposColaborador = ["nombre"];
				$condicionesColaborador = ["colaboradores_id" => $users_id];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];

				$resultadoColaborador = $database->consultarTabla(
					$tablColaborador,
					$camposColaborador,
					$condicionesColaborador,
					$orderBy,
					$tablaJoin,
					$condicionesJoin
				);

				$colaborador_nombre = "";

				if (!empty($resultadoColaborador)) {
					$colaborador_nombre = trim($resultadoColaborador[0]['nombre']);
				}

				if ($colaborador_nombre == "") {
					$colaborador_nombre = "Usuario";
				}
			
				/* =========================================================
				   OBTENER CORREO DEL USUARIO
				========================================================= */

				$tablaUsuario = "users";
				$camposUsuario = ["email", "server_customers_id"];
				$condicionesUsuario = ["users_id" => $users_id];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];

				$resultadoUsuario = $database->consultarTabla(
					$tablaUsuario,
					$camposUsuario,
					$condicionesUsuario,
					$orderBy,
					$tablaJoin,
					$condicionesJoin
				);

				$correo_usuario = "";
				$server_customers_id = 0;
				$estado = 1;

				if (!empty($resultadoUsuario)) {
					$correo_usuario = trim($resultadoUsuario[0]['email']);
					$server_customers_id = trim($resultadoUsuario[0]['server_customers_id']);
				}
				
				if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
					$updateDBMainUsers = "UPDATE users 
						SET 
							password = '$contraseña',
							estado = '$estado'
						WHERE email = '$correo_usuario' 
						  AND server_customers_id = '$server_customers_id'";

					mainModel::connectionLogin()->query($updateDBMainUsers);
				}

				/* =========================================================
				   OBTENER NOMBRE DE LA EMPRESA
				========================================================= */

				$empresa_id_sesion = $_SESSION['empresa_id_sd'];

				$tablaEmpresa = "empresa";
				$camposEmpresa = ["nombre"];
				$condicionesEmpresa = ["empresa_id" => $empresa_id_sesion];
				$orderBy = "";
				$tablaJoin = "";
				$condicionesJoin = [];

				$resultadoEmpresa = $database->consultarTabla(
					$tablaEmpresa,
					$camposEmpresa,
					$condicionesEmpresa,
					$orderBy,
					$tablaJoin,
					$condicionesJoin
				);
			
				$empresa_nombre = "";
			
				if (!empty($resultadoEmpresa)) {
					$empresa_nombre = strtoupper(trim($resultadoEmpresa[0]['nombre']));
				}

				if ($empresa_nombre == "") {
					$empresa_nombre = "IZZY";
				}

				/* =========================================================
				   ENVÍO DE CORREO
				   Si el correo falla, NO debe deshacer el cambio.
				========================================================= */

				if ($correo_usuario != "" && filter_var($correo_usuario, FILTER_VALIDATE_EMAIL)) {
					try {
						$sendEmail = new sendEmail();

						$correo_tipo_id = "1";

						$destinatarios = [
							$correo_usuario => $colaborador_nombre
						];

						$bccDestinatarios = [];

						$asunto = "¡Cambio de Contraseña Exitoso!";

						$mensaje = '
							<div style="padding: 20px;">
								<p style="margin-bottom: 10px;">
									¡Hola '.$colaborador_nombre.'!
								</p>
								
								<p style="margin-bottom: 10px;">
									Esperamos que se encuentre bien. Queremos informarle que se ha realizado con éxito el cambio de contraseña en el sistema IZZY.
								</p>								
								
								<p style="margin-bottom: 10px;">
									Si usted no realizó esta acción, le recomendamos ingresar al sistema y cambiar nuevamente su contraseña desde la configuración de su cuenta.
								</p>					
								
								<p style="margin-bottom: 10px;">
									<a href="'.SERVERURL.'">Clic para Acceder a IZZY</a>
								</p>
								
								<p style="margin-bottom: 10px;">
									La seguridad de su cuenta es muy importante para nosotros.
								</p>
																						
								<p style="margin-bottom: 10px;">
									Agradecemos su confianza en '.$empresa_nombre.'.
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

						$sendEmail->enviarCorreo(
							$destinatarios,
							$bccDestinatarios,
							$asunto,
							$mensaje,
							$correo_tipo_id,
							$empresa_id_sesion,
							$archivos_adjuntos
						);

					} catch (Exception $e) {

					} catch (Error $e) {

					}
				}
				
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
					"title" => "Ocurrió un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger"
				];
			}
			
			return mainModel::sweetAlert($alert);
		}

		public function resetear_contraseña_controlador(){
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
			
			$contraseña = cambiarContraseñaModelo::generar_pass_complejo();
			$users_id = $_POST['users_id'];
			$server_customers_id = $_POST['server_customers_id'];
			$contraseña_encriptada = mainModel::encryption($contraseña);

			$datos = [
				"users_id" => $users_id,
				"contraseña" => $contraseña_encriptada
			];

			/* =========================================================
			   OBTENER NOMBRE DEL COLABORADOR
			   OJO: La tabla colaboradores NO tiene apellido
			========================================================= */

			$respuesta = 0;

			$tablColaborador = "colaboradores";
			$camposColaborador = ["nombre"];
			$condicionesColaborador = ["colaboradores_id" => $users_id];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];

			$resultadoColaborador = $database->consultarTabla(
				$tablColaborador,
				$camposColaborador,
				$condicionesColaborador,
				$orderBy,
				$tablaJoin,
				$condicionesJoin
			);

			$colaborador_nombre = "";

			if (!empty($resultadoColaborador)) {
				$colaborador_nombre = trim($resultadoColaborador[0]['nombre']);
			}

			if ($colaborador_nombre == "") {
				$colaborador_nombre = "Usuario";
			}

			/* =========================================================
			   OBTENER CORREO DEL USUARIO
			========================================================= */

			$tablaUsuario = "users";
			$camposUsuario = ["email"];
			$condicionesUsuario = ["users_id" => $users_id];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];

			$resultadoUsuario = $database->consultarTabla(
				$tablaUsuario,
				$camposUsuario,
				$condicionesUsuario,
				$orderBy,
				$tablaJoin,
				$condicionesJoin
			);

			$correo_usuario = "";

			if (!empty($resultadoUsuario)) {
				$correo_usuario = trim($resultadoUsuario[0]['email']);
			}

			$query = cambiarContraseñaModelo::edit_contraseña_modelo($datos);

			if($GLOBALS['db'] !== $GLOBALS['DB_MAIN']) {
				$updateDBMainUsers = "UPDATE users 
					SET password = '$contraseña_encriptada' 
					WHERE email = '$correo_usuario' 
					  AND server_customers_id = '$server_customers_id'";
				
				mainModel::connectionLogin()->query($updateDBMainUsers);
			}

			/* =========================================================
			   OBTENER NOMBRE DE LA EMPRESA
			========================================================= */

			$empresa_id_sesion = $_SESSION['empresa_id_sd'];

			$tablaEmpresa = "empresa";
			$camposEmpresa = ["nombre"];
			$condicionesEmpresa = ["empresa_id" => $empresa_id_sesion];
			$orderBy = "";
			$tablaJoin = "";
			$condicionesJoin = [];

			$resultadoEmpresa = $database->consultarTabla(
				$tablaEmpresa,
				$camposEmpresa,
				$condicionesEmpresa,
				$orderBy,
				$tablaJoin,
				$condicionesJoin
			);
		
			$empresa_nombre = "";
		
			if (!empty($resultadoEmpresa)) {
				$empresa_nombre = strtoupper(trim($resultadoEmpresa[0]['nombre']));
			}

			if ($empresa_nombre == "") {
				$empresa_nombre = "IZZY";
			}

			if($query && $correo_usuario != "" && filter_var($correo_usuario, FILTER_VALIDATE_EMAIL)) {
				try {
					$sendEmail = new sendEmail();

					$correo_tipo_id = "1";

					$destinatarios = [
						$correo_usuario => $colaborador_nombre
					];

					$bccDestinatarios = [];

					$asunto = "¡Cambio de Contraseña Exitoso!";

					$mensaje = '
						<div style="padding: 20px;">
							<p style="margin-bottom: 10px;">
								¡Hola '.$colaborador_nombre.'!
							</p>
							
							<p style="margin-bottom: 10px;">
								Esperamos que se encuentre bien. Queremos informarle que se ha realizado con éxito el cambio de contraseña en el sistema IZZY.
							</p>								
							
							<p style="margin-bottom: 10px;">
								Su nueva contraseña temporal es: <b>'.$contraseña.'</b>
							</p>	

							<p style="margin-bottom: 10px;">
								Le recomendamos iniciar sesión usando esta contraseña temporal y luego cambiarla por una de su elección.
							</p>											
							
							<p style="margin-bottom: 10px;">
								<a href="'.SERVERURL.'">Clic para Acceder a IZZY</a>
							</p>
							
							<p style="margin-bottom: 10px;">
								La seguridad de su cuenta es muy importante para nosotros.
							</p>
																			
							<p style="margin-bottom: 10px;">
								Agradecemos su confianza en '.$empresa_nombre.'.
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

					$respuesta = $sendEmail->enviarCorreo(
						$destinatarios,
						$bccDestinatarios,
						$asunto,
						$mensaje,
						$correo_tipo_id,
						$empresa_id_sesion,
						$archivos_adjuntos
					);

				} catch (Exception $e) {
					$respuesta = 1;
				} catch (Error $e) {
					$respuesta = 1;
				}
			}else{
				$respuesta = 0;
			}
			
			return $respuesta;
		}

		public function resetear_contraseña_login_controlador(){
			$database = new Database();

			$respuesta = 0;
			$contraseña = cambiarContraseñaModelo::generar_pass_complejo();
			$usu_forgot = $_POST['usu_forgot'];
			$contraseña_encriptada = mainModel::encryption($contraseña);

			/* =========================================================
			   CONSULTAR LA DB DEL USUARIO
			   OJO: colaboradores NO tiene apellido
			========================================================= */

			$query = "SELECT 
					u.users_id,
					u.server_customers_id,
					c.nombre AS colaborador_nombre,
					s.db
				FROM users AS u
				INNER JOIN colaboradores AS c
					ON u.colaboradores_id = c.colaboradores_id
				LEFT JOIN server_customers AS s
					ON u.server_customers_id = s.server_customers_id
				WHERE u.email = '$usu_forgot'";

			$resultUser = mainModel::connectionLogin()->query($query);

			if($resultUser && $resultUser->num_rows > 0){
				$ConsultaDBPrincipal = $resultUser->fetch_assoc();

				$db_cosulta = $ConsultaDBPrincipal['db'];
				$colaborador_nombre = $ConsultaDBPrincipal['colaborador_nombre'];
				$server_customers_id = $ConsultaDBPrincipal['server_customers_id'];

				if ($colaborador_nombre == "") {
					$colaborador_nombre = "Usuario";
				}

				$query = "SELECT 
						u.users_id,
						e.empresa_id,
						e.nombre AS empresa_nombre
					FROM users AS u
					INNER JOIN empresa AS e
						ON u.empresa_id = e.empresa_id
					WHERE u.email = '$usu_forgot' 
					  AND u.server_customers_id = '$server_customers_id'";

				$resultQuery = mainModel::connectionDBLocal($db_cosulta)->query($query);

				if($resultQuery && $resultQuery->num_rows > 0){
					$ConsultaQuery = $resultQuery->fetch_assoc();

					$users_id = $ConsultaQuery['users_id'];
					$empresa_id = $ConsultaQuery['empresa_id'];
					$empresa_nombre = strtoupper(trim($ConsultaQuery['empresa_nombre']));
						
					$update = "UPDATE users 
						SET password = '$contraseña_encriptada' 
						WHERE users_id = '$users_id'";

					mainModel::connectionDBLocal($db_cosulta)->query($update);

					if($db_cosulta !== $GLOBALS['DB_MAIN']) {
						$updateDBMainUsers = "UPDATE users 
							SET password = '$contraseña_encriptada' 
							WHERE email = '$usu_forgot' 
							  AND server_customers_id = '$server_customers_id'";
						
						mainModel::connectionLogin()->query($updateDBMainUsers);
					}

					if ($empresa_nombre == "") {
						$empresa_nombre = "IZZY";
					}

					if($usu_forgot != "" && filter_var($usu_forgot, FILTER_VALIDATE_EMAIL)) {
						try {
							$sendEmail = new sendEmail();

							$correo_tipo_id = "1";

							$destinatarios = [
								$usu_forgot => $colaborador_nombre
							];

							$bccDestinatarios = [];

							$asunto = "¡Cambio de Contraseña Exitoso!";

							$mensaje = '
								<div style="padding: 20px;">
									<p style="margin-bottom: 10px;">
										¡Hola '.$colaborador_nombre.'!
									</p>
									
									<p style="margin-bottom: 10px;">
										Esperamos que se encuentre bien. Queremos informarle que se ha realizado con éxito el cambio de contraseña en el sistema IZZY.
									</p>								
									
									<p style="margin-bottom: 10px;">
										Su nueva contraseña temporal es: <b>'.$contraseña.'</b>
									</p>	

									<p style="margin-bottom: 10px;">
										Le recomendamos iniciar sesión usando esta contraseña temporal y luego cambiarla por una de su elección.
									</p>											
									
									<p style="margin-bottom: 10px;">
										<a href="'.SERVERURL.'">Clic para Acceder a IZZY</a>
									</p>
									
									<p style="margin-bottom: 10px;">
										La seguridad de su cuenta es muy importante para nosotros.
									</p>
																						
									<p style="margin-bottom: 10px;">
										Agradecemos su confianza en '.$empresa_nombre.'.
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

							$respuesta = $sendEmail->enviarCorreo(
								$destinatarios,
								$bccDestinatarios,
								$asunto,
								$mensaje,
								$correo_tipo_id,
								$empresa_id,
								$archivos_adjuntos
							);

						} catch (Exception $e) {
							$respuesta = 1;
						} catch (Error $e) {
							$respuesta = 1;
						}
					}else{
						$respuesta = 1;
					}

				}else{
					$respuesta = 3;
				}

			}else{
				$respuesta = 3;
			}

			$result_valid_user = cambiarContraseñaModelo::valid_user($usu_forgot);
			
			return $respuesta;
		}
	}