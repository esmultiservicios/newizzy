<?php
    if($peticionAjax){		
        require_once "../modelos/loginModel.php";	
		require_once "../core/Database.php";	
    }else{		
        require_once "./modelos/loginModel.php";
		require_once "./core/Database.php";
    }
    
    class loginControlador extends loginModel{
		
		public function iniciar_sesion_controlador() {
			$username = mainModel::cleanString($_POST['inputEmail']);
			$password = mainModel::encryption($_POST['inputPassword']);
			$inputCliente = $_POST['inputCliente'];
			$inputPin = $_POST['inputPin'];
		
			// Antes de usar $GLOBALS['db'] en iniciar_sesion_controlador
			if (!isset($GLOBALS['db']) || empty($GLOBALS['db'])) {
				// Manejar el error o definir un valor predeterminado
				$GLOBALS['db'] = $GLOBALS['DB_MAIN'];
			}
				
			$respuesta = false;
			$query_server = "";
			$codigoCliente = "";
			$pingInvalido = false;
			$Consultacliente = false;
			$mantenimiento = false;
		
			if ($inputCliente !== "" && $inputPin !== "") {
				$query_server = "SELECT COALESCE(s.server_customers_id, '0') AS server_customers_id, COALESCE(s.db, '" . DB_MAIN . "') AS db, codigo_cliente
					FROM users AS u
					LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
					WHERE s.codigo_cliente = '$inputCliente'";
		
				$resultServerUser = mainModel::connectionLogin()->query($query_server);
		
				if ($resultServerUser->num_rows > 0) {
					$consultaServeruser = $resultServerUser->fetch_assoc();
					$codigoCliente = $consultaServeruser['codigo_cliente'];
				}
		
				$mysqliPin = mainModel::connectionDBLocal(DB_MAIN);
		
				$query = "SELECT pin FROM pin WHERE codigo_cliente = '$codigoCliente' AND pin = '$inputPin' AND fecha_hora_fin > NOW()";

				$resultPin = $mysqliPin->query($query) or die($mysqliPin->error);
		
				if ($resultPin->num_rows > 0) {
					$Consultacliente = true;
					$respuesta = true;
				} else {
					$pingInvalido = true;
					$respuesta = false;
				}
			} else if ($inputCliente === "" && $inputPin === "") {
				$query_server = "SELECT COALESCE(s.server_customers_id, '0') AS server_customers_id, COALESCE(s.db, '" . DB_MAIN . "') AS db, codigo_cliente
					FROM users AS u
					LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
					WHERE (BINARY u.email = '$username' OR BINARY u.username = '$username')";
				
				$respuesta = true;
			} else {
				$respuesta = false;
			}
		
			if ($respuesta) {
				$resultServerUser = mainModel::connectionLogin()->query($query_server);
		
				if ($resultServerUser->num_rows > 0) {
					$consultaServeruser = $resultServerUser->fetch_assoc();
					$codigoCliente = $consultaServeruser['codigo_cliente'];
					$GLOBALS['db'] = $consultaServeruser['db'] === "" ? $GLOBALS['DB_MAIN'] : $consultaServeruser['db'];
		
					$datosLogin = [
						"username" => $username,
						"password" => $password,
						"db" => $GLOBALS['db'],
					];
		
					if ($Consultacliente) {
						$datosLogin = [
							"username" => "admin",
							"password" => mainModel::encryption("C@M1Cl1n1c@r3"),
							"db" => $GLOBALS['db'],
						];
		
						$result = loginModel::iniciar_sesion_admin_modelo($datosLogin);
		
						if ($result->num_rows > 0) {
							$mantenimiento = true;
						}
					} else {
						$result = loginModel::iniciar_sesion_modelo($datosLogin);
					}
		
					if ($result->num_rows != 0) {
						$row = $result->fetch_assoc();
		
						$fechaActual = date("Y-m-d");
						$añoActual = date("Y");
						$horaActual = date("H:m:s");
		
						$query = "SELECT bitacora_id FROM bitacora";
						$result1 = mainModel::ejecutar_consulta_simple($query);
		
						$numero = ($result1->num_rows) + 1;
						$codigoB = mainModel::getRandom("CB", 7, $numero);
		
						$datosBitacora = [
							"bitacoraCodigo" => $codigoB,
							"bitacoraFecha" => $fechaActual,
							"bitacoraHoraInicio" => $horaActual,
							"bitacoraHoraFinal" => "Sin Registro",
							"bitacoraTipo" => $row['tipo_user_id'],
							"bitacoraYear" => $añoActual,
							"user_id" => $row['users_id']
						];
		
						$insertarBitacora = mainModel::guardar_bitacora($datosBitacora);
		
						if ($insertarBitacora) {
							$_SESSION['users_id_sd'] = $row['users_id'];
							$_SESSION['user_sd'] = $row['users_id'];
							$_SESSION['tipo_sd'] = $row['cuentaTipo'];
							$_SESSION['privilegio_sd'] = $row['privilegio_id'];
							$_SESSION['tipo_user_id_sd'] = $row['tipo_user_id'];
							$_SESSION['token_sd'] = uniqid(mt_rand(), true);
							$_SESSION['server_token'] = $_SESSION['token_sd'];
							$_SESSION['colaborador_id_sd'] = $row['colaboradores_id'];
							$_SESSION['empresa_id_sd'] = $row['empresa_id'];
							$_SESSION['server_customers_id'] = $row['server_customers_id'];
							$_SESSION['codigo_bitacora_sd'] = $codigoB;
							$_SESSION['identidad'] = $row['identidad'];
							$_SESSION['codigoCliente'] = $codigoCliente;
							$_SESSION['session_time'] = time();

							//CONSULTAMOS EL PLAN ACTIVO DEL CLIENTE
							$resultPlanSistema = mainModel::getPlanSistema()->fetch_assoc();
							$_SESSION['planes_id'] = $resultPlanSistema['plan_id'];

							if ($mantenimiento) {
								$_SESSION['modo_soporte'] = "SI";
							} else {
								$_SESSION['modo_soporte'] = "NO";
							}
		
							$_SESSION['db_cliente'] = $consultaServeruser['db'];
		
							$result_consultaMenu = loginModel::getMenuAccesoLogin($row['privilegio_id']);
		
							if ($result_consultaMenu->num_rows > 0) {
								$result_MenuAcceso = $result_consultaMenu->fetch_assoc();
								$consultaMenu = $result_MenuAcceso['name'];
		
								$url = SERVERURL . $consultaMenu . "/";
							} else {
								$result_consultaSubMenu = loginModel::getSubMenuAccesoLogin($row['privilegio_id']);
		
								if ($result_consultaSubMenu->num_rows > 0) {
									$result_SubMenuAcceso = $result_consultaSubMenu->fetch_assoc();
									$consultaSubMenu = $result_SubMenuAcceso['name'];
		
									$url = SERVERURL . $consultaSubMenu . "/";
								} else {
									$result_consultaSubMenu1 = loginModel::getSubMenu1AccesoLogin($row['privilegio_id']);
		
									if ($result_consultaSubMenu1->num_rows > 0) {
										$result_SubMenu1Acceso = $result_consultaSubMenu1->fetch_assoc();
										$consultaSubMenu1 = $result_SubMenu1Acceso['name'];
		
										$url = SERVERURL . $consultaSubMenu1 . "/";
									} else {
										$url = SERVERURL . "dashboard/";
									}
								}
							}
		
							$datos = [
								0 => $url,
								1 => "",
							];
						} else {
							$datos = [
								0 => "",
								1 => "Error",
							];
						}
					} else {
						$datos = [
							0 => "",
							1 => "ErrorS",
						];
					}
				} else {
					$datos = [
						0 => "",
						1 => "ErrorC",
					];
				}
			} else {
				if ($pingInvalido) {
					$datos = [
						0 => "",
						1 => "ErrorPinInvalido",
					];
				} else {
					$datos = [
						0 => "",
						1 => "ErrorVacio",
					];
				}
			}
		
			return json_encode($datos);
		}		
		
		public function cerrar_sesion_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
		
			$token = $_SESSION['server_token'];
			$hora = date("H:m:s");
		
			$datos = [
				"usuario" => $_SESSION['user_sd'],
				"token_s" => $_SESSION['token_sd'],
				"token" => $token,
				"codigo" => $_SESSION['codigo_bitacora_sd'],
				"hora" => $hora,                
			];
		
			mainModel::guardar_historial_accesos("Cierre de Sesion");
		
			$resultado_cierre = loginModel::cerrar_sesion_modelo($datos);
			
			if ($resultado_cierre == 1) {
				return 1; // Sesion cerrada correctamente
			} else {
				return 0; // Error al cerrar la sesión
			}
		}		
		
		public function forzar_cierre_sesion_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
			}
			
			mainModel::guardar_historial_accesos("Cierre de Sesion Forzado");
			session_destroy();
			return header("Location: ".SERVERURL."login/");
		}	
		
		public function validar_pago_pendiente_main_server_controlador(){
			$username = mainModel::cleanString($_POST['inputEmail']);
			$password = mainModel::encryption($_POST['inputPassword']);
			
			$query_server = "SELECT COALESCE(s.server_customers_id, '0') AS server_customers_id, COALESCE(s.db, '" . DB_MAIN . "') AS db, codigo_cliente
								FROM users AS u
								LEFT JOIN server_customers AS s ON u.server_customers_id = s.server_customers_id
								WHERE (BINARY u.email = '$username' OR BINARY u.username = '$username')";
			
			
			//echo $query_server."***";
			$resultServerUser = mainModel::connectionLogin()->query($query_server);
					
			if ($resultServerUser->num_rows > 0) {
				$consultaServeruser = $resultServerUser->fetch_assoc();
				$GLOBALS['db'] = $consultaServeruser['db'] === "" ? $GLOBALS['DB_MAIN'] : $consultaServeruser['db'];
			}	


			$result = loginModel::validar_pago_pendiente_main_server_modelo();
			$result_validar_cliente = loginModel::validar_cliente_server_modelo();
				
			
			$date = date("Y-m-d");
			$año = date("Y");
			$mes = date("m");
	
			$fecha_inicial = date("Y-m-d", strtotime($año."-".$mes."-01"));
			$fecha_final = date("Y-m-d", strtotime($año."-".$mes."-15"));
	
			//SI NOS ESTAMOS CONECTANDO AL SISTEMA PRINCIPAL, SIMPLEMENTE ENTRAMOS SIN PROBLEMA
			if($GLOBALS['db'] == DB_MAIN_LOGIN_CONTROLADOR){
				$datos = 1;
			}else{			
				$result_pagoVencido = loginModel::validar_cliente_pagos_vencidos_main_server_modelo();//METODO QUE VALIDA LOS PAGOS VENCIDOS DE LOS CLIENTES

				$row = $result_validar_cliente->fetch_assoc();

				//EVALUAMOS QUE LA VARIABLE VALIDAR NO VENGA VACIA O NULA
				$validar = $row['validar'] ?? 0;
				
				//CONSULTAMOS SI ES NECESARIO VALIDAR EL CLIENTE, SI NO LO ES, LO DEJAMOS INICIAR SESION CORRECTAMENTE
				if($validar==0){
					$datos = 1;
				}else{
					//VALIDAMOS SI EL CLIENTE TIENE PAGOS VENCIDOS
					//echo "El valor que se recibe es: ".$result_pagoVencido->num_rows."***";
					if($result_pagoVencido->num_rows >= 1){
						$datos = array(
							0 => "",
							1 => "ErrorP",
						);	
					}else{//SI EL CLIENTE NO TIENE PAGOS VENCIDOS SOLO EVALUAMOS LOS PAGOS PENDIENTES DEL MES  EN CURSO	
						//CONSULTAMOS SI HAY PENDIENTES DE PAGO POR EL CLIENTE
						if($result->num_rows == 1){//SI ENCUENTRA UN REGISTRO PENDIENTE DE PAGO
							if($date >= $fecha_inicial && $date <= $fecha_final){//EVALUAMOS SI ESTA DENTRO DE LA FECHA PERMITIDA PARA INICIAR SESION CON O SIN PAGO EFECUTADO
								$datos = 1;
							}else{//SI SE HA PASADO DE LA FECHA PERMITIDA DE PAGO, NO DEJAMOS INICIAR SESION AL CLIENTE
								$datos = array(
									0 => "",
									1 => "ErrorP",
								);	
							}
						}else if($result->num_rows > 1){//SI ENCONTRAMOS MAS DE UN REGISTRO DE PAGO PENDIENTE, NO DEJAMOS INICIAR SESION AL CLIENTE
							$datos = array(
								0 => "",
								1 => "ErrorP",
							);	
						}else{//SI NO ENCONTRAMOS NINGUN REGISTO PENDIENTE DE PAGO, DEJAMOS INICIAR SESION AL CLIENTE
							$datos = 1;
						}
					}					
				}				
			}

			return json_encode($datos);
		}
    }