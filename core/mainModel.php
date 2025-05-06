<?php
// core/mainModel.php

// Determinar la ruta base según si es petición Ajax o no
if (isset($peticionAjax)) {
    $basePath = dirname(__DIR__) . '/core/';
} else {
    $basePath = __DIR__ . '/';
}

// Incluir archivos de configuración
require_once $basePath . 'configAPP.php';
require_once $basePath . 'phpmailer/class.phpmailer.php';
require_once $basePath . 'phpmailer/class.smtp.php';

class mainModel
{
	/* FUNCTION QUE PERMITE REALIZAR LA CONEXIÓN A LA DB */
	public function connection()
	{
		// Desactiva la conexión persistente removiendo 'p:'
		$mysqli = new mysqli(SERVER, USER, PASS);
	
		if ($mysqli->connect_errno) {
			throw new Exception('Fallo al conectar a MySQL, connection: ' . $mysqli->connect_error);
		}
	
		$mysqli->set_charset('utf8mb4'); // Usamos utf8mb4 para soporte completo de caracteres
	
		// Intenta seleccionar la base de datos
		if (!$mysqli->select_db($GLOBALS['db'])) {
			throw new Exception('Error al seleccionar la base de datos desde mainModel.php, connection: ' . $mysqli->error);
		}
	
		return $mysqli;
	}

	// En tu mainModel.php agrega:
	public static function staticConnection() {
		$instance = new self();
		return $instance->connection();
	}
		
	public function connectionLogin()
	{
		// Usamos conexiones persistentes con 'p:'
		$mysqliLogin = new mysqli('p:' . SERVER, USER, PASS);
	
		if ($mysqliLogin->connect_errno) {
			throw new Exception('Fallo al conectar a MySQL, connectionLogin: ' . $mysqliLogin->connect_error);
		}
	
		$mysqliLogin->set_charset('utf8mb4'); // Usamos utf8mb4 para soporte completo de caracteres
	
		// Intenta seleccionar la base de datos
		if (!$mysqliLogin->select_db($GLOBALS['DB_MAIN'])) {
			throw new Exception('Error al seleccionar la base de datos desde mainModel.php, connectionLogin: ' . $mysqliLogin->error);
		}
	
		return $mysqliLogin;
	}
	
	public function connectionDBLocal($dblocal)
	{
		// Usamos conexiones persistentes con 'p:'
		$mysqliDBLocal = new mysqli('p:' . SERVER, USER, PASS);
	
		if ($mysqliDBLocal->connect_errno) {
			throw new Exception('Fallo al conectar a MySQL, connectionDBLocal: ' . $mysqliDBLocal->connect_error);
		}
	
		$mysqliDBLocal->set_charset('utf8mb4'); // Usamos utf8mb4 para soporte completo de caracteres
	
		// Intenta seleccionar la base de datos
		if (!$mysqliDBLocal->select_db($dblocal)) {
			throw new Exception('Error al seleccionar la base de datos desde mainModel.php, connectionDBLocal: ' . $mysqliDBLocal->error);
		}
	
		return $mysqliDBLocal;
	}	

	public function connectToDatabase($config) {
		$conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['name']);
		
		if ($conn->connect_error) {
			error_log("Error conectando a DB cliente: " . $conn->connect_error);
			return false;
		}
		
		$conn->set_charset("utf8");
		return $conn;
	}

	public function databaseExists($dbName) {
		$conn = new mysqli(SERVER, USER, PASS);
		if ($conn->connect_error) {
			return false;
		}
		
		$result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
		$exists = ($result->num_rows > 0);
		$conn->close();
		
		return $exists;
	}

	// En tu mainModel.php agregamos:
	public static function validarSesion() {
		// Verificar si la sesión no está iniciada
		if(session_status() === PHP_SESSION_NONE) {
			session_start([
				'name' => 'SD', 
				'cookie_lifetime' => 86400,
				'cookie_secure' => true,
				'cookie_httponly' => true,
				'cookie_samesite' => 'Lax'
			]);
			
			if(!isset($_SESSION['user_sd'])) {
				$_SESSION['user_sd'] = null;
			}
		}
		
		// Verificar si el usuario está logueado
		if($_SESSION['user_sd'] === null) {
			return [
				"error" => true,
				"mensaje" => "Debe iniciar sesión para acceder a esta función",
				"redireccion" => SERVERURL."login/"
			];
		}
		
		// Verificar variables de sesión esenciales
		$variablesRequeridas = ['colaborador_id_sd', 'empresa_id_sd'];
		foreach($variablesRequeridas as $variable) {
			if(!isset($_SESSION[$variable])) {
				return [
					"error" => true,
					"mensaje" => "Faltan datos esenciales en la sesión",
					"redireccion" => SERVERURL."login/"
				];
			}
		}
		
		return ["error" => false];
	}	

	public function correlativoLogin($campo_id, $tabla)
	{
		$query = 'SELECT MAX(' . $campo_id . ') AS max, COUNT(' . $campo_id . ') AS count FROM ' . $tabla;
		$result = self::connectionLogin()->query($query);
		$correlativo2 = $result->fetch_assoc();
		$numero = $correlativo2['max'];
		$cantidad = $correlativo2['count'];

		if ($cantidad == 0)
			$numero = 1;
		else
			$numero = $numero + 1;

		return $numero;
	}
	

	public function consulta_total_ingreso($query)
	{
		$result = self::connection()->query($query);

		return $result;
	}
	// FUNCION CORRELATIVO	
		
	public function correlativo($campo_id, $tabla)
	{
		$query = 'SELECT MAX(' . $campo_id . ') AS max, COUNT(' . $campo_id . ') AS count FROM ' . $tabla;
		$result = self::connection()->query($query);

		if (!$result) {
			die('Error en la consulta: ' . self::connection()->error . ' - Query: ' . $query);
		}

		$correlativo2 = $result->fetch_assoc();
		$numero = $correlativo2['max'];
		$cantidad = $correlativo2['count'];

		if ($cantidad == 0)
			$numero = 1;
		else
			$numero = $numero + 1;

		return $numero;
	}

	protected function correlativoDBPrincipal($campo_id, $tabla)
	{
		$query = 'SELECT MAX(' . $campo_id . ') AS max, COUNT(' . $campo_id . ') AS count FROM ' . $tabla;
		$result = self::connectionDBLocal(DB_MAIN)->query($query);
		$correlativo2 = $result->fetch_assoc();
		$numero = $correlativo2['max'];
		$cantidad = $correlativo2['count'];

		if ($cantidad == 0)
			$numero = 1;
		else
			$numero = $numero + 1;

		return $numero;
	}
	
	public function obtener_planes_id_por_plan_id(){
		$query = "SELECT planes_id FROM plan"; // ejemplo con ID fijo
	
		$result = $this->ejecutar_consulta_simple($query); // sin pasar parámetros
	
		if($result && $result->num_rows > 0){
			$row = $result->fetch_assoc();
			return $row['planes_id'];
		}
		return null;
	}
	
	// Función para generar username único según las reglas especificadas
	public function generarUsernameUnico($nombre_completo) {
		// Separar el nombre completo en partes
		$partes_nombre = explode(' ', trim($nombre_completo));
		
		// Obtener primera letra del primer nombre (en minúscula)
		$primera_letra = strtolower(substr($partes_nombre[0], 0, 1));
		
		// Buscar el primer apellido (el siguiente elemento después del primer nombre)
		$primer_apellido = '';
		for ($i = 1; $i < count($partes_nombre); $i++) {
			if (!empty($partes_nombre[$i])) {
				$primer_apellido = strtolower($partes_nombre[$i]);
				break;
			}
		}
		
		// Si no hay apellido, usar solo la primera letra
		if (empty($primer_apellido)) {
			$username_base = $primera_letra;
		} else {
			$username_base = $primera_letra . $primer_apellido;
		}
		
		// Verificar si el username ya existe
		$username_final = $username_base;
		$contador = 1;
		
		while (true) {
			$check = mainModel::ejecutar_consulta_simple("SELECT users_id FROM users WHERE username = '$username_final'");
			
			if ($check->num_rows == 0) {
				break; // Username disponible
			}
			
			// Si existe, agregar número consecutivo
			$username_final = $username_base . str_pad($contador, 2, '0', STR_PAD_LEFT);
			$contador++;
			
			// Prevención por si acaso (nunca debería llegar a esto)
			if ($contador > 100) {
				$username_final = $username_base . uniqid();
				break;
			}
		}
		
		return $username_final;
	}	

	// En tu mainModel.php
	public function obtener_plan_modelo($plan_id) {
		$mainModel = new mainModel();
		$conexion = $mainModel->connection();
		
		try {
			$stmt = $conexion->prepare("SELECT * FROM planes WHERE planes_id = ?");
			$stmt->bind_param("i", $plan_id);
			$stmt->execute();
			
			$resultado = $stmt->get_result();
			
			if($resultado->num_rows == 1) {
				$plan = $resultado->fetch_assoc();
				
				// Limpiar datos de salida
				$plan['nombre'] = $this->cleanStringConverterCase($plan['nombre']);
				
				return [
					"success" => true,
					"data" => $plan
				];
			} else {
				return [
					"success" => false,
					"error" => "Plan no encontrado"
				];
			}
			
		} catch(Exception $e) {
			error_log("Error en obtener_plan_modelo: " . $e->getMessage());
			return [
				"success" => false,
				"error" => $e->getMessage()
			];
		} finally {
			$conexion->close();
		}
	}	
	public function registrar_plan_modelo($datos) {
		$conexionPrincipal = $this->connection();
		
		try {
			// Iniciar transacción
			$conexionPrincipal->autocommit(false);
	
			// 1. Registrar en la base de datos principal
			$stmt = $conexionPrincipal->prepare("INSERT INTO planes (
											  nombre,
											  estado,
											  fecha_registro,
											  configuraciones
											  ) VALUES (?, ?, ?, ?)");
			
			$stmt->bind_param("siss", 
				$datos['nombre'],
				$datos['estado'],
				$datos['fecha_registro'],
				$datos['configuraciones']
			);
			
			$stmt->execute();
			$insertId = $stmt->insert_id;
			
			// 2. Registrar en todas las bases de datos de clientes
			$clientes = $this->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
			
			foreach ($clientes as $cliente) {
				$dbName = $cliente['db'];
				
				// Verificar si la base de datos existe
				if ($this->databaseExists($dbName)) {
					$configCliente = [
						'host' => SERVER,
						'user' => USER,
						'pass' => PASS,
						'name' => $dbName
					];
					
					$connCliente = $this->connectToDatabase($configCliente);
					
					if ($connCliente !== false) {
						// Verificar si la tabla planes existe
						$tableExists = $connCliente->query("SHOW TABLES LIKE 'planes'");
						
						if ($tableExists->num_rows > 0) {
							// Insertar en la base de datos del cliente
							$stmtCliente = $connCliente->prepare("INSERT INTO planes (
															  planes_id,
															  nombre,
															  estado,
															  fecha_registro,
															  configuraciones
															  ) VALUES (?, ?, ?, ?, ?)");
							
							$stmtCliente->bind_param("isiss", 
								$insertId,
								$datos['nombre'],
								$datos['estado'],
								$datos['fecha_registro'],
								$datos['configuraciones']
							);
							
							$stmtCliente->execute();
							$stmtCliente->close();
						}
						$connCliente->close();
					}
				}
			}
			
			// Confirmar transacción
			$conexionPrincipal->commit();
			
			return [
				'success' => true,
				'insert_id' => $insertId,
				'affected_rows' => $stmt->affected_rows,
				'message' => 'Plan registrado correctamente en todas las bases de datos'
			];
			
		} catch (Exception $e) {
			// Revertir transacción en caso de error
			$conexionPrincipal->rollback();
			
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		} finally {
			if(isset($stmt)) $stmt->close();
			$conexionPrincipal->autocommit(true);
			$conexionPrincipal->close();
		}
	}

	public function actualizar_plan_modelo($datos) {
		$conexionPrincipal = $this->connection();
		
		try {
			// Iniciar transacción
			$conexionPrincipal->autocommit(false);
	
			// 1. Actualizar en la base de datos principal
			$stmt = $conexionPrincipal->prepare("UPDATE planes SET 
											  nombre = ?,
											  estado = ?,
											  configuraciones = ?
											  WHERE planes_id = ?");
			
			$stmt->bind_param("sisi", 
				$datos['nombre'],
				$datos['estado'],
				$datos['configuraciones'],
				$datos['plan_id']
			);
			
			$stmt->execute();
			$affectedRows = $stmt->affected_rows;
			
			// 2. Actualizar en todas las bases de datos de clientes
			$clientes = $this->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
			
			foreach ($clientes as $cliente) {
				$dbName = $cliente['db'];
				
				// Verificar si la base de datos existe
				if ($this->databaseExists($dbName)) {
					$configCliente = [
						'host' => SERVER,
						'user' => USER,
						'pass' => PASS,
						'name' => $dbName
					];
					
					$connCliente = $this->connectToDatabase($configCliente);
					
					if ($connCliente !== false) {
						// Verificar si la tabla planes existe
						$tableExists = $connCliente->query("SHOW TABLES LIKE 'planes'");
						
						if ($tableExists->num_rows > 0) {
							// Verificar si el plan existe en el cliente
							$planExists = $connCliente->query("SELECT 1 FROM planes WHERE planes_id = " . $datos['plan_id']);
							
							if ($planExists->num_rows > 0) {
								// Actualizar en la base de datos del cliente
								$stmtCliente = $connCliente->prepare("UPDATE planes SET 
																  nombre = ?,
																  estado = ?,
																  configuraciones = ?
																  WHERE planes_id = ?");
								
								$stmtCliente->bind_param("sisi", 
									$datos['nombre'],
									$datos['estado'],
									$datos['configuraciones'],
									$datos['plan_id']
								);
								
								$stmtCliente->execute();
							} else {
								// Insertar si no existe
								$stmtCliente = $connCliente->prepare("INSERT INTO planes (
																  planes_id,
																  nombre,
																  estado,
																  fecha_registro,
																  configuraciones
																  ) VALUES (?, ?, ?, NOW(), ?)");
								
								$stmtCliente->bind_param("isss", 
									$datos['plan_id'],
									$datos['nombre'],
									$datos['estado'],
									$datos['configuraciones']
								);
								
								$stmtCliente->execute();
							}
							$stmtCliente->close();
						}
						$connCliente->close();
					}
				}
			}
			
			// Confirmar transacción
			$conexionPrincipal->commit();
			
			return [
				'success' => true,
				'affected_rows' => $affectedRows,
				'message' => 'Plan actualizado correctamente en todas las bases de datos'
			];
			
		} catch (Exception $e) {
			// Revertir transacción en caso de error
			$conexionPrincipal->rollback();
			
			return [
				'success' => false,
				'error' => $e->getMessage()
			];
		} finally {
			if(isset($stmt)) $stmt->close();
			$conexionPrincipal->autocommit(true);
			$conexionPrincipal->close();
		}
	}

	public function eliminar_plan_modelo($planId) {
		$response = [
			'success' => false,
			'message' => ''
		];
	
		try {
			// Obtener conexión principal
			$conexionPrincipal = $this->connection();
			$conexionPrincipal->autocommit(false);
	
			// 1. Verificar si el plan tiene relaciones en tablas de permisos
			$tieneRelaciones = false;
			
			// Verificar en menu_plan
			$checkMenuPlan = $conexionPrincipal->query("SELECT 1 FROM menu_plan WHERE plan_id = '$planId' LIMIT 1");
			if ($checkMenuPlan->num_rows > 0) {
				$tieneRelaciones = true;
			}
			
			// Verificar en submenu_plan (si no se encontró en la anterior)
			if (!$tieneRelaciones) {
				$checkSubmenuPlan = $conexionPrincipal->query("SELECT 1 FROM submenu_plan WHERE plan_id = '$planId' LIMIT 1");
				if ($checkSubmenuPlan->num_rows > 0) {
					$tieneRelaciones = true;
				}
			}
			
			// Verificar en submenu1_plan (si no se encontró en las anteriores)
			if (!$tieneRelaciones) {
				$checkSubmenu1Plan = $conexionPrincipal->query("SELECT 1 FROM submenu1_plan WHERE plan_id = '$planId' LIMIT 1");
				if ($checkSubmenu1Plan->num_rows > 0) {
					$tieneRelaciones = true;
				}
			}
	
			if ($tieneRelaciones) {
				$conexionPrincipal->rollback();
				return [
					'success' => false,
					'message' => 'No se puede eliminar el plan porque tiene permisos asignados'
				];
			}
	
			// 2. Eliminar de la base principal
			$deletePrincipal = $conexionPrincipal->query("DELETE FROM planes WHERE plan_id = '$planId'");
			
			if (!$deletePrincipal) {
				$conexionPrincipal->rollback();
				return [
					'success' => false,
					'message' => 'Error al eliminar el plan de la base principal'
				];
			}
	
			// 3. Eliminar de todas las bases de datos de clientes
			$clientes = $this->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
			$erroresClientes = [];
	
			foreach ($clientes as $cliente) {
				$dbName = $cliente['db'];
				
				if ($this->databaseExists($dbName)) {
					$configCliente = [
						'host' => SERVER,
						'user' => USER,
						'pass' => PASS,
						'name' => $dbName
					];
					
					$connCliente = $this->connectToDatabase($configCliente);
					
					if ($connCliente !== false) {
						try {
							// Verificar si la tabla planes existe
							$tableExists = $connCliente->query("SHOW TABLES LIKE 'planes'");
							
							if ($tableExists->num_rows > 0) {
								// Verificar si el plan existe en el cliente
								$planExists = $connCliente->query("SELECT 1 FROM planes WHERE plan_id = '$planId'");
								
								if ($planExists->num_rows > 0) {
									// Eliminar de la base del cliente
									$deleteCliente = $connCliente->query("DELETE FROM planes WHERE plan_id = '$planId'");
									
									if (!$deleteCliente) {
										$erroresClientes[] = "Error al eliminar de $dbName: " . $connCliente->error;
									}
								}
							}
						} catch (Exception $e) {
							$erroresClientes[] = "Error en $dbName: " . $e->getMessage();
						} finally {
							$connCliente->close();
						}
					}
				}
			}
	
			// Confirmar transacción principal
			$conexionPrincipal->commit();
	
			$response = [
				'success' => true,
				'message' => 'Plan eliminado correctamente'
			];
	
			if (!empty($erroresClientes)) {
				$response['warnings'] = $erroresClientes;
			}
	
			return $response;
	
		} catch (Exception $e) {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->rollback();
			}
			
			return [
				'success' => false,
				'message' => 'Error en el servidor: ' . $e->getMessage()
			];
		} finally {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->autocommit(true);
				$conexionPrincipal->close();
			}
		}
	}
		
	public function getPlanConfiguracionMainModel(){
        $query = "SELECT pp.configuraciones 
		FROM planes pp
		INNER JOIN plan p ON p.planes_id = pp.planes_id";
        
        $sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
        
        if($sql->num_rows > 0){
            $row = $sql->fetch_assoc();
            return json_decode($row['configuraciones'], true);
        }
        
        return [];
    }

	public function guardar_o_actualizar_modulo_lista_blanca($nombre_config, $moduloNuevo) {
		$response = [
			'success' => false,
			'message' => ''
		];
	
		try {
			// Obtener conexión principal
			$conexionPrincipal = $this->connection();
			$conexionPrincipal->autocommit(false);
	
			// 1. Actualizar en la base principal
			$stmt = $conexionPrincipal->prepare("SELECT modulos FROM config_lista_blanca WHERE nombre_config = ?");
			$stmt->bind_param("s", $nombre_config);
			$stmt->execute();
			$result = $stmt->get_result();
	
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$listaModulos = json_decode($row['modulos'], true);
	
				if (!in_array($moduloNuevo, $listaModulos)) {
					$listaModulos[] = $moduloNuevo;
					$modulosJson = json_encode(array_values(array_unique($listaModulos)), JSON_UNESCAPED_UNICODE);
	
					$stmt = $conexionPrincipal->prepare("UPDATE config_lista_blanca SET modulos = ? WHERE nombre_config = ?");
					$stmt->bind_param("ss", $modulosJson, $nombre_config);
					$stmt->execute();
					
					if ($stmt->affected_rows <= 0) {
						throw new Exception("No se actualizó ningún registro en la base principal");
					}
				}
			} else {
				$modulosJson = json_encode([$moduloNuevo], JSON_UNESCAPED_UNICODE);
				$stmt = $conexionPrincipal->prepare("INSERT INTO config_lista_blanca (nombre_config, modulos) VALUES (?, ?)");
				$stmt->bind_param("ss", $nombre_config, $modulosJson);
				$stmt->execute();
				
				if ($stmt->affected_rows <= 0) {
					throw new Exception("No se insertó ningún registro en la base principal");
				}
			}
	
			// 2. Actualizar en todas las bases de datos de clientes
			$clientes = $this->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
			$erroresClientes = [];
	
			foreach ($clientes as $cliente) {
				$dbName = $cliente['db'];
				
				if ($this->databaseExists($dbName)) {
					$configCliente = [
						'host' => SERVER,
						'user' => USER,
						'pass' => PASS,
						'name' => $dbName
					];
					
					$connCliente = $this->connectToDatabase($configCliente);
					
					if ($connCliente !== false) {
						try {
							$connCliente->autocommit(false);
	
							// Verificar si la tabla existe
							$tableExists = $connCliente->query("SHOW TABLES LIKE 'config_lista_blanca'");
							
							if ($tableExists->num_rows > 0) {
								// Verificar si la configuración existe
								$stmtCliente = $connCliente->prepare("SELECT modulos FROM config_lista_blanca WHERE nombre_config = ?");
								$stmtCliente->bind_param("s", $nombre_config);
								$stmtCliente->execute();
								$resultCliente = $stmtCliente->get_result();
	
								if ($resultCliente->num_rows > 0) {
									$rowCliente = $resultCliente->fetch_assoc();
									$listaModulosCliente = json_decode($rowCliente['modulos'], true);
	
									if (!in_array($moduloNuevo, $listaModulosCliente)) {
										$listaModulosCliente[] = $moduloNuevo;
										$modulosJsonCliente = json_encode(array_values(array_unique($listaModulosCliente)), JSON_UNESCAPED_UNICODE);
	
										$stmtUpdate = $connCliente->prepare("UPDATE config_lista_blanca SET modulos = ? WHERE nombre_config = ?");
										$stmtUpdate->bind_param("ss", $modulosJsonCliente, $nombre_config);
										$stmtUpdate->execute();
										
										if ($stmtUpdate->affected_rows <= 0) {
											throw new Exception("No se actualizó ningún registro en $dbName");
										}
									}
								} else {
									$modulosJsonCliente = json_encode([$moduloNuevo], JSON_UNESCAPED_UNICODE);
									$stmtInsert = $connCliente->prepare("INSERT INTO config_lista_blanca (nombre_config, modulos) VALUES (?, ?)");
									$stmtInsert->bind_param("ss", $nombre_config, $modulosJsonCliente);
									$stmtInsert->execute();
									
									if ($stmtInsert->affected_rows <= 0) {
										throw new Exception("No se insertó ningún registro en $dbName");
									}
								}
							}
	
							$connCliente->commit();
						} catch (Exception $e) {
							$connCliente->rollback();
							$erroresClientes[] = "Error en $dbName: " . $e->getMessage();
						} finally {
							$connCliente->autocommit(true);
							$connCliente->close();
						}
					}
				}
			}
	
			// Confirmar transacción principal
			$conexionPrincipal->commit();
	
			$response = [
				'success' => true,
				'message' => 'Módulo actualizado en lista blanca correctamente'
			];
	
			if (!empty($erroresClientes)) {
				$response['warnings'] = $erroresClientes;
			}
	
			return $response;
	
		} catch(Exception $e) {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->rollback();
			}
			
			return [
				'success' => false,
				'message' => 'Error: ' . $e->getMessage()
			];
		} finally {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->autocommit(true);
				$conexionPrincipal->close();
			}
		}
	}
	
	public function eliminar_modulo_lista_blanca($nombre_config, $moduloEliminar) {
		$response = [
			'success' => false,
			'message' => ''
		];
	
		try {
			// Obtener conexión principal
			$conexionPrincipal = $this->connection();
			$conexionPrincipal->autocommit(false);
	
			// 1. Eliminar de la base principal
			$stmt = $conexionPrincipal->prepare("SELECT modulos FROM config_lista_blanca WHERE nombre_config = ?");
			$stmt->bind_param("s", $nombre_config);
			$stmt->execute();
			$result = $stmt->get_result();
	
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$listaModulos = json_decode($row['modulos'], true);
				
				$nuevaLista = array_diff($listaModulos, [$moduloEliminar]);
				
				if (count($nuevaLista) != count($listaModulos)) {
					$modulosJson = json_encode(array_values($nuevaLista), JSON_UNESCAPED_UNICODE);
					$stmt = $conexionPrincipal->prepare("UPDATE config_lista_blanca SET modulos = ? WHERE nombre_config = ?");
					$stmt->bind_param("ss", $modulosJson, $nombre_config);
					$stmt->execute();
					
					if ($stmt->affected_rows <= 0) {
						throw new Exception("No se actualizó ningún registro en la base principal");
					}
				}
			} else {
				$conexionPrincipal->rollback();
				return [
					'success' => false,
					'message' => 'La configuración no existe en la base principal'
				];
			}
	
			// 2. Actualizar en todas las bases de datos de clientes
			$clientes = $this->ejecutar_consulta("SELECT db FROM server_customers WHERE estado = 1 AND db != ''");
			$erroresClientes = [];
	
			foreach ($clientes as $cliente) {
				$dbName = $cliente['db'];
				
				if ($this->databaseExists($dbName)) {
					$configCliente = [
						'host' => SERVER,
						'user' => USER,
						'pass' => PASS,
						'name' => $dbName
					];
					
					$connCliente = $this->connectToDatabase($configCliente);
					
					if ($connCliente !== false) {
						try {
							$connCliente->autocommit(false);
	
							// Verificar si la tabla existe
							$tableExists = $connCliente->query("SHOW TABLES LIKE 'config_lista_blanca'");
							
							if ($tableExists->num_rows > 0) {
								// Verificar si la configuración existe
								$stmtCliente = $connCliente->prepare("SELECT modulos FROM config_lista_blanca WHERE nombre_config = ?");
								$stmtCliente->bind_param("s", $nombre_config);
								$stmtCliente->execute();
								$resultCliente = $stmtCliente->get_result();
	
								if ($resultCliente->num_rows > 0) {
									$rowCliente = $resultCliente->fetch_assoc();
									$listaModulosCliente = json_decode($rowCliente['modulos'], true);
									
									$nuevaListaCliente = array_diff($listaModulosCliente, [$moduloEliminar]);
									
									if (count($nuevaListaCliente) != count($listaModulosCliente)) {
										$modulosJsonCliente = json_encode(array_values($nuevaListaCliente), JSON_UNESCAPED_UNICODE);
										$stmtUpdate = $connCliente->prepare("UPDATE config_lista_blanca SET modulos = ? WHERE nombre_config = ?");
										$stmtUpdate->bind_param("ss", $modulosJsonCliente, $nombre_config);
										$stmtUpdate->execute();
										
										if ($stmtUpdate->affected_rows <= 0) {
											throw new Exception("No se actualizó ningún registro en $dbName");
										}
									}
								}
							}
	
							$connCliente->commit();
						} catch (Exception $e) {
							$connCliente->rollback();
							$erroresClientes[] = "Error en $dbName: " . $e->getMessage();
						} finally {
							$connCliente->autocommit(true);
							$connCliente->close();
						}
					}
				}
			}
	
			// Confirmar transacción principal
			$conexionPrincipal->commit();
	
			$response = [
				'success' => true,
				'message' => 'Módulo eliminado de lista blanca correctamente'
			];
	
			if (!empty($erroresClientes)) {
				$response['warnings'] = $erroresClientes;
			}
	
			return $response;
	
		} catch(Exception $e) {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->rollback();
			}
			
			return [
				'success' => false,
				'message' => 'Error: ' . $e->getMessage()
			];
		} finally {
			if (isset($conexionPrincipal)) {
				$conexionPrincipal->autocommit(true);
				$conexionPrincipal->close();
			}
		}
	}
	
    // Ejecutar consulta simple (SELECT)
	public static function ejecutar_consulta($query) {
		// Abrir conexión a la base de datos
		$conexion = (new self())->connection();
		
		// Ejecutar la consulta
		$resultado = $conexion->query($query);
		
		// Verificar si la consulta fue exitosa
		if (!$resultado) {
			$error = $conexion->error;
			$conexion->close();
			throw new Exception('Error en la consulta: ' . $error);
		}
		
		// Para consultas SELECT, obtener y almacenar los resultados antes de cerrar
		if (stripos(trim($query), 'SELECT') === 0) {
			$data = [];
			while ($row = $resultado->fetch_assoc()) {
				$data[] = $row;
			}
			$resultado->free();
			$conexion->close();
			return $data;
		}
		
		// Para otras consultas (INSERT, UPDATE, DELETE)
		$conexion->close();
		return $resultado;
	}

	// Función para generar código único basado en fecha y cliente_id
	public function generarCodigoUnico($clientes_id) {
		$fecha = date('Ymd'); // Fecha en formato AAAAMMDD
		$hash = substr(md5($clientes_id . microtime()), 0, 4); // 4 caracteres únicos
		$codigo = substr($fecha . $hash, 0, 8); // Combinación de 8 dígitos
		
		// Aseguramos que sea numérico
		return (int)preg_replace('/[^0-9]/', '', $codigo);
	}
	
    // Insertar datos
    public static function insertar_datos($tabla, $datos) {
        $campos = implode(", ", array_keys($datos));
        $valores = "'" . implode("', '", array_values($datos)) . "'";
        $query = "INSERT INTO $tabla ($campos) VALUES ($valores)";

        $conexion = (new mainModel())->connection();
        $resultado = $conexion->query($query);

        if (!$resultado) {
            throw new Exception('Error al insertar datos: ' . $conexion->error);
        }

        $conexion->close();
        return $resultado;
    }

    // Actualizar datos
    public static function actualizar_datos($tabla, $datos, $condicion) {
        $updates = [];
        foreach ($datos as $campo => $valor) {
            $updates[] = "$campo = '$valor'";
        }
        $updates = implode(", ", $updates);

        $query = "UPDATE $tabla SET $updates WHERE $condicion";

        $conexion = (new mainModel())->connection();
        $resultado = $conexion->query($query);

        if (!$resultado) {
            throw new Exception('Error al actualizar datos: ' . $conexion->error);
        }

        $conexion->close();
        return $resultado;
    }

    // Eliminar datos
    public static function eliminar_datos($tabla, $condicion) {
        $query = "DELETE FROM $tabla WHERE $condicion";

        $conexion = (new mainModel())->connection();
        $resultado = $conexion->query($query);

        if (!$resultado) {
            throw new Exception('Error al eliminar datos: ' . $conexion->error);
        }

        $conexion->close();
        return $resultado;
    }
		
	protected function guardar_bitacora($datos) {
		try {
			$bitacora_id = self::correlativo('bitacora_id', 'bitacora');
			$fecha_registro = date('Y-m-d H:i:s');
			
			$query = "INSERT INTO bitacora (bitacora_id, bitacoraCodigo, bitacoraFecha, bitacoraHoraInicio, bitacoraHoraFinal, bitacoraTipo, bitacoraYear, colaboradores_id, fecha_registro) 
					  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			
			$params = [
				$bitacora_id,
				$datos['bitacoraCodigo'],
				$datos['bitacoraFecha'],
				$datos['bitacoraHoraInicio'],
				$datos['bitacoraHoraFinal'],
				$datos['bitacoraTipo'],
				$datos['bitacoraYear'],
				$datos['user_id'],
				$fecha_registro
			];
			
			$result = $this->ejecutar_consulta_simple_preparada($query, "issssisis", $params);
			
			return $result;
		} catch (Exception $e) {
			error_log("Error en guardar_bitacora: " . $e->getMessage());
			return false;
		}
	}

	public function actualizar_hora_salida_bitacora($codigo_bitacora, $hora_salida) {
		try {
			$query = "UPDATE bitacora SET bitacoraHoraFinal = ? WHERE bitacoraCodigo = ?";
			$params = [$hora_salida, $codigo_bitacora];
			
			$result = $this->ejecutar_consulta_simple_preparada($query, "ss", $params);
			
			return $result;
		} catch (Exception $e) {
			error_log("Error en actualizar_hora_salida_bitacora: " . $e->getMessage());
			return false;
		}
	}

	public function guardarHistorial($datos) {
		try {
			$historial_id = self::correlativo('historial_id', 'historial');
			$fecha_registro = date('Y-m-d H:i:s');
			
			$query = "INSERT INTO historial (historial_id, modulo, colaboradores_id, status, observacion, fecha_registro) 
					 VALUES (?, ?, ?, ?, ?, ?)";
			
			$params = [
				$historial_id,
				$datos['modulo'],
				$datos['colaboradores_id'],
				$datos['status'],
				$datos['observacion'],
				$fecha_registro
			];
			
			$result = $this->ejecutar_consulta_simple_preparada($query, "isssss", $params);
			
			return $result;
		} catch (Exception $e) {
			error_log("Error en guardarHistorial: " . $e->getMessage());
			return false;
		}
	}

	public function validSalidaAsistenciaColaborador($asistencia_id)
	{
		$delete = "SELECT horaf FROM asistencia WHERE asistencia_id = '$asistencia_id'";

		$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

		return $sql;
	}

	/**
	 * Genera nombres de base de datos a partir del nombre de una compañía
	 * 
	 * @param string $companyName Nombre de la compañía
	 * @return array Devuelve un array con ambos formatos requeridos
	 */
	function generateDatabaseName($companyName, $sistema) {
		// Normalizar el nombre: eliminar acentos, caracteres especiales y convertir a minúsculas
		$cleanName = preg_replace('/[^a-z0-9]/', '', 
					strtolower(
						iconv('UTF-8', 'ASCII//TRANSLIT', $companyName)
					));
		
		// Obtener una versión corta del nombre limpio
		$uniqueId = substr($cleanName, 0, DB_MAX_LENGTH);
		
		// Si el nombre queda vacío después de la limpieza, usar un valor aleatorio
		if(empty($uniqueId)) {
			$uniqueId = 'cmp' . rand(100, 999);
		}
		
		// Asegurar que el prefijo usado sea el de cPanel (CPANEL_USERNAME)
		$cpanelPrefix = defined('CPANEL_USERNAME') ? CPANEL_USERNAME : DB_PREFIX;
		
		// Devolver ambos formatos requeridos
		return [
			'prefixed' => $cpanelPrefix . '_' . $uniqueId . '_' . $sistema ,  // ej: "esmultiservicios_banbuclic_izzy"
			'unprefixed' => $uniqueId . '_' . $sistema                       // ej: "banbuclic_izzy"
		];
	}

	public function updateSalidaAsistenciaColaborador($asistencia_id)
	{
		$update = "UPDATE asistencia
							SET horaf = ''
						WHERE asistencia_id = '$asistencia_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function deleteAsistenciaColaborador($asistencia_id)
	{
		$delete = "DELETE FROM asistencia WHERE asistencia_id = '$asistencia_id'";

		$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

		return $sql;
	}

	public function deleteDestinatarios($notificaciones_id)
	{
		$delete = "DELETE FROM notificaciones WHERE notificaciones_id = '$notificaciones_id'";

		$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

		return $sql;
	}

	public function eliminar_bitacora($user_id)
	{
		$delete = "DELETE FROM bitacora WHERE user_id = '$user_id'";
		$result = self::connection()->query($delete);

		return $result;
	}

	public function getRealIP()
	{
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
			return $_SERVER['HTTP_X_FORWARDED'];
		} elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			return $_SERVER['HTTP_FORWARDED_FOR'];
		} elseif (isset($_SERVER['HTTP_FORWARDED'])) {
			return $_SERVER['HTTP_FORWARDED'];
		} else {
			return $_SERVER['REMOTE_ADDR'];
		}
	}

	function eliminar_acentos($cadena)
	{
		// Reemplazamos la A y a
		$cadena = str_replace(
			array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
			array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
			$cadena
		);

		// Reemplazamos la E y e
		$cadena = str_replace(
			array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
			array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
			$cadena
		);

		// Reemplazamos la I y i
		$cadena = str_replace(
			array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
			array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
			$cadena
		);

		// Reemplazamos la O y o
		$cadena = str_replace(
			array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
			array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
			$cadena
		);

		// Reemplazamos la U y u
		$cadena = str_replace(
			array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
			array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
			$cadena
		);

		// Reemplazamos la N, n, C y c
		$cadena = str_replace(
			array('Ñ', 'ñ', 'Ç', 'ç'),
			array('N', 'n', 'C', 'c'),
			$cadena
		);

		return $cadena;
	}

	public function guardar_historial_accesos($comentario_)
	{
		$nombre_host = self::getRealIP();
		$fecha = date('Y-m-d H:i:s');
		$comentario = $comentario_;
		$usuario = $_SESSION['colaborador_id_sd'] ?? 0;

		$historial_acceso_id = self::correlativo('historial_acceso_id ', 'historial_acceso');
		$insert = "INSERT INTO historial_acceso VALUES('$historial_acceso_id','$fecha','$usuario','$nombre_host','$comentario')";

		$result = self::connection()->query($insert);

		return $result;
	}

	public function anular_cotizacion($cotizacion_id)
	{
		$update = "UPDATE cotizacion
			SET
				estado = 2
			WHERE cotizacion_id = '$cotizacion_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function anular_factura($facturas_id)
	{
		$update = "UPDATE facturas
			SET
				estado = 4
			WHERE facturas_id = '$facturas_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function delete_bill_draft($facturas_id)
	{
		$delete = "DELETE FROM facturas WHERE facturas_id = '$facturas_id' AND estado = 1";

		$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);

		return $sql;
	}

	public function anular_compra($compras_id)
	{
		$update = "UPDATE compras
			SET
				estado = 4
			WHERE compras_id = '$compras_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function anular_vale($vale_id)
	{
		$update = "UPDATE vale 
				\t   SET estado = 2
				\t   WHERE vale_id = '$vale_id' 
				\t   AND estado = 0";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function anular_pago_factura($facturas_id)
	{
		$update = "UPDATE pagos
			SET
				estado = 2
			WHERE facturas_id = '$facturas_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function anular_pago_compras($compras_id)
	{
		$update = "UPDATE pagoscompras
			SET
				estado = 2
			WHERE compras_id = '$compras_id'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function valid_pago_factura($facturas_id)
	{
		$query = "SELECT pagos_id
				FROM pagos
				WHERE facturas_id = '$facturas_id'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function consultar_tabla($tabla, $campos, $condicion)
	{
		// Convierte el array de campos en una cadena separada por comas
		$campos_str = implode(', ', $campos);

		// Construye la consulta SQL dinámicamente
		$query = "SELECT $campos_str FROM $tabla WHERE $condicion";

		// Ejecuta la consulta
		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		// Inicializa un array para almacenar los resultados
		$resultados = [];

		// Recorre los resultados y almacena en el array
		while ($fila = $sql->fetch_assoc()) {
			$resultados[] = $fila;
		}

		// Retorna el array de resultados
		return $resultados;
	}

	public function abonos_cxc_cliente($facturas_id)
	{
		$query = "SELECT
			pagos.facturas_id,
			pagos.fecha,
			pagos.importe as abono,
			pagos_detalles.descripcion1,
			facturas.importe,
			clientes.nombre as cliente,
            tipo_pago.nombre as tipo_pago,
			sf.prefijo AS 'prefijo',
			sf.siguiente AS 'numero',
			sf.relleno AS 'relleno',
			sf.prefijo AS 'prefijo',
			colaboradores.nombre AS 'usuario'
			FROM pagos
			LEFT JOIN pagos_detalles ON pagos.pagos_id = pagos_detalles.pagos_id
			INNER JOIN facturas ON facturas.facturas_id = pagos.facturas_id
			INNER JOIN clientes ON facturas.clientes_id = clientes.clientes_id
            INNER JOIN tipo_pago ON pagos_detalles.tipo_pago_id = tipo_pago.tipo_pago_id
			INNER JOIN secuencia_facturacion AS sf ON facturas.secuencia_facturacion_id = sf.secuencia_facturacion_id
			INNER JOIN colaboradores ON pagos.usuario = colaboradores.colaboradores_id
			WHERE pagos.facturas_id = '$facturas_id'";
		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function abonos_cxp_proveedor($facturas_id)
	{
		$query = "SELECT
			pagoscompras.importe AS total,
			pagoscompras.pagoscompras_id,
			pagoscompras.compras_id,
			pagoscompras.tipo_pago,
			pagoscompras.fecha,
			pagoscompras.efectivo,
			pagoscompras.cambio,
			pagoscompras.tarjeta,
			pagoscompras.usuario,
			pagoscompras.estado,
			pagoscompras.empresa_id,
			pagoscompras.fecha_registro,
			proveedores.nombre,
			compras.importe,
			tipo_pago.nombre as tipoPago,
			pagoscompras_detalles.descripcion1,
			compras.number AS factura,
			colaboradores.nombre AS 'usuario'
			FROM compras
			INNER JOIN pagoscompras ON compras.compras_id = pagoscompras.compras_id
			INNER JOIN pagoscompras_detalles ON pagoscompras_detalles.pagoscompras_id = pagoscompras.pagoscompras_id
			INNER JOIN tipo_pago ON pagoscompras_detalles.tipo_pago_id = tipo_pago.tipo_pago_id
			INNER JOIN proveedores ON proveedores.proveedores_id = compras.proveedores_id
			INNER JOIN colaboradores ON pagoscompras.usuario = colaboradores.colaboradores_id
			WHERE
				compras.compras_id ='$facturas_id'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function valid_pago_compras($compras_id)
	{
		$query = "SELECT pagoscompras_id
				FROM pagoscompras
				WHERE compras_id = '$compras_id'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	protected function generar_password_complejo()
	{
		$largo = 12;
		$cadena_base = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$password = '';

		$limite = strlen($cadena_base) - 1;

		for ($i = 0; $i < $largo; $i++)
			$password .= $cadena_base[rand(0, $limite)];

		return $password;
	}

	/* Funcion que permite encriptar string */

	public function encryption($string)
	{
		$ouput = FALSE;
		$key = hash('sha256', SECRET_KEY);
		$iv = substr(hash('sha256', SECRET_IV), 0, 16);
		$output = openssl_encrypt($string, METHOD, $key, 0, $iv);
		$output = base64_encode($output);

		return $output;
	}

	/* Funcion que permite desencriptar string */
	public function decryption($string)
	{
		$key = hash('sha256', SECRET_KEY);
		$iv = substr(hash('sha256', SECRET_IV), 0, 16);
		$output = openssl_decrypt(base64_decode($string), METHOD, $key, 0, $iv);

		return $output;
	}

	/* Funcion que permite generar codigos aleatorios */

	public function getRandom($word, $length, $number)
	{
		for ($i = 1; $i < $length; $i++) {
			$number = rand(0, 9);
			$word .= $number;
		}

		return $word . $number;
	}

	/* Funcion que permite limpiar valores de los string (Inyección SQL) */

	public function cleanString($string)
	{
		// Limpia espacios al inicio y al final
		$string = trim($string);

		// Elimina barras de un string con comillas escapadas
		$string = stripslashes($string);

		// Escapa caracteres especiales de HTML
		$string = htmlspecialchars($string);

		// Eliminar etiquetas HTML y JavaScript
		$string = strip_tags($string);

		// Elimina caracteres peligrosos específicos de SQL
		$string = str_ireplace(array(';', '--', '^', ']', '[', '{', '}', '=='), '', $string);

		return $string;
	}

	protected function cleanStringStrtolower($string)
	{
		// Limpia espacios al inicio y al final, y convierte a minúsculas
		$string = strtolower(trim($string));

		// Elimina barras de un string con comillas escapadas
		$string = stripslashes($string);

		// Escapa caracteres especiales de HTML
		$string = htmlspecialchars($string);

		// Eliminar etiquetas HTML y JavaScript
		$string = strip_tags($string);

		// Elimina caracteres peligrosos específicos de SQL
		$string = str_ireplace(array(';', '--', '^', ']', '[', '{', '}', '=='), '', $string);

		return $string;
	}

	protected function cleanStringStrtoupper($string)
	{
		// Limpia espacios al inicio y al final, y convierte a mayúsculas
		$string = strtoupper(trim($string));

		// Escapa caracteres especiales de HTML
		$string = htmlspecialchars($string);

		// Elimina barras de un string con comillas escapadas
		$string = stripslashes($string);

		// Eliminar etiquetas HTML y JavaScript
		$string = strip_tags($string);

		// Elimina caracteres peligrosos específicos de SQL
		$string = str_ireplace(array(';', '--', '^', '\]', '\[', '{', '}', '=='), '', $string);

		return $string;
	}

	public function cleanStringConverterCase($string)
	{
		// Limpia espacios al inicio y al final
		$string = trim($string);

		// Quita las barras de un string con comillas escapadas
		$string = stripslashes($string);

		// Escapa caracteres especiales de HTML
		$string = htmlspecialchars($string);

		// Eliminar etiquetas HTML y JavaScript
		$string = strip_tags($string);

		// Eliminar caracteres peligrosos específicos de SQL
		$string = str_ireplace(array(';', '--', '^', ']', '[', '{', '}', '==', "'"), '', $string);

		return $string;
	}

	public function sweetAlert($datos)
	{
		if ($datos['alert'] == 'simple') {
			$alerta = "
                    <script>
                        swal({
                            title: '" . $datos['title'] . "',
                            text: '" . $datos['text'] . "',
                            icon: '" . $datos['type'] . "',
                            confirmButtonClass: '" . $datos['btn-class'] . "',
							allowEscapeKey: false,
							allowOutsideClick: false
                        });
                    </script>
                ";
		} elseif ($datos['alert'] == 'reload') {
			$alerta = "
                    <script>
					swal({
						title: '" . $datos['title'] . "',
						text: '" . $datos['text'] . "',
						icon: '" . $datos['type'] . "',
						buttons: true,
						confirmButtonClass: '" . $datos['btn-class'] . "',
						confirmButtonText: '" . $datos['btn-text'] . "',
						timer: 3000,
						allowOutsideClick: false
					}).then((willConfirm) => {
						if (willConfirm) {
							location.reload();  // Recarga la página cuando se confirma
						}
					});
                    </script>
                ";
		} elseif ($datos['alert'] == 'cerrar') {
			$alerta = "
                    <script>
					swal({
						title: '" . $datos['title'] . "',
						text: '" . $datos['text'] . "',
						icon: '" . $datos['type'] . "',
						buttons: true,
						dangerMode: true,  // Si quieres resaltar el botón de peligro, puedes usar esto
						timer: 3000,
						allowOutsideClick: false
					}).then((willDelete) => {
						if (willDelete) {
							redireccionar(); // Redirecciona si el usuario confirma
						} else {
							redireccionar(); // Redirecciona si el usuario cancela
						}
					});
                    </script>
                ";
			self::cerrar_sesion();
		} elseif ($datos['alert'] == 'clear') {
			$alerta = "
			<script>
				swal({
					title: '" . $datos['title'] . "',
					text: '" . $datos['text'] . "',
					icon: '" . $datos['type'] . "',
					showCancelButton: false,
					timer: 3000,
					confirmButtonClass: '" . $datos['btn-class'] . "',
					confirmButtonText: '" . $datos['btn-text'] . "',
					closeOnConfirm: false,
					allowEscapeKey: false,
					allowOutsideClick: false
				});
		
				$('#" . $datos['form'] . "')[0].reset();
				$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
				" . $datos['funcion'] . ';
			</script>';		
		} elseif ($datos['alert'] == 'clear_pay') {
			$alerta = "
                    <script>
						swal({
							title: '" . $datos['title'] . "',
							text: '" . $datos['text'] . "',
							icon: '" . $datos['type'] . "',
							showCancelButton: false,
							confirmButtonClass: '" . $datos['btn-class'] . "',
							confirmButtonText: '" . $datos['btn-text'] . "',
							closeOnConfirm: false,
							allowEscapeKey: false,
							allowOutsideClick: false
						});
						location.reload();
						\$('#" . $datos['form'] . "')[0].reset();
						\$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
						" . $datos['funcion'] . ';
						$(\'#' . $datos['modal'] . "').modal('hide');
                    </script>
                ";
		} elseif ($datos['alert'] == 'save_simple') {
			$alerta = "
                    <script>
						swal({
							title: '" . $datos['title'] . "',
							text: '" . $datos['text'] . "',
							icon: '" . $datos['type'] . "',
							showCancelButton: false,
							timer: 3000,
							confirmButtonClass: '" . $datos['btn-class'] . "',
							confirmButtonText: '" . $datos['btn-text'] . "',
							closeOnConfirm: false,
							allowEscapeKey: false,
							allowOutsideClick: false
						});

						\$('#" . $datos['form'] . "')[0].reset();
						\$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
						" . $datos['funcion'] . ';
						$(\'#' . $datos['modal'] . "').modal('hide');
                    </script>
                ";
		} elseif ($datos['alert'] == 'save') {
			$alerta = "
                    <script>
						swal({
							title: '" . $datos['title'] . "',
							text: '" . $datos['text'] . "',
							icon: '" . $datos['type'] . "',
							showCancelButton: false,
							timer: 3000,
							confirmButtonClass: '" . $datos['btn-class'] . "',
							confirmButtonText: '" . $datos['btn-text'] . "',
							closeOnConfirm: false,
							allowEscapeKey: false,
							allowOutsideClick: false
						});

				\t    \$('#" . $datos['form'] . "')[0].reset();
				\t    \$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
				\t    " . $datos['funcion'] . ';
                    </script>

                ';
		} elseif ($datos['alert'] == 'delete') {
			$alerta = "
                    <script>
						swal({
							title: '" . $datos['title'] . "',
							text: '" . $datos['text'] . "',
							icon: '" . $datos['type'] . "',
							showCancelButton: false,
							timer: 3000,
							confirmButtonClass: '" . $datos['btn-class'] . "',
							confirmButtonText: '" . $datos['btn-text'] . "',
							closeOnConfirm: false,
							allowEscapeKey: false,
							allowOutsideClick: false
						});

				\t    \$('#" . $datos['form'] . "')[0].reset();
				\t    \$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
				\t    " . $datos['funcion'] . ";
				\t    \$('#" . $datos['modal'] . "').modal('hide');
                    </script>
                ";
		} elseif ($datos['alert'] == 'edit') {
			$alerta = "
                    <script>
						swal({
							title: '" . $datos['title'] . "',
							text: '" . $datos['text'] . "',
							icon: '" . $datos['type'] . "',
							showCancelButton: false,
							timer: 3000,
							confirmButtonClass: '" . $datos['btn-class'] . "',
							confirmButtonText: '" . $datos['btn-text'] . "',
							closeOnConfirm: false,
							allowEscapeKey: false,
							allowOutsideClick: false
						});

				\t    \$('#" . $datos['form'] . ' #' . $datos['id'] . "').val('" . $datos['valor'] . "');
				\t    " . $datos['funcion'] . ";
				\t    \$('#" . $datos['modal'] . "').modal('hide');
                    </script>
                ";
		}

		return $alerta;
	}

	public function showNotification($alert) {
		$type = $alert['type'] ?? 'error';
		$title = $alert['title'] ?? 'Notificación';
		$message = $alert['text'] ?? '';
		$status = ($type === 'success') ? 'success' : 'error';
		
		// Permitir HTML en el mensaje si se especifica
		$allowHtml = $alert['allow_html'] ?? false;
		
		// Inicializar array de acciones
		$actions = [];
		
		// Notificación principal (siempre primera)
		$notificationScript = "if (typeof showNotify === 'function') { 
			showNotify('{$status}', '" . addslashes($title) . "', " . 
			($allowHtml ? "`{$message}`" : "'" . addslashes($message) . "'") . ", " .
			($allowHtml ? 'true' : 'false') . "); 
		}";
		$actions[] = $notificationScript;
		
		// Resetear formulario si se especifica
		if (!empty($alert['form'])) {
			$actions[] = "$('#{$alert['form']}')[0].reset();";
			
			// Resetear también selects con selectpicker si existen
			$actions[] = "$('#{$alert['form']}').find('.selectpicker').selectpicker('refresh');";
		}
		
		// Ejecutar funciones adicionales si se especifican
		if (!empty($alert['funcion'])) {
			$functions = array_filter(explode(';', $alert['funcion']));
			foreach ($functions as $func) {
				$func = trim($func);
				if (!empty($func)) {
					$actions[] = "try { 
						if (typeof " . explode('(', $func)[0] . " === 'function') { 
							{$func}; 
						}
					} catch (e) {
						console.error('Error al ejecutar función: {$func}', e); 
					}";
				}
			}
		}
		
		// Cerrar modales si se solicita
		if (!empty($alert['closeAllModals'])) {
			$actions[] = "$('.modal').modal('hide');";
		}
		
		// Redireccionar si se especifica
		if (!empty($alert['redirect'])) {
			$redirectUrl = addslashes($alert['redirect']);
			$actions[] = "setTimeout(function() {
				window.location.href = '{$redirectUrl}';
			}, 1500);";
		}
		
		// Generar UN solo script
		return "<script>
			(function() {
				" . implode("\n", $actions) . "
			})();
		</script>";
	}

	function cerrar_sesion()
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$token = $_SESSION['token_sd'];
		$hora = date('H:m:s');
		$usuario = $_SESSION['user_sd'];
		$token_s = $_SESSION['token_sd'];
		$codigo = $_SESSION['codigo_bitacora_sd'];
		self::guardar_historial_accesos('Cierre de Sesion');
		session_unset();  // VACIAR LA SESION
		session_destroy();  // DESTRUIR LA SESION

		// Redirigir con JavaScript
		echo "<script>window.location.href = '" . SERVERURL . "login/';</script>";
	}

	public function getProductoBarCodeBill($barCode)
	{
		$query = "SELECT productos_id, nombre, precio_venta, isv_venta, precio_mayoreo, cantidad_mayoreo

				FROM productos

				WHERE estado = 1 AND barCode = '$barCode'

				ORDER BY descripcion";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCategoriaProductos($estado)
	{
		$query = "SELECT categoria_id, nombre, estado

				FROM categoria

				WHERE estado = '$estado'";

		$result = self::connection()->query($query);

		return $result;
	}

	/* INICIO CONVERTIR COTIZACION A FACTURA */

	public function correlativoEntidades($campo_id, $tabla)
	{
		$query = 'SELECT MAX(' . $campo_id . ') AS max, COUNT(' . $campo_id . ') AS count FROM ' . $tabla;

		$result = self::connection()->query($query);

		$correlativo2 = $result->fetch_assoc();

		$numero = $correlativo2['max'];

		$cantidad = $correlativo2['count'];

		if ($cantidad == 0)
			$numero = 1;
		else
			$numero = $numero + 1;

		return $numero;
	}

	public function actualizarCotizacionFactura($cotizacion_id)
	{
		$query = "UPDATE cotizacion
				SET
					estado = '3'
				WHERE cotizacion_id = '$cotizacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCotizacionFactura($cotizacion_id)
	{
		$query = "SELECT clientes_id, colaboradores_id, importe, notas
				FROM cotizacion
				WHERE cotizacion_id = '$cotizacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCotizacionDetallesFactura($cotizacion_id)
	{
		$query = "SELECT productos_id, cantidad, precio, isv_valor, descuento
				FROM cotizacion_detalles
				WHERE cotizacion_id = '$cotizacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	

	function consultarDBClientes()
	{
		$query = "SELECT db
				FROM server_customers
				WHERE db LIKE 'clinicarehn_clientes_%'
					AND db NOT LIKE 'clinicarehn_clientes_monisys'
				UNION
				SELECT 'clinicarehn_clientes_clinicare' AS db
				ORDER BY db;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function secuencia_facturacion($empresa_id)
	{
		$query = "SELECT secuencia_facturacion_id, prefijo, siguiente AS 'numero', rango_final, fecha_limite, incremento, relleno
		\t   FROM secuencia_facturacion
		\t   WHERE activo = '1' AND empresa_id = '$empresa_id'";
		$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $result;
	}

	public function agregar_facturas($datos)
	{
		$insert = "INSERT INTO facturas (
			`facturas_id`, 
			`clientes_id`, 
			`secuencia_facturacion_id`, 
			`apertura_id`, 
			`number`, 
			`tipo_factura`, 
			`colaboradores_id`, 
			`importe`, 
			`notas`, 
			`fecha`, 
			`estado`, 
			`usuario`, 
			`empresa_id`, 
			`fecha_registro`, 
			`fecha_dolar`
		) VALUES (
			'" . $datos['facturas_id'] . "', 
			'" . $datos['clientes_id'] . "', 
			'" . $datos['secuencia_facturacion_id'] . "', 
			'" . $datos['apertura_id'] . "', 
			'" . $datos['numero'] . "', 
			'" . $datos['tipo_factura'] . "', 
			'" . $datos['colaboradores_id'] . "', 
			'" . $datos['importe'] . "', 
			'" . $datos['notas'] . "', 
			'" . $datos['fecha'] . "', 
			'" . $datos['estado'] . "', 
			'" . $datos['usuario'] . "', 
			'" . $datos['empresa_id'] . "', 
			'" . $datos['fecha_registro'] . "', 
			'" . $datos['fecha_dolar'] . "'
		)";
	
		$result = mainModel::connection()->query($insert) 
			or die(mainModel::connection()->error);
	
		return $result;
	}		

	public function agregar_detalle_facturas($datos)
	{
		$facturas_detalle_id = mainModel::correlativo('facturas_detalle_id', 'facturas_detalles');
		
		$insert = "INSERT INTO facturas_detalles (
			`facturas_detalle_id`, 
			`facturas_id`, 
			`productos_id`, 
			`cantidad`, 
			`precio`, 
			`isv_valor`, 
			`descuento`, 
			`medida`
		) VALUES (
			'$facturas_detalle_id',
			'" . $datos['facturas_id'] . "',
			'" . $datos['productos_id'] . "',
			'" . $datos['cantidad'] . "',
			'" . $datos['precio'] . "',
			'" . $datos['isv_valor'] . "',
			'" . $datos['descuento'] . "',
			'" . $datos['medida'] . "'
		)";
		
		$result = mainModel::connection()->query($insert) 
			or die(mainModel::connection()->error);
	
		return $result;
	}	

	public function getAperturaID($datos)
	{
		$query = "SELECT apertura_id
				FROM apertura
				WHERE colaboradores_id = '" . $datos['colaboradores_id'] . "' AND fecha = '" . $datos['fecha'] . "' AND estado = '" . $datos['estado'] . "'";
		$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $result;
	}

	/* FIN CONVERTIR COTIZACION A FACTURA */
	public function getEmpresa($datos)
	{
		$privilegio = $datos['privilegio_colaborador'];
		$stado = $datos['estado'];

		if ($privilegio === 'Super Administrador' ||
				$privilegio === 'Administrador' ||
				$privilegio === 'Emprendedor' ||
				$privilegio === 'Basico' ||
				$privilegio === 'Regular' ||
				$privilegio === 'Estandar' ||
				$privilegio === 'Premium') {
			$where = "WHERE estado = '$stado'";
		} else {
			// $where = "WHERE estado = 1 AND empresa_id = '".$datos['empresa_id']."'";
			$where = "WHERE estado = '$stado'";
		}

		$query = "SELECT *
				FROM empresa
				{$where}
				ORDER BY nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpresaSelect($datos)
	{
		if ($datos['privilegio_colaborador'] === 'Super Administrador' && $datos['privilegio_colaborador'] === 'Administrador') {
			$where = 'WHERE estado = 1';
		} else {
			$where = "WHERE estado = 1 AND empresa_id = '" . $datos['empresa_id'] . "'";
		}

		$query = 'SELECT *
				FROM empresa
				WHERE estado = 1
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpresaConsulta($empresa_id)
	{
		$query = "SELECT *
				FROM empresa
				WHERE estado = 1 AND empresa_id = $empresa_id
				ORDER BY nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDocumento()
	{
		$query = 'SELECT *
				FROM documento
				WHERE estado = 1
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuenta()
	{
		$query = 'SELECT *
				FROM cuentas
				WHERE estado = 1
				ORDER BY nombre';
		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoCuenta()
	{
		$query = 'SELECT *
				FROM tipo_cuenta
				WHERE estado = 1
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProveedoresConsulta()
	{
		$query = 'SELECT *
				FROM proveedores
				WHERE estado = 1
				ORDER BY nombre';
		$result = self::connection()->query($query);

		return $result;
	}

	public function getClientesConsulta()
	{
		$query = 'SELECT *
				FROM clientes
				WHERE estado = 1
				ORDER BY nombre';
		$result = self::connection()->query($query);

		return $result;
	}

	public function ActualizarEstadoAsistencia($colaboradores_id)
	{
		$update = "UPDATE asistencia
				SET
					estado = 1
				WHERE colaboradores_id = '" . $colaboradores_id . "'";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function actualizarVales($datos)
	{
		$update = "UPDATE vale
				SET
					nomina_id = {$datos['nomina_id']},
					estado = {$datos['estado']}
				WHERE colaboradores_id = {$datos['colaboradores_id']} AND estado = 0";

		$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);

		return $sql;
	}

	public function GetColaboradoresNomina($nomina_id)
	{
		$query = "SELECT colaboradores_id
				FROM nomina_detalles
				WHERE nomina_id = '$nomina_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getColaboradoresConsulta()
	{
		$where = '';
		$valores = "'Reseller', 'Clientes'";

		if ($GLOBALS['db'] === DB_MAIN) {			
			$where = "WHERE c.estado = 1 ";
		} else {
			$where = "WHERE c.estado = 1 AND c.colaboradores_id NOT IN(1) AND p.nombre NOT IN($valores)";
		}

		$query = "SELECT c.colaboradores_id, c.nombre AS 'nombre', c.identidad
			FROM colaboradores AS c
			INNER JOIN puestos AS p ON c.puestos_id = p.puestos_id
			" . $where . '
			ORDER BY c.nombre;';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getColaboradoresConsultaAsistencia()
	{
		$valores = "'Reseller', 'Clientes'";
		$where = "WHERE c.estado = 1 AND c.colaboradores_id NOT IN(1) AND p.nombre NOT IN($valores)";
		
		$query = "SELECT c.colaboradores_id, c.nombre AS 'nombre', c.identidad
			FROM colaboradores AS c
			INNER JOIN puestos AS p ON c.puestos_id = p.puestos_id
			" . $where . '
			ORDER BY c.nombre;';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDiasTrabajados($colaboradores_id)
	{
		$query = "SELECT COUNT(asistencia_id) AS 'total'
				FROM asistencia
				WHERE colaboradores_id = '$colaboradores_id' AND estado = 0";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getAsistencia($datos)
	{
		$fecha = '';
		$colaboradores_id = '';

		if ($datos['fechai'] != $fecha) {
			$fecha = "AND s.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";
		}

		if ($datos['colaboradores_id'] != '' || $datos['colaboradores_id'] != 0) {
			$colaboradores_id = "AND s.colaboradores_id = '" . $datos['colaboradores_id'] . "'";
		}

		$query = "SELECT a.asistencia_id AS 'asistencia_id', a.colaboradores_id AS 'colaboradores_id', c.nombre AS 'colaborador', a.fecha AS 'fecha', a.horai AS 'hora_entrada', CONVERT(a.horaf, TIME) AS 'hora_salida', DATE_FORMAT(a.horai,'%h:%i:%s %p') AS 'horai',  DATE_FORMAT(CONVERT(a.horaf, TIME),'%h:%i:%s %p') AS 'horaf', a.comentario AS 'comentario', TIME_TO_SEC(TIMEDIFF(horaf, horai))/3600 AS 'total_horas'
				FROM asistencia AS a
				INNER JOIN colaboradores AS c ON a.colaboradores_id = c.colaboradores_id
				WHERE a.estado = 0";

		$result = self::connection()->query($query);

		return $result;
	}

	/* INICIO PRIVILEGIOS */
	public function getMenusAcceso()
	{
		$query = 'SELECT m.menu_id, m.name
				FROM plan AS p
				INNER JOIN menu_plan AS mp ON p.planes_id = mp.planes_id
				INNER JOIN menu AS m ON mp.menu_id = m.menu_id';
		$result = self::connection()->query($query);

		return $result;
	}

	public function getMenusparaSubmenuAccesos($privilegio_id)
	{
		$query = "SELECT m.menu_id AS 'menu_id', m.name AS 'name'
				FROM acceso_menu AS am
				INNER JOIN menu AS m ON am.menu_id = m.menu_id
				INNER JOIN submenu AS s ON m.menu_id = s.menu_id
				WHERE am.privilegio_id = '$privilegio_id'
				GROUP BY m.menu_id";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getSubMenusAcceso($data)
	{
		$query = "SELECT s.submenu_id, s.name
				FROM plan AS p
				INNER JOIN submenu_plan AS sp ON p.planes_id = sp.planes_id
				INNER JOIN submenu AS s ON sp.submenu_id = s.submenu_id
				WHERE s.menu_id = '" . $data['menu_id'] . "'
				GROUP BY s.submenu_id";

		$result = self::connection()->query($query);

		return $result;
	}

	/**
	 * Obtiene el ID del cliente asociado a una factura específica.
	 * 
	 * @param int $factura_id El ID de la factura.
	 * @return int|false El ID del cliente si se encuentra la factura, o `false` si no se encuentra.
	 */
	public function obtenerClientePorFactura($factura_id)
	{
		// Consulta SQL para obtener el cliente_id basado en el facturas_id
		$query = "SELECT f.clientes_id FROM facturas AS f WHERE f.facturas_id = ?";
		
		// Preparar la consulta
		$stmt = self::connection()->prepare($query);
		
		// Vincular el parámetro de la consulta (factura_id)
		$stmt->bind_param("i", $factura_id);
		
		// Ejecutar la consulta
		$stmt->execute();
		
		// Obtener el resultado de la consulta
		$result = $stmt->get_result();
		
		// Verificar si se encontró la factura
		if ($result->num_rows > 0) {
			// Obtener el cliente_id
			$row = $result->fetch_assoc();
			return $row['clientes_id'];  // Retorna el ID del cliente
		} else {
			// No se encontró la factura
			return false;
		}
	}

	/**
	 * Verifica si un cliente tiene habilitado el servicio del Programa de Planes.
	 * 
	 * @param int $plan_id El ID del plan del cliente.
	 * @return bool `true` si el cliente tiene habilitado el servicio, `false` si no.
	 */
	public function verificarProgramaPuntos($plan_id)
	{
		// Consulta SQL para verificar si el cliente tiene habilitado el servicio
		$query = "SELECT sp.submenu_id
				FROM submenu_plan AS sp
				INNER JOIN plan AS p ON sp.planes_id = p.planes_id
				INNER JOIN submenu AS s ON s.submenu_id = sp.submenu_id
				WHERE p.planes_id = ? AND s.name = 'programaPuntos'";

		// Preparar la consulta
		$stmt = self::connection()->prepare($query);
		if ($stmt === false) {
			return false;  // Error en la preparación de la consulta
		}

		// Vincular el parámetro
		$stmt->bind_param("i", $plan_id);

		// Ejecutar la consulta
		$stmt->execute();

		// Obtener el resultado
		$result = $stmt->get_result();

		// Verificar si hay filas en el resultado
		if ($result->num_rows > 0) {
			// Si hay resultados, el cliente tiene habilitado el servicio
			return true;
		} else {
			// Si no hay resultados, el cliente NO tiene habilitado el servicio
			return false;
		}
	}


	/**
	 * Obtiene el programa de puntos activo.
	 * 
	 * @return array|false El programa de puntos activo o `false` si no hay un programa activo.
	 */
	public function obtenerProgramaPuntosActivo()
	{
		$query = "SELECT * FROM programa_puntos WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT 1";
		$stmt = self::connection()->prepare($query);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			return $result->fetch_assoc(); // Retorna el programa de puntos activo
		} else {
			return false; // Si no hay programa de puntos activo
		}
	}	

	/**
	 * Acumula puntos para un cliente según el programa de puntos y el monto consumido.
	 * 
	 * Este método calcula la cantidad de puntos que debe acumular un cliente dependiendo
	 * del tipo de cálculo (monto o porcentaje) y luego actualiza la tabla `puntos_cliente`
	 * con el nuevo total de puntos. También registra el movimiento de acumulación en la 
	 * tabla `historial_puntos`.
	 * 
	 * @param int $cliente_id El ID del cliente al que se le acumularán los puntos.
	 * @param int $programa_puntos_id El ID del programa de puntos asociado al cliente.
	 * @param float $monto_consumido El monto total consumido por el cliente, usado para calcular los puntos.
	 * @return bool Retorna `true` si los puntos se acumularon correctamente, o `false` si hubo un error.
	 */
	public function acumularPuntos($cliente_id, $programa_puntos_id, $monto_consumido)
	{
		// Primero obtenemos el tipo de cálculo y las condiciones del programa de puntos
		$query = "SELECT tipo_calculo, monto, porcentaje FROM programa_puntos WHERE id = ?";
		$stmt = self::connection()->prepare($query);
		$stmt->bind_param("i", $programa_puntos_id);
		$stmt->execute();
		$result = $stmt->get_result();
		
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$tipo_calculo = $row['tipo_calculo'];
			$monto = $row['monto'];
			$porcentaje = $row['porcentaje'];

			// Inicializamos las variables para calcular los puntos
			$puntos = 0;

			// Si el cálculo es por monto
			if ($tipo_calculo == 'monto') {
				// Calculamos puntos según el monto
				$puntos = $monto_consumido / $monto;
			} elseif ($tipo_calculo == 'porcentaje') {
				// Calculamos puntos según el porcentaje del monto consumido
				$puntos = ($monto_consumido * $porcentaje) / 100;
			}

			// Actualizamos los puntos del cliente
			// Primero obtenemos los puntos actuales
			$query = "SELECT total_puntos FROM puntos_cliente WHERE cliente_id = ? AND programa_puntos_id = ?";
			$stmt = self::connection()->prepare($query);
			$stmt->bind_param("ii", $cliente_id, $programa_puntos_id);
			$stmt->execute();
			$result = $stmt->get_result();

			// Si el cliente ya tiene puntos en este programa
			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$puntos_actuales = $row['total_puntos'];

				// Sumamos los puntos nuevos a los puntos existentes
				$nuevo_total_puntos = $puntos_actuales + $puntos;

				// Actualizamos el total de puntos
				$update_query = "UPDATE puntos_cliente SET total_puntos = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE cliente_id = ? AND programa_puntos_id = ?";
				$stmt = self::connection()->prepare($update_query);
				$stmt->bind_param("dii", $nuevo_total_puntos, $cliente_id, $programa_puntos_id);
				$stmt->execute();
			} else {
				// Si no tiene puntos en este programa, los insertamos
				$insert_query = "INSERT INTO puntos_cliente (cliente_id, programa_puntos_id, total_puntos) VALUES (?, ?, ?)";
				$stmt = self::connection()->prepare($insert_query);
				$stmt->bind_param("iid", $cliente_id, $programa_puntos_id, $puntos);
				$stmt->execute();
			}

			// Registrar el movimiento en el historial
			$descripcion = "Acumulación de puntos por consumo de Lempiras: $monto_consumido";
			$insert_historial_query = "INSERT INTO historial_puntos (cliente_id, programa_puntos_id, tipo_movimiento, puntos, descripcion) 
									VALUES (?, ?, 'acumulacion', ?, ?)";
			$stmt = self::connection()->prepare($insert_historial_query);
			$stmt->bind_param("iiis", $cliente_id, $programa_puntos_id, $puntos, $descripcion);
			$stmt->execute();

			return true;
		} else {
			// No se encontró el programa de puntos
			return false;
		}
	}

	/**
	 * Redime puntos de un cliente según el programa de puntos.
	 * 
	 * Este método verifica si el cliente tiene suficientes puntos para redimir. Si es así,
	 * se actualiza el total de puntos en la tabla `puntos_cliente` y se registra el movimiento
	 * de redención en la tabla `historial_puntos`.
	 * 
	 * @param int $cliente_id El ID del cliente al que se le redimirán los puntos.
	 * @param int $programa_puntos_id El ID del programa de puntos asociado al cliente.
	 * @param float $puntos_a_redimir La cantidad de puntos que el cliente desea redimir.
	 * @return bool Retorna `true` si la redención de puntos se realizó correctamente, o `false` si no tiene suficientes puntos.
	 */
	public function redimirPuntos($cliente_id, $programa_puntos_id, $puntos_a_redimir)
	{
		// Primero obtenemos los puntos actuales del cliente en el programa de puntos
		$query = "SELECT total_puntos FROM puntos_cliente WHERE cliente_id = ? AND programa_puntos_id = ?";
		$stmt = self::connection()->prepare($query);
		$stmt->bind_param("ii", $cliente_id, $programa_puntos_id);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$puntos_actuales = $row['total_puntos'];

			// Verificamos si el cliente tiene suficientes puntos para redimir
			if ($puntos_a_redimir <= $puntos_actuales) {
				// Restamos los puntos redimidos del total de puntos
				$nuevo_total_puntos = $puntos_actuales - $puntos_a_redimir;

				// Actualizamos el total de puntos
				$update_query = "UPDATE puntos_cliente SET total_puntos = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE cliente_id = ? AND programa_puntos_id = ?";
				$stmt = self::connection()->prepare($update_query);
				$stmt->bind_param("dii", $nuevo_total_puntos, $cliente_id, $programa_puntos_id);
				$stmt->execute();

				// Registrar el movimiento en el historial
				$descripcion = "Redención de puntos";
				$insert_historial_query = "INSERT INTO historial_puntos (cliente_id, programa_puntos_id, tipo_movimiento, puntos, descripcion) 
										VALUES (?, ?, 'redencion', ?, ?)";
				$stmt = self::connection()->prepare($insert_historial_query);
				$stmt->bind_param("iiis", $cliente_id, $programa_puntos_id, $puntos_a_redimir, $descripcion);
				$stmt->execute();

				return true;
			} else {
				// El cliente no tiene suficientes puntos
				return false;
			}
		} else {
			// No se encontró el cliente en este programa de puntos
			return false;
		}
	}

	public function getSubMenus1Acceso($privilegio_id)
	{
		$query = "SELECT sm.submenu_id, sm.name As 'submenu'
				FROM plan AS p
				INNER JOIN submenu_plan AS sp ON p.planes_id = sp.planes_id
				INNER JOIN submenu AS sm ON sp.submenu_id  = sm.submenu_id
				INNER JOIN submenu1 AS sm1 ON sm.submenu_id = sm1.submenu_id
				GROUP BY sm.submenu_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSubMenusConsultaAccesos($data)
	{
		$query = "SELECT sm.submenu1_id AS 'submenu_id', sm.name AS 'submenu'
				FROM plan AS p
				INNER JOIN submenu1_plan AS sp ON p.planes_id = sp.planes_id
				INNER JOIN submenu1 AS sm ON sp.submenu1_id = sm.submenu1_id
				WHERE sm.submenu_id = '" . $data['menu_id'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function delete_menuAccessos($datos)
	{
		$query = "DELETE FROM acceso_menu WHERE acceso_menu_id = '" . $datos['acceso_menu_id'] . "' AND privilegio_id = '" . $datos['privilegio_id'] . "'";
		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function delete_subMenuAccessos($datos)
	{
		$query = "DELETE FROM acceso_submenu WHERE acceso_submenu_id = '" . $datos['acceso_submenu_id'] . "' AND privilegio_id = '" . $datos['privilegio_id'] . "'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function delete_subMenu1Accessos($datos)
	{
		$query = "DELETE FROM acceso_submenu1 WHERE acceso_submenu1_id = '" . $datos['submenu_id'] . "' AND privilegio_id = '" . $datos['privilegio_id'] . "'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function getMenuAccesosDataTable($privilegio_id)
	{
		$query = "SELECT 
					am.acceso_menu_id AS 'acceso_menu_id', 
					m.name AS 'menu', 
					p.nombre AS 'privilegio', 
					p.privilegio_id AS 'privilegio_id', 
					m.menu_id AS 'menu_id',
					am.estado AS 'estado' 
				FROM acceso_menu AS am
				INNER JOIN menu AS m ON am.menu_id = m.menu_id
				INNER JOIN privilegio AS p ON am.privilegio_id = p.privilegio_id
				WHERE am.privilegio_id = '$privilegio_id'";
	
		return self::connection()->query($query);
	}
	

	public function getSubMenuAccesosDataTable($privilegio_id)
	{
		$query = "SELECT 
					asm.acceso_submenu_id AS 'acceso_submenu_id', 
					m.name AS 'menu', 
					sm.name AS 'submenu', 
					p.nombre AS 'privilegio', 
					p.privilegio_id AS 'privilegio_id', 
					sm.submenu_id AS 'submenu_id',
					asm.estado AS 'estado'
				FROM acceso_submenu AS asm
				INNER JOIN submenu AS sm ON asm.submenu_id = sm.submenu_id
				INNER JOIN menu AS m ON sm.menu_id = m.menu_id
				INNER JOIN privilegio AS p ON asm.privilegio_id = p.privilegio_id
				WHERE asm.privilegio_id = '$privilegio_id'";
	
		return self::connection()->query($query);
	}
	

	public function getSubMenu1AccesosDataTable($privilegio_id)
	{
		$query = "SELECT 
					asm1.acceso_submenu1_id AS 'acceso_submenu_id', 
					sm1.submenu_id AS 'submenu_id', 
					sm.name AS 'submenu', 
					sm1.name AS 'submenu1', 
					p.nombre AS 'privilegio', 
					p.privilegio_id AS 'privilegio_id',
					asm1.estado AS 'estado' 
				FROM acceso_submenu1 AS asm1
				INNER JOIN submenu1 AS sm1 ON asm1.submenu1_id = sm1.submenu1_id
				INNER JOIN submenu AS sm ON sm1.submenu_id = sm.submenu_id
				INNER JOIN privilegio AS p ON asm1.privilegio_id = p.privilegio_id
				WHERE asm1.privilegio_id = '$privilegio_id'";
	
		return self::connection()->query($query);
	}
	

	public function valid_menu_on_submenu_acceso($datos)
	{
		$query = "SELECT asm.acceso_submenu_id AS 'acceso_submenu_id'
				FROM acceso_submenu AS asm
				INNER JOIN submenu AS sm ON asm.submenu_id = sm.submenu_id
				INNER JOIN menu AS m ON sm.menu_id = m.menu_id
				WHERE m.menu_id = '" . $datos['menu_id'] . "' AND asm.privilegio_id = '" . $datos['privilegio_id'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function valid_submenu_on_submenu1_acceso($datos)
	{
		$query = "SELECT asm1.acceso_submenu1_id
				FROM acceso_submenu1 AS asm1
				INNER JOIN submenu1 AS sm1 ON asm1.submenu1_id = sm1.submenu1_id
				WHERE sm1.submenu_id = '" . $datos['submenu_id'] . "' AND asm1.privilegio_id = '" . $datos['privilegio_id'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}
	/* FIN PRIVILEGIOS */

	public function getDepartamentos()
	{
		$query = 'SELECT *
				FROM departamentos';
		$result = self::connection()->query($query);

		return $result;
	}

	public function getMunicipios($departamentos_id)
	{
		$query = "SELECT *
				FROM municipios WHERE departamentos_id  = '$departamentos_id'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getFacturador()
	{
		$query = "SELECT c.colaboradores_id AS 'colaboradores_id', c.nombre AS 'nombre', c.identidad AS 'identidad'
			FROM facturas AS f
			INNER JOIN colaboradores AS c
			ON f.usuario = c.colaboradores_id
			GROUP BY f.usuario";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getClientesCXC()
	{
		$query = "SELECT c.clientes_id AS 'clientes_id', c.nombre AS 'nombre'
			FROM cobrar_clientes AS cc
			INNER JOIN clientes AS c
			ON cc.clientes_id = c.clientes_id
			GROUP BY c.nombre";
		$result = self::connection()->query($query);

		return $result;
	}

	public function saldo_factura_cuentas_por_cobrar($facturas_id)
	{
		$query = "SELECT *
				FROM cobrar_clientes
				WHERE facturas_id = '$facturas_id'";
		$result = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $result;
	}

	public function getProveedoresCXP()
	{
		$query = "SELECT p.proveedores_id AS 'proveedores_id', p.nombre AS 'nombre'
			FROM pagar_proveedores AS pp
			INNER JOIN proveedores AS p
			ON pp.proveedores_id = p.proveedores_id
			GROUP BY p.nombre;";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoUsuario($datos)
	{
		$estado = $datos['estado'] ?? 1;

		if ($datos['db_cliente'] === $GLOBALS['DB_MAIN']) {
			$where = "WHERE estado = '$estado'";
		} else {
			$where = "WHERE estado = '$estado' AND tipo_user_id NOT IN(1,3)";
		}

		$query = 'SELECT *
				FROM tipo_user
				' . $where . '
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPrivilegio($datos)
	{
		if ($datos['DB_MAIN'] === $GLOBALS['DB_MAIN']) {
			$where = 'WHERE estado = 1';
		} else {
			$where = 'WHERE estado = 1 AND privilegio_id NOT IN(1,3)';
		}

		/*if($datos['privilegio_colaborador'] !== "Super Administrador"){//SUPER ADMINISTRADOR
			$where = "WHERE estado = 1";
		}else{
			$where = "WHERE estado = 1 AND privilegio_id NOT IN(1)";
		}*/

		$query = 'SELECT *
				FROM privilegio
				' . $where . '
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCajas($datos)
	{
		$fecha = date('Y-m-d');

		// Define la cláusula WHERE según la fecha y los privilegios del usuario
		if ($datos['fechai'] == $fecha) {
			$where = "WHERE a.empresa_id = '" . $datos['empresa_id_sd'] . "' 
					\t  AND a.estado = '" . $datos['estado'] . "'";
		} else {
			if ($datos['privilegio_id'] == 1 || $datos['privilegio_id'] == 2) {
				$where = "
						WHERE a.empresa_id = '" . $datos['empresa_id_sd'] . "' 
						AND a.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' 
						AND a.estado = '" . $datos['estado'] . "'
					";
			} else {
				$where = "
						WHERE a.empresa_id = '" . $datos['empresa_id_sd'] . "' 
						AND a.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' 
						AND a.colaboradores_id = '" . $datos['colaborador_id'] . "' 
						AND a.estado = '" . $datos['estado'] . "'
					";
			}
		}

		// Consulta SQL para obtener la información de las cajas
		$query = "
				SELECT 
					a.fecha AS 'fecha', 
					a.factura_inicial AS 'factura_inicial', 
					a.factura_final AS 'factura_final', 
					a.apertura AS 'monto_apertura', 
					(CASE WHEN a.estado = '1' THEN 'Activa' ELSE 'Inactiva' END) AS 'caja', 
					c.nombre AS 'usuario', 
					a.colaboradores_id AS 'colaboradores_id', 
					a.apertura_id AS 'apertura_id'
				FROM apertura AS a
				INNER JOIN colaboradores AS c ON a.colaboradores_id = c.colaboradores_id
				$where
			";

		// Ejecuta la consulta y devuelve el resultado
		$result = self::connection()->query($query);

		return $result;
	}

	public function getFacturaInicial($apertura_id)
	{
		$query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.relleno As 'relleno'

				FROM facturas AS f

				INNER JOIN secuencia_facturacion AS sf

				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id

				WHERE apertura_id = '$apertura_id' AND estado = 2

				ORDER BY f.number ASC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSaldoMovimientosCuentasSaldoAnterior($datos)
	{
		$query = "SELECT saldo
				FROM movimientos_cuentas
				WHERE MONTH(CAST(fecha AS DATE)) = MONTH(DATE_ADD('" . $datos['fechai'] . "',INTERVAL -1 MONTH)) AND cuentas_id = '" . $datos['cuentas_id'] . "'
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSaldoMovimientosCuentasUltimoSaldo($datos)
	{
		$query = "SELECT saldo, fecha
				FROM movimientos_cuentas
				WHERE cuentas_id = '" . $datos['cuentas_id'] . "' AND MONTH(fecha) = MONTH(DATE_ADD('" . $datos['fechai'] . "',INTERVAL -1 MONTH))
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDocumentoSecuenciaFacturacion($documento)
	{
		$query = "SELECT documento_id
				FROM documento
				WHERE nombre = '" . $documento . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSaldoMovimientosCuentasUltimaFecha($cuentas_id, $fecha)
	{
		$query = "SELECT saldo, fecha
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id' AND MONTH(fecha) = MONTH('$fecha')
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getFacturaFinal($apertura_id)
	{
		$query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.relleno As 'relleno'
				FROM facturas AS f
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				WHERE apertura_id = '$apertura_id' AND estado = 2
				ORDER BY f.number DESC LIMIT 1";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getImporteVentaporUsuario($apertura_id)
	{
		$query = "SELECT SUM(importe) AS 'importe'
				FROM facturas AS f
				WHERE apertura_id = '$apertura_id' AND estado = 2";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getPuestoColaboradores()
	{
		$query = 'SELECT *
				FROM puestos
				WHERE estado = 1';
		$result = self::connection()->query($query);

		return $result;
	}

	public function getUserSession($colaborador_id)
	{
		$query = "SELECT nombre
				FROM colaboradores
				WHERE colaboradores_id = '$colaborador_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getBitacora($fechai, $fechaf)
	{
		$query = "SELECT b.bitacoraCodigo AS 'bitacoraCodigo', DATE_FORMAT(b.bitacoraFecha, '%d/%m/%Y') AS 'bitacoraFecha', b.bitacoraHoraInicio As 'bitacoraHoraInicio', b.bitacoraHoraFinal AS 'bitacoraHoraFinal', tu.nombre AS 'bitacoraTipo', b.bitacoraYear AS 'bitacoraYear',c.nombre AS 'colaborador'
				FROM bitacora AS b
				INNER JOIN tipo_user AS tu
				ON b.bitacoraTipo = tu.tipo_user_id
				INNER JOIN colaboradores AS c
				ON b.colaboradores_id = c.colaboradores_id
				WHERE b.bitacoraFecha BETWEEN '$fechai' AND '$fechaf'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getHistorialAccesos($fechai, $fechaf)
	{
		$query = "SELECT ha.historial_acceso_id AS 'historial_acceso_id', DATE_FORMAT(ha.fecha, '%d/%m/%Y %H:%i:%s') AS 'fecha',c.nombre As 'colaborador', ha.ip AS 'ip', ha.acceso AS 'acceso'
				FROM historial_acceso AS ha
				INNER JOIN colaboradores AS c
				ON ha.colaboradores_id = c.colaboradores_id
				WHERE CAST(ha.fecha AS DATE) BETWEEN '$fechai' AND '$fechaf'
				ORDER BY ha.fecha DESC";
		$result = self::connection()->query($query);

		return $result;
	}

	public function obtenerPlanUsuario() {
		// Implementa la lógica para obtener el plan del usuario actual
		// Ejemplo básico:
		$consulta = "SELECT planes_id FROM usuarios WHERE usuario_id = ?";
		$resultado = $this->ejecutar_consulta_simple_preparada($consulta, "i", [$_SESSION['id']]);
		return $resultado->fetch_assoc()['planes_id'] ?? null;
	}
	
	public function obtenerSubmenuIdPorNombre($nombre) {
		$consulta = "SELECT submenu_id FROM submenu WHERE name = ?";
		$resultado = $this->ejecutar_consulta_simple_preparada($consulta, "s", [$nombre]);
		return $resultado->fetch_assoc()['submenu_id'] ?? null;
	}
	
	public function getClientes($estado)
	{
		if (!isset($_SESSION['user_sd'])) {
			session_start(['name' => 'SD']);
		}

		$privilegio_sd = $_SESSION['privilegio_sd'];
		$colaborador_id_sd = $_SESSION['colaborador_id_sd'];

		$where = "WHERE c.estado = '$estado'";

		//CONSULTAMOS EL NOMBRE DEL PRIVILEGIO
		$query_privilegio = "SELECT nombre FROM privilegio WHERE privilegio_id = $privilegio_sd ";
		$result_privilegio = self::connection()->query($query_privilegio);
		$privilegio_name = "";

		if($result_privilegio->num_rows > 0 ){
			$row = $result_privilegio->fetch_assoc();
			$privilegio_name  = $row['nombre'];
		}

		if ($privilegio_name === 'Reseller') {
			$where = "WHERE c.estado = '$estado' AND usr.privilegio_id = '$privilegio_sd'";
		}

		$query = "SELECT c.clientes_id AS 'clientes_id', 
				c.nombre AS 'cliente', 
				c.rtn AS 'rtn', 
				c.localidad AS 'localidad', 
				c.telefono AS 'telefono', 
				c.correo AS 'correo', 
				d.nombre AS 'departamento', 
				m.nombre AS 'municipio', 
				c.rtn AS 'rtn', 
				GROUP_CONCAT(DISTINCT  s.sistema_id) AS 'sistema_ids', 
				GROUP_CONCAT(DISTINCT  si.nombre) AS 'db_values', 
				c.eslogan, 
				c.otra_informacion, 
				c.whatsapp, 
				c.empresa,
				c.colaboradores_id,
				p.planes_id AS plan_id,
				c.estado    
		FROM clientes AS c
			LEFT JOIN departamentos AS d ON c.departamentos_id = d.departamentos_id
			LEFT JOIN municipios AS m ON c.municipios_id = m.municipios_id
			LEFT JOIN server_customers AS s ON c.clientes_id = s.clientes_id
			LEFT JOIN sistema AS si ON si.sistema_id=s.sistema_id
			LEFT JOIN plan p ON c.clientes_id = p.plan_id
			INNER JOIN users AS usr ON c.colaboradores_id = usr.colaboradores_id
		".$where."
		GROUP BY 
			c.clientes_id, c.nombre, c.rtn, c.localidad, c.telefono, c.correo, d.nombre, 
			m.nombre, c.rtn, c.eslogan, c.otra_informacion, c.whatsapp, c.empresa, p.planes_id;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProveedores($estado)
	{
		$query = "SELECT p.proveedores_id AS 'proveedores_id', p.nombre AS 'proveedor', p.rtn AS 'rtn' , p.localidad AS 'localidad', p.telefono AS 'telefono', p.correo AS 'correo', d.nombre AS 'departamento', m.nombre AS 'municipio', p.estado
				FROM proveedores AS p
				LEFT JOIN departamentos AS d
				ON p.departamentos_id = d.departamentos_id
				LEFT JOIN municipios AS m
				ON p.municipios_id = m.municipios_id
				WHERE p.estado = '$estado'
				ORDER BY p.nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getColaboradores()
	{
		$where = '';

		if ($GLOBALS['db'] === DB_MAIN) {
			$where = 'WHERE c.estado = 1';
		} else {
			$where = "WHERE c.estado = 1 AND c.colaboradores_id NOT IN(1) AND p.nombre NOT IN('Reseller', 'Clientes')";
		}

		$query = "SELECT c.colaboradores_id AS 'colaborador_id', c.nombre AS 'colaborador', c.identidad AS 'identidad',
				CASE WHEN c.estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS 'estado', c.telefono AS 'telefono', e.nombre AS 'empresa', p.nombre AS 'puesto'
				FROM colaboradores AS c
				INNER JOIN empresa AS e
				ON c.empresa_id = e.empresa_id
				INNER JOIN puestos AS p
				ON c.puestos_id = p.puestos_id
				" . $where . "
				ORDER BY c.nombre";

		$result = self::connection()->query($query);
		return $result;
	}

	public function getColaboradoresTabla($datos)
	{
		$estado = $datos["estado"] ?? ''; // No asignamos valor por defecto
		
		$where = '';
	
		if ($GLOBALS['db'] === DB_MAIN) {
			$where = "WHERE c.colaboradores_id NOT IN(1)";
			if ($estado !== '') {
				$where .= " AND c.estado = '$estado'";
			}
		} else {
			$where = "WHERE c.colaboradores_id NOT IN(1) AND p.nombre NOT IN('Reseller', 'Clientes') AND e.empresa_id = '" . $datos['empresa_id'] . "'";
			if ($estado !== '') {
				$where .= " AND c.estado = '$estado'";
			}
		}
	
		$query = "SELECT c.colaboradores_id AS 'colaborador_id', c.nombre AS 'colaborador', c.identidad AS 'identidad', c.estado, c.telefono AS 'telefono', e.nombre AS 'empresa', p.nombre AS 'puesto'
				FROM colaboradores AS c
				INNER JOIN empresa AS e
				ON c.empresa_id = e.empresa_id
				INNER JOIN puestos AS p
				ON c.puestos_id = p.puestos_id
				" . $where . "
				ORDER BY c.nombre";
	
		$result = self::connection()->query($query);
		return $result;
	}

	public function getColaboradoresFactura()
	{
		$query = "SELECT c.colaboradores_id AS 'colaborador_id', c.nombre AS 'colaborador', c.identidad AS 'identidad',
				CASE WHEN c.estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS 'estado', c.telefono AS 'telefono', e.nombre AS 'empresa', p.nombre AS 'puesto'
				FROM colaboradores AS c
				INNER JOIN empresa AS e
				ON c.empresa_id = e.empresa_id
				INNER JOIN puestos AS p
				ON c.puestos_id = p.puestos_id
				WHERE c.estado = 1 AND p.nombre = 'Vendedores'
				ORDER BY c.nombre";

		$result = self::connection()->query($query);
		return $result;
	}

	public function getPuestos($estado)
	{
		$query = "SELECT *
				FROM puestos
				WHERE estado = '$estado'
				ORDER BY nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function GetDetalleVentas($datos)
	{
		// Construir la consulta base
		$query = "
				SELECT 
					CASE 
						WHEN sf.documento_id = 4 THEN CONCAT('PROFORMA-', sf.prefijo, '', LPAD(f.number, sf.relleno, 0))
						ELSE CONCAT(sf.prefijo, '', LPAD(f.number, sf.relleno, 0))
					END AS numero,
					p.nombre AS Producto,
					fd.precio AS Precio,
					fd.cantidad AS Cantidad,
					fd.isv_valor AS ISV,
					fd.descuento AS Descuento,
					(fd.precio * fd.cantidad + fd.isv_valor - fd.descuento) AS Total,
					c.nombre AS Vendedor
				FROM 
					facturas_detalles fd
					INNER JOIN productos p ON fd.productos_id = p.productos_id              
					INNER JOIN facturas f ON fd.facturas_id = f.facturas_id
					INNER JOIN colaboradores c ON f.colaboradores_id = c.colaboradores_id
					INNER JOIN secuencia_facturacion sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
					INNER JOIN documento AS d ON sf.documento_id = d.documento_id
			";

		// Construir la cláusula WHERE
		$whereClause = [];

		// Verificar si se ha definido un rango de fechas
		if (!empty($datos['fechai']) && !empty($datos['fechaf'])) {
			$whereClause[] = "f.fecha BETWEEN '{$datos['fechai']}' AND '{$datos['fechaf']}' AND f.estado IN(2,3)";
		}

		// Verificar si se ha definido un producto específico
		if (!empty($datos['productos_id'])) {
			$whereClause[] = "p.productos_id = '{$datos['productos_id']}' AND f.estado IN(2,3)";
		}

		// Verificar si se ha definido un colaborador específico
		if (!empty($datos['colaboradores_id'])) {
			$whereClause[] = "c.colaboradores_id = '{$datos['colaboradores_id']}' AND f.estado IN(2,3)";
		}

		// Si no se ha definido un producto ni un colaborador, mostrar el rango de fechas
		if (empty($datos['productos_id']) && empty($datos['colaboradores_id'])) {
			if (!empty($datos['fechai']) && !empty($datos['fechaf'])) {
				$whereClause[] = "f.fecha BETWEEN '{$datos['fechai']}' AND '{$datos['fechaf']}' AND f.estado IN(2,3)";
			}
		}

		// Si hay condiciones, agregarlas a la consulta
		if (!empty($whereClause)) {
			$query .= " WHERE f.empresa_id = '{$datos['empresa_id_sd']}' AND " . implode(' AND ', $whereClause);
		}

		// Ejecutar la consulta
		$result = self::connection()->query($query);

		// Verificar si se obtuvieron resultados
		if ($result === false) {
			// Manejar el error, por ejemplo, imprimir el mensaje de error y salir
			echo 'Error al ejecutar la consulta: ' . self::connection()->error;
			exit;
		}

		// Retornar los resultados
		return $result;
	}

	public function getTipoContrato()
	{
		$query = 'SELECT *
				FROM tipo_contrato
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoNomina()
	{
		$query = 'SELECT *
				FROM tipo_nomina
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPagoPlanificado()
	{
		$query = 'SELECT *
				FROM pago_planificado
				ORDER BY nombre';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoEmpleado()
	{
		$query = 'SELECT *
				FROM tipo_empleado
				ORDER BY nombre DESC';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpleadoContrato()
	{
		$valores = "'Reseller', 'Clientes'";

		$query = "SELECT colaboradores_id aS 'colaborador_id', c.nombre AS 'nombre', c.identidad AS 'identidad'
				FROM colaboradores AS c
				INNER JOIN puestos AS p ON c.puestos_id = p.puestos_id
				WHERE c.estado = 1 AND colaboradores_id NOT IN(1) AND p.nombre NOT IN($valores) 
				ORDER BY c.nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpleadoContratoEdit($colaboradores_id)
	{
		$query = "SELECT c.colaborador_id AS colaborador_id, co.nombre AS 'nombre', co.identidad AS 'identidad', p.nombre AS 'puesto', c.contrato_id AS 'contrato_id', c.salario_mensual AS 'salario_mensual', co.fecha_ingreso AS 'fecha_ingreso', c.tipo_empleado_id AS 'tipo_empleado_id', c.pago_planificado_id AS 'pago_planificado_id', c.salario, c.semanal
				FROM contrato AS c
				INNER JOIN colaboradores AS co ON c.colaborador_id = co.colaboradores_id
				INNER JOIN puestos AS p ON co.puestos_id = p.puestos_id
				WHERE c.colaborador_id = '" . $colaboradores_id . "' AND c.estado = 1
				ORDER BY co.nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTotalesNominaDetalle($nomina_id)
	{
		$query = "SELECT SUM(nd.neto_ingresos) AS 'neto_ingresos', SUM(nd.neto_egresos) AS 'neto_egresos', SUM(nd.neto) AS 'neto'
				FROM nomina_detalles AS nd
				INNER JOIN nomina AS n ON nd.nomina_id = n.nomina_id
				WHERE nd.nomina_id = " . $nomina_id;

		$result = self::connection()->query($query);

		return $result;
	}

	public function actualizarPinServerP($datos)
	{
		$codigo_cliente = $datos['codigo_cliente'];
		$fecha_hora_fin = $datos['fecha_hora_fin'];
		$pin = $datos['pin'];

		$update = "UPDATE pin
				SET
					fecha_hora_fin = '$fecha_hora_fin'
				WHERE codigo_cliente = '$codigo_cliente' AND pin = '" . $datos['pin'] . "'";

		$result = self::connectionDBLocal(DB_MAIN)->query($update);

		return $result;
	}

	public function insertarPinServerP($datos)
	{
		$pin_id = self::correlativoDBPrincipal('pin_id', 'pin');
		$server_customers_id = $datos['server_customers_id'];
		$codigo_cliente = $datos['codigo_cliente'];
		$pin = $datos['pin'];
		$fecha_hora_inicio = $datos['fecha_hora_inicio'];
		$fecha_hora_fin = $datos['fecha_hora_fin'];

		$insert = "INSERT INTO `pin`(`pin_id`, `server_customers_id`, `codigo_cliente`, `pin`, `fecha_hora_inicio`, `fecha_hora_fin`) VALUES ('$pin_id','$server_customers_id','$codigo_cliente','$pin','$fecha_hora_inicio','$fecha_hora_fin')";

		$result = self::connectionDBLocal(DB_MAIN)->query($insert);

		return $result;
	}

	public function actualizarNomina($nomina_id, $importe)
	{
		$update = 'UPDATE nomina
				SET
					estado = 1,
					importe = ' . $importe . "
				WHERE nomina_id = '" . $nomina_id . "'";

		$result = self::connection()->query($update);

		return $result;
	}

	public function actualizarNominaDetalles($nomina_id)
	{
		$update = "UPDATE nomina_detalles
			SET
				estado = 1
			WHERE nomina_id = '" . $nomina_id . "'";

		$result = self::connection()->query($update);

		return $result;
	}

	public function getEmpleado()
	{
		$query = "SELECT co.colaborador_id AS 'colaboradores_id', nombre AS 'nombre'
			FROM contrato AS co
			INNER JOIN colaboradores AS c ON co.colaborador_id = c.colaboradores_id
			WHERE co.estado = 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentaNomina($nombre)
	{
		$query = "SELECT cuentas_id
			FROM diarios
			WHERE nombre = '" . $nombre . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentaIdNomina($nomina_id)
	{
		$query = "SELECT cuentas_id
			FROM nomina
			WHERE nomina_id = '" . $nomina_id . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function agregarEgresosMainModel($datos)
	{
		$egresos_id = mainModel::correlativo('egresos_id', 'egresos');
		$insert = "INSERT INTO egresos VALUES('" . $egresos_id . "','" . $datos['cuentas_id'] . "','" . $datos['proveedores_id'] . "','" . $datos['empresa_id'] . "','" . $datos['tipo_egreso'] . "','" . $datos['fecha'] . "','" . $datos['factura'] . "','" . $datos['subtotal'] . "','" . $datos['descuento'] . "','" . $datos['nc'] . "','" . $datos['isv'] . "','" . $datos['total'] . "','" . $datos['observacion'] . "','" . $datos['estado'] . "','" . $datos['colaboradores_id'] . "','" . $datos['fecha_registro'] . "','" . $datos['categoria_gastos_id'] . "')";

		$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);

		return $sql;
	}

	public function agregarMovimientosMainModel($datos)
	{
		$movimientos_cuentas_id = mainModel::correlativo('movimientos_cuentas_id', 'movimientos_cuentas');
		$insert = "INSERT INTO movimientos_cuentas VALUES('$movimientos_cuentas_id','" . $datos['cuentas_id'] . "','" . $datos['empresa_id'] . "','" . $datos['fecha'] . "','" . $datos['ingreso'] . "','" . $datos['egreso'] . "','" . $datos['saldo'] . "','" . $datos['colaboradores_id'] . "','" . $datos['fecha_registro'] . "')";

		$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);

		return $sql;
	}

	public function consultaSaldoMovimientosMainModel($cuentas_id)
	{
		$query = "SELECT ingreso, egreso, saldo
				FROM movimientos_cuentas
				WHERE cuentas_id = '$cuentas_id'
				ORDER BY movimientos_cuentas_id DESC LIMIT 1";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function validEgresosCuentasMainModel($datos)
	{
		$query = "SELECT egresos_id FROM egresos WHERE factura = '" . $datos['factura'] . "' AND proveedores_id = '" . $datos['proveedores_id'] . "'";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function getContratoEdit($contrato_id)
	{
		$query = "SELECT *
			FROM contrato
			WHERE contrato_id = '" . $contrato_id . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getContrato($datos)
	{
		$filtro = '';

		if ($datos['tipo_contrato'] != '' && $datos['tipo_contrato'] != 0) {
			$filtro .= " AND c.tipo_contrato_id = '" . $datos['tipo_contrato'] . "'";
		}

		if ($datos['pago_planificado'] != '' && $datos['pago_planificado'] != 0) {
			$filtro .= " AND c.pago_planificado_id = '" . $datos['pago_planificado'] . "'";
		}

		if ($datos['tipo_empleado'] != '' && $datos['tipo_empleado'] != 0) {
			$filtro .= " AND c.tipo_empleado_id = '" . $datos['tipo_empleado'] . "'";
		}

		$query = "SELECT c.contrato_id AS contrato_id,co.nombre AS 'empleado', tc.nombre AS 'tipo_contrato', pp.nombre AS 'pago_planificado', te.nombre AS 'tipo_empleado', c.fecha_inicio AS 'fecha_inicio', c.estado AS 'estado', (CASE WHEN c.estado = '1' THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', c.salario AS 'salario', c.tipo_contrato_id AS 'tipo_contrato_id', c.pago_planificado_id AS 'pago_planificado_id', c.tipo_empleado_id AS 'tipo_empleado_id', (CASE WHEN c.fecha_fin = '' THEN 'Sin Registro' ELSE c.fecha_fin END) AS 'fecha_fin', c.notas AS 'notas'
			FROM contrato AS c
			INNER JOIN colaboradores AS co ON c.colaborador_id = co.colaboradores_id
			INNER JOIN tipo_contrato AS tc ON c.tipo_contrato_id = tc.tipo_contrato_id
			INNER JOIN pago_planificado AS pp ON c.pago_planificado_id = pp.pago_planificado_id
			INNER JOIN tipo_empleado AS te ON c.tipo_empleado_id = te.tipo_empleado_id
			WHERE c.estado = '" . $datos['estado'] . "'
			$filtro
			ORDER BY co.nombre ASC";

		$result = self::connection()->query($query);

		return $result;
	}
	
	public function getNomina($datos)
	{
		$tipo_contrato_condicion = "";
	
		if (isset($datos['tipo_contrato_id']) && $datos['tipo_contrato_id'] != '' && $datos['tipo_contrato_id'] != 0) {
			$tipo_contrato_condicion = "AND c.tipo_contrato_id = '" . $datos['tipo_contrato_id'] . "'";
		}
	
		$query = "SELECT n.nomina_id AS 'nomina_id', 
						 e.nombre AS 'empresa', 
						 n.fecha_inicio AS 'fecha_inicio', 
						 n.fecha_fin AS 'fecha_fin', 
						 n.importe AS 'importe', 
						 n.notas AS 'notas', 
						 (CASE WHEN n.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', 
						 n.estado AS 'estado', 
						 n.empresa_id AS 'empresa_id', 
						 n.detalle AS 'detalle', 
						 n.pago_planificado_id AS 'pago_planificado_id'
				  FROM nomina AS n
				  INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
				  LEFT JOIN nomina_detalles AS nd ON n.nomina_id = nd.nomina_id
				  LEFT JOIN contrato AS c ON nd.colaboradores_id = c.colaborador_id
				  WHERE n.estado = '" . $datos['estado'] . "'
				  $tipo_contrato_condicion
				  ORDER BY n.fecha_registro DESC";
	
		$result = self::connection()->query($query);
		return $result;
	}

	public function getImporteNominaDetalles($nomina_id)
	{
		$query = "SELECT SUM(neto) AS neto
			FROM nomina_detalles
			WHERE nomina_id = {$nomina_id}";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNominaEdit($nomina_id)
	{
		$query = "SELECT n.nomina_id AS 'nomina_id', e.nombre AS 'empresa', n.fecha_inicio AS 'fecha_inicio', n.fecha_fin AS 'fecha_fin', n.importe AS 'importe', n.notas AS 'notas', (CASE WHEN n.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', n.estado AS 'estado', n.empresa_id AS 'empresa_id', n.detalle AS 'detalle', n.pago_planificado_id AS 'pago_planificado_id', e.empresa_id AS 'empresa_id', n.estado AS 'estado', n.tipo_nomina_id AS 'tipo_nomina_id', n.cuentas_id
			FROM nomina AS n
			INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
			WHERE n.nomina_id = '" . $nomina_id . "'
			ORDER BY n.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNominaDetalles($datos)
	{
		$empleado = '';

		if ($datos['empleado'] != '' && $datos['empleado'] != 0) {
			$empleado = "AND c.colaboradores_id = '" . $datos['empleado'] . "'";
		}		

		$query = "SELECT n.nomina_id AS 'nomina_id', nd.nomina_detalles_id AS 'nomina_detalles_id', c.nombre AS 'empleado', nd.salario AS 'salario', nd.hrse25 AS 'horas_25', nd.hrse50 As 'horas_50', nd.hrse75 AS 'horas_75', nd.hrse100 As 'horas_100', nd.retroactivo AS 'retroactivo', nd.bono AS 'bono', nd.deducciones AS 'deducciones', nd.prestamo AS 'prestamo', nd.ihss AS 'ihss', nd.rap AS 'rap', nd.estado AS 'estado', nd.estado AS 'estado', nd.nomina_detalles_id AS 'nomina_detalles_id', (CASE WHEN nd.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', nd.colaboradores_id AS 'colaboradores_id', nd.neto_ingresos As 'neto_ingresos', nd.neto_egresos AS 'neto_egresos', nd.neto AS 'neto', nd.notas AS 'notas', tp.nombre AS 'contrato', e.nombre AS 'empresa', n.fecha_inicio, n.fecha_fin
				FROM nomina_detalles AS nd
				INNER JOIN nomina AS n ON nd.nomina_id = n.nomina_id
				INNER JOIN colaboradores AS c ON nd.colaboradores_id = c.colaboradores_id
				INNER JOIN contrato AS co ON nd.colaboradores_id = co.colaborador_id
				INNER JOIN tipo_contrato AS tp ON co.tipo_contrato_id = tp.tipo_contrato_id
				INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
				WHERE nd.estado = '" . $datos['estado'] . "' AND nd.nomina_id = '" . $datos['nomina_id'] . "' AND co.estado = 1
				$empleado
				ORDER BY nd.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNominaComprobante($nomina_id)
	{
		$query = "SELECT n.nomina_id AS 'nomina_id', e.nombre AS 'empresa', n.fecha_inicio AS 'fecha_inicio', n.fecha_fin AS 'fecha_fin', n.importe AS 'importe', n.notas AS 'notas', (CASE WHEN n.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', n.estado AS 'estado', n.empresa_id AS 'empresa_id', n.detalle AS 'detalle', n.pago_planificado_id AS 'pago_planificado_id', n.pago_planificado_id AS 'pago_planificado_id', e.rtn AS 'rtn_empresa', DATE_FORMAT(n.fecha_registro, '%d/%m/%Y') AS fecha_registro, YEAR(n.fecha_registro) AS 'ano_registro', MONTHNAME(n.fecha_registro) AS 'mes_registro', n.fecha_registro AS 'fecha_registro_1', e.logotipo, e.razon_social
			FROM nomina AS n
			INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
			WHERE n.nomina_id = '" . $nomina_id . "' AND n.estado = 1
			ORDER BY n.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNominaComprobanteDetalles($nomina_id)
	{
		$query = "SELECT 
					n.nomina_id AS 'nomina_id', 
					nd.nomina_id AS 'nomina_detalles_id', 
					c.nombre AS 'empleado', 
					nd.salario AS 'salario', 
					nd.hrse25,
					nd.hrse50,
					nd.hrse75, 
					nd.hrse100, 
					nd.retroactivo AS 'retroactivo', 
					nd.bono AS 'bono', 
					nd.deducciones AS 'deducciones', 
					nd.prestamo AS 'prestamo', 
					nd.ihss AS 'ihss', 
					nd.rap AS 'rap', 
					nd.estado AS 'estado', 
					nd.estado AS 'estado', 
					nd.nomina_detalles_id AS 'nomina_detalles_id', 
					(CASE WHEN nd.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', 
					nd.colaboradores_id AS 'colaboradores_id', 
					nd.neto_ingresos AS 'neto_ingresos', 
					nd.neto_egresos AS 'neto_egresos', 
					nd.neto AS 'neto', 
					nd.notas AS 'notas', 
					tp.nombre AS 'contrato', 
					e.nombre AS 'empresa', 
					c.identidad AS 'identidad', 
					c.fecha_ingreso AS 'fecha_ingreso', 
					pc.nombre AS 'puesto', 
					nd.dias_trabajados AS 'dias_trabajados', 
					nd.otros_ingresos AS 'otros_ingresos', 
					nd.incapacidad_ihss AS 'incapacidad_ihss', 
					nd.isr AS 'isr', 
					nd.vales, 
					c.fecha_ingreso AS 'fecha_ingreso', 
					n.fecha_inicio, 
					n.fecha_fin, 
					n.fecha_registro, 
					e.logotipo, 
					nd.hrse25_valor, 
					nd.hrse50_valor, 
					nd.hrse75_valor, 
					nd.hrse100_valor
				FROM nomina_detalles AS nd
				INNER JOIN nomina AS n ON nd.nomina_id = n.nomina_id
				INNER JOIN colaboradores AS c ON nd.colaboradores_id = c.colaboradores_id
				INNER JOIN puestos AS pc ON c.puestos_id = pc.puestos_id
				INNER JOIN contrato AS co ON nd.colaboradores_id = co.colaborador_id
				INNER JOIN tipo_contrato AS tp ON co.tipo_contrato_id = tp.tipo_contrato_id
				INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
				WHERE n.nomina_id = '" . $nomina_id . "' AND co.estado = 1
				ORDER BY nd.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNominaDetallesEdit($nomina_detalles_id)
	{
		$query = "SELECT n.nomina_id AS 'nomina_id', nd.nomina_detalles_id AS 'nomina_detalles_id', c.nombre AS 'empleado', nd.salario AS 'salario', nd.hrse25 AS 'horas_25', nd.hrse50 As 'horas_50', nd.hrse75 AS 'horas_75', nd.hrse100 As 'horas_100', nd.retroactivo AS 'retroactivo', nd.bono AS 'bono', nd.deducciones AS 'deducciones', nd.prestamo AS 'prestamo', nd.ihss AS 'ihss', nd.rap AS 'rap', nd.estado AS 'estado', nd.estado AS 'estado', nd.nomina_detalles_id AS 'nomina_detalles_id', (CASE WHEN nd.estado = 1 THEN 'Activo' ELSE 'Inactivo' END) AS 'estado_nombre', nd.colaboradores_id AS 'colaboradores_id', nd.neto_ingresos As 'neto_ingresos', nd.neto_egresos AS 'neto_egresos', nd.neto AS 'neto', nd.notas AS 'notas', tp.nombre AS 'contrato', e.nombre AS 'empresa', n.pago_planificado_id AS 'pago_planificado_id', n.notas AS 'notas', c.identidad AS 'identidad', p.nombre AS 'puesto', co.contrato_id AS 'contrato_id', c.fecha_ingreso AS 'fecha_ingreso', nd.dias_trabajados AS 'dias_trabajados', nd.otros_ingresos AS 'otros_ingresos', nd.isr AS 'isr', nd.incapacidad_ihss AS 'incapacidad_ihss', nd.notas AS 'nota_detalles', nd.vales, e.logotipo, nd.hrse25_valor, nd.hrse50_valor, nd.hrse75_valor, nd.hrse100_valor, n.detalle, nd.salario_mensual, co.tipo_empleado_id
				FROM nomina_detalles AS nd
				INNER JOIN nomina AS n ON nd.nomina_id = n.nomina_id
				INNER JOIN colaboradores AS c ON nd.colaboradores_id = c.colaboradores_id
				INNER JOIN contrato AS co ON nd.colaboradores_id = co.colaborador_id
				INNER JOIN tipo_contrato AS tp ON co.tipo_contrato_id = tp.tipo_contrato_id
				INNER JOIN empresa AS e ON n.empresa_id = e.empresa_id
				INNER JOIN puestos AS p ON c.puestos_id = p.puestos_id
				WHERE nd.nomina_detalles_id = '" . $nomina_detalles_id . "'
				ORDER BY nd.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCantidadUsuariosPlan()
	{
		$query = 'SELECT *
				FROM plan';

		$result = self::connection()->query($query);

		return $result;
	}

	// Método para obtener los datos del plan

	public function getCantidadPerfilesPlan()
	{
		$query = 'SELECT perfiles FROM plan';

		return self::connection()->query($query);
	}

	public function getUsuarios($datos)
	{
		$estado = $datos['estado'] ?? 1;

		// Consulta para obtener el privilegio del colaborador
		$privilegioQuery = "SELECT nombre FROM privilegio WHERE privilegio_id = '" . $datos['privilegio_id'] . "'";
		$privilegioResult = self::connection()->query($privilegioQuery);
	
		$privilegio_colaborador = "";
	
		if ($privilegioResult && $privilegioResult->num_rows > 0) {
			$row = $privilegioResult->fetch_assoc();
			$privilegio_colaborador = $row['nombre'];
		}
	
		// Construir la consulta principal de usuarios
		if ($datos['db_cliente'] === $GLOBALS['DB_MAIN']) {
			$where = 'WHERE u.estado = 1';
		} else {
			if ($privilegio_colaborador === 'Super Administrador' ) {
				$where = "WHERE u.estado = '$estado'";
			} else if ($privilegio_colaborador === 'Administrador') {
				$where = "WHERE u.estado = '$estado' AND u.privilegio_id NOT IN(1)";
			} else {
				$where = "WHERE u.estado = '$estado' AND u.privilegio_id NOT IN(1) AND u.empresa_id = '" . $datos['empresa_id'] . "'";
			}
		}
	
		// Consulta principal sin la lógica de empresa dinámica
		$query = "SELECT u.users_id AS 'users_id', 
						 c.nombre AS 'colaborador', 
						 u.email AS 'correo', 
						 tp.nombre AS 'tipo_usuario',
						 e.nombre AS 'empresa',						 
						 CASE WHEN u.estado = 1 THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
						 u.server_customers_id, 
						 p.nombre AS 'privilegio',
						 u.estado
				  FROM users AS u
				  INNER JOIN colaboradores AS c ON u.colaboradores_id = c.colaboradores_id
				  INNER JOIN tipo_user AS tp ON u.tipo_user_id = tp.tipo_user_id
				  INNER JOIN privilegio AS p ON u.privilegio_id = p.privilegio_id
				  INNER JOIN empresa AS e ON u.empresa_id = e.empresa_id
				  " . $where . "
				  ORDER BY c.nombre";

		$result = self::connection()->query($query);
	
		// Procesar los resultados para obtener el nombre de la empresa dinámica
		$usuarios = [];
		while ($row = $result->fetch_assoc()) {
			// Si server_customers_id no es 0, obtener el nombre de la empresa desde la base de datos correspondiente
			if ($row['server_customers_id'] != 0) {
				try {
					$query_db = "SELECT db FROM server_customers WHERE server_customers_id = ?";
					$stmt_db = self::connection()->prepare($query_db);
					$stmt_db->bind_param("i", $row['server_customers_id']);
					$stmt_db->execute();
					$result_db = $stmt_db->get_result();

					if ($result_db && $result_db->num_rows > 0) {
						$db_row = $result_db->fetch_assoc();
						$db_name = $db_row['db'];

						if (!empty($db_name)) {
							$query_empresa = "SELECT nombre FROM `".self::connection()->real_escape_string($db_name)."`.empresa WHERE empresa_id = 1";
							$result_empresa = self::connection()->query($query_empresa);
							// ... resto del código
						}
					}
				} catch (Exception $e) {
					// Loggear el error o manejar la excepción
					$row['empresa'] = "No disponible";
				}
			}
	
			$usuarios[] = $row;
		}
	
		return $usuarios;
	}

	public function getSecuenciaFacturacion($datos)
	{
		$privilegio = $datos['privilegio_colaborador'];
		$empresaId = $datos['empresa_id'];
		$estado = $datos['estado'] ?? 1;

		$query = "
			SELECT sf.secuencia_facturacion_id AS 'secuencia_facturacion_id', sf.cai AS 'cai', 
			   sf.prefijo AS 'prefijo', sf.relleno AS 'relleno', sf.incremento AS 'incremento', 
			   sf.siguiente AS 'siguiente', sf.rango_inicial AS 'rango_inicial', sf.rango_final AS 'rango_final', 
			   DATE_FORMAT(sf.fecha_activacion, '%d/%m/%Y') AS 'fecha_activacion', 
			   DATE_FORMAT(sf.fecha_registro, '%d/%m/%Y') AS 'fecha_registro', 
			   e.nombre AS 'empresa', 
			   DATE_FORMAT(sf.fecha_limite, '%d/%m/%Y') AS 'fecha_limite', 
			   d.nombre AS 'documento', sf.activo AS 'estado'
			FROM secuencia_facturacion AS sf
			INNER JOIN empresa AS e ON sf.empresa_id = e.empresa_id
			INNER JOIN documento AS d ON sf.documento_id = d.documento_id
			WHERE sf.activo = '$estado'";

		if ($privilegio !== 'Administrador' && $privilegio !== 'Super Administrador') {
			$query .= " AND e.empresa_id = '$empresaId'";
		}
		$result = self::connection()->query($query);

		return $result;
	}

	public function getDocumentosfiscalesDashboard()
	{
		$query = "SELECT sf.secuencia_facturacion_id AS 'secuencia_facturacion_id', sf.cai AS 'cai', sf.prefijo AS 'prefijo', sf.relleno AS 'relleno', sf.incremento AS 'incremento', sf.siguiente AS 'siguiente', sf.rango_inicial AS 'rango_inicial', sf.rango_final AS 'rango_final', DATE_FORMAT(sf.fecha_activacion, '%d/%m/%Y') AS 'fecha_activacion', DATE_FORMAT(sf.fecha_registro, '%d/%m/%Y') AS 'fecha_registro', e.nombre AS 'empresa', DATE_FORMAT(sf.fecha_limite, '%d/%m/%Y') AS 'fecha_limite', d.nombre AS 'documento'
				FROM secuencia_facturacion AS sf
				INNER JOIN empresa AS e
				ON sf.empresa_id = e.empresa_id
				INNER JOIN documento as d
				ON sf.documento_id = d.documento_id
				WHERE sf.activo = 1
				ORDER BY sf.fecha_registro";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getISV($documento)
	{
		$query = "SELECT i.isv_id AS 'isv_id', i.isv_tipo_id AS 'isv_tipo_id', i.valor AS 'valor', it.nombre AS 'tipo_isv', i.activar
				FROM isv AS i
				INNER JOIN isv_tipo As it
				ON i.isv_tipo_id = it.isv_tipo_id
				WHERE it.nombre = '$documento'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getHoraInicio($colaborador_id)
	{
		$query = "SELECT horai
			FROM asistencia
			WHERE colaboradores_id = '$colaborador_id' AND CAST(fecha_registro AS DATE) = CAST(NOW() AS DATE)";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getISVEstadoProducto($productos_id)
	{
		$query = "SELECT isv_venta
				FROM productos
				WHERE productos_id = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoProducto($productos_id)
	{
		$query = "SELECT tp.nombre AS 'tipo_producto'
				FROM productos AS p
				INNER JOIN tipo_producto AS tp
				ON p.tipo_producto_id = tp.tipo_producto_id
				WHERE p.productos_id = '$productos_id'
				GROUP BY p.productos_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTotalHijosporPadre($productos_id)
	{
		$query = "SELECT productos_id
				FROM productos
				WHERE id_producto_superior = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function generarCodigoBarra()
	{
		$codigo = date('YmdHis');  // Obtener la fecha y agregar "K"
		return substr($codigo, 0, 12);  // Tomar solo los primeros 12 caracteres
	}

	public function getProductoHijo($producto_id)
	{
		$query = "SELECT
			productos.productos_id,
			productos.id_producto_superior,
			productos.nombre,
			medida.nombre as medida
			FROM
			productos
			INNER JOIN medida ON productos.medida_id = medida.medida_id
			WHERE productos.id_producto_superior = '$producto_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMedidaProductoPadre($productos_id)
	{
		$query = "SELECT m.nombre AS 'medida'
				FROM productos AS p
				INNER JOIN medida AS m
				ON p.medida_id = m.medida_id
				WHERE p.productos_id = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function ejecutarConsultaSimple($query){
		$result = self::connection()->query($query);

		return $result;
	}

	public function ejecutar_consulta_simple_preparada($query, $types = "", $params = array()) {
		$conexion = self::connection();
		
		// Preparar la declaración
		$stmt = $conexion->prepare($query);
		
		if (!$stmt) {
			throw new Exception("Error al preparar la consulta: " . $conexion->error);
		}
		
		// Si hay parámetros para vincular
		if (!empty($types) && !empty($params)) {
			// Crear un array con referencias para bind_param
			$bind_params = array();
			
			// El primer elemento es el string de tipos
			$bind_params[] = &$types;
			
			// Agregar referencias de los parámetros
			foreach ($params as $key => $value) {
				$bind_params[] = &$params[$key];
			}
			
			// Usar call_user_func_array para vincular los parámetros
			call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		}
		
		// Ejecutar la consulta
		if (!$stmt->execute()) {
			throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
		}
		
		// Obtener el resultado (para SELECT, SHOW, DESCRIBE, EXPLAIN)
		$result = $stmt->get_result();
		
		// Si es una consulta que no devuelve resultados (INSERT, UPDATE, DELETE)
		if ($result === false) {
			// Verificar si hay filas afectadas
			if ($stmt->affected_rows > 0) {
				return true;
			} else {
				return false;
			}
		}
		
		return $result;
	}

	public function getProductosUnificado($datos)
	{
		$empresa_id = $datos['empresa_id_sd'];
		$estado = $datos['estado'] ?? 1;

		$query = "SELECT 
					p.barCode AS 'barCode', 
					p.productos_id AS 'productos_id', 
					p.nombre AS 'nombre', 
					p.descripcion AS 'descripcion', 
					p.precio_compra AS 'precio_compra', 
					p.precio_venta AS 'precio_venta', 
					m.nombre AS 'medida', 
					a.nombre AS 'almacen', 
					u.nombre AS 'ubicacion', 
					e.nombre AS 'empresa',
					p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END) AS 'estado', 
					(CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END) AS 'isv',
					tp.tipo_producto_id AS 'tipo_producto_id', 
					tp.nombre AS 'categoria', 
					(CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END) AS 'isv_venta', 
					(CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END) AS 'isv_compra', 
					p.file_name AS 'image', 
					p.porcentaje_venta,
					COALESCE(SUM(mov.cantidad_entrada) - SUM(mov.cantidad_salida), 0) AS 'saldo',
				FROM productos AS p
				INNER JOIN medida AS m ON p.medida_id = m.medida_id
				INNER JOIN almacen AS a ON p.almacen_id = a.almacen_id
				INNER JOIN ubicacion AS u ON a.ubicacion_id = u.ubicacion_id
				INNER JOIN empresa AS e ON u.empresa_id = e.empresa_id
				INNER JOIN tipo_producto AS tp ON p.tipo_producto_id = tp.tipo_producto_id
				LEFT JOIN movimientos AS mov ON p.productos_id = mov.productos_id
				WHERE p.estado = '$estado'
					AND e.empresa_id = '$empresa_id'
				GROUP BY p.productos_id
				HAVING (tp.tipo_producto_id = 2 OR saldo > 0)";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSaldoProductosMovimientosBodega($productos_id, $almacen_id)
	{
		$query = "SELECT
					SUM(m.cantidad_entrada) AS 'entrada',
					SUM(m.cantidad_salida) AS 'salida',
					(
						SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)
					) AS 'saldo'
				FROM
					movimientos AS m
				INNER JOIN productos AS p ON m.productos_id = p.productos_id
				WHERE p.estado = 1 AND p.productos_id = '$productos_id' AND m.almacen_id = '$almacen_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	protected function agregar_movimiento_productos_modelo($datos)
	{
		$movimientos_id = mainModel::correlativo('movimientos_id', 'movimientos');
		$documento = 'Entrada Movimientos ' . $movimientos_id;
	
		// Manejar valores opcionales
		$almacen_id = isset($datos['almacen_id']) ? $datos['almacen_id'] : 0;
		$lote_id = isset($datos['lote_id']) ? $datos['lote_id'] : 0;
	
		// Conexión a la base de datos
		$conn = mainModel::connection();
		
		// Sentencia preparada
		$query = "INSERT INTO movimientos (
			movimientos_id, 
			productos_id, 
			documento, 
			cantidad_entrada, 
			cantidad_salida, 
			saldo, 
			empresa_id, 
			fecha_registro, 
			clientes_id, 
			comentario, 
			almacen_id, 
			lote_id
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	
		if ($stmt = $conn->prepare($query)) {
			// Asociar los parámetros
			$stmt->bind_param(
				"iisiiiisisii",
				$movimientos_id, 
				$datos['productos_id'], 
				$documento, 
				$datos['cantidad_entrada'], 
				$datos['cantidad_salida'], 
				$datos['saldo'], 
				$datos['empresa'], 
				$datos['fecha_registro'], 
				$datos['clientes_id'], 
				$datos['comentario'], 
				$almacen_id, 
				$lote_id
			);
	
			// Ejecutar y cerrar
			$stmt->execute();
			$stmt->close();
			
			return true;
		} else {
			return false;
		}
	}	

	public function getSaldoProductosMovimientos($productos_id)
	{
		$query = "SELECT
						SUM(m.cantidad_entrada) AS 'entrada',
						SUM(m.cantidad_salida) AS 'salida',
						(
							SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)
						) AS 'saldo'
					FROM
						movimientos AS m
					INNER JOIN productos AS p ON m.productos_id = p.productos_id
					WHERE p.estado = 1 AND p.productos_id = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProductos($datos)
	{
		$query = "SELECT p.barCode AS 'barCode', p.productos_id AS 'productos_id', p.nombre AS 'nombre', p.descripcion AS 'descripcion', p.precio_compra AS 'precio_compra', p.precio_venta AS 'precio_venta',m.nombre AS 'medida', a.nombre AS 'almacen', u.nombre AS 'ubicacion', e.nombre AS 'empresa',
				p.estado AS 'estado', (CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END) AS 'isv',
				tp.tipo_producto_id AS 'tipo_producto_id', tp.nombre AS 'categoria', (CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END) AS 'isv_venta', (CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END) AS 'isv_compra', p.file_name AS 'image', p.porcentaje_venta
					FROM productos AS p
					INNER JOIN medida AS m
					ON p.medida_id = m.medida_id
					INNER JOIN almacen AS a
					ON p.almacen_id = a.almacen_id
					INNER JOIN ubicacion AS u
					ON a.ubicacion_id = u.ubicacion_id
					INNER JOIN empresa AS e
					ON u.empresa_id = e.empresa_id
					INNER JOIN tipo_producto AS tp
					ON p.tipo_producto_id = tp.tipo_producto_id
					WHERE p.estado = '" . $datos['estado'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCantidadProductos($productos_id)
	{
		$query = "SELECT id_producto_superior
				FROM productos
				WHERE productos_id = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProductosConInventarioYServicios($datos)
	{
		$bodega = '';
		$barCode = '';

		// Filtro por bodega (solo para productos, no para servicios)
		if ($datos['bodega'] != '' && $datos['bodega'] != '0') {
			$bodega = "AND (m.almacen_id = '" . $datos['bodega'] . "' OR m.almacen_id IS NULL OR m.almacen_id = 0)";
		}

		// Filtro por código de barras
		if ($datos['barcode'] != '') {
			$barCode = "AND p.barCode = '" . $datos['barcode'] . "'";
		}

		// Consulta unificada: Productos + Servicios
		$query = "
			-- Consulta para productos (siempre incluye todos)
			SELECT
				m.almacen_id,
				m.movimientos_id AS 'movimientos_id',
				p.barCode AS 'barCode',
				p.nombre AS 'nombre',
				me.nombre AS 'medida',
				IFNULL(SUM(m.cantidad_entrada), 0) AS 'entrada',
				IFNULL(SUM(m.cantidad_salida), 0) AS 'salida',
				IFNULL((SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)), 0) AS 'cantidad',
				bo.nombre AS 'almacen',
				DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				p.precio_compra AS 'precio_compra',
				p.precio_venta,
				p.precio_mayoreo,
				p.cantidad_mayoreo,
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
				CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END AS 'isv',
				tp.nombre AS 'tipo_producto_nombre',
				CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END AS 'isv_venta',
				CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END AS 'isv_compra',
				(SELECT id_producto_superior FROM productos WHERE productos_id = p.productos_id) AS 'id_producto_superior'
			FROM
				productos AS p
			LEFT JOIN movimientos AS m 
				ON m.productos_id = p.productos_id 
				AND m.empresa_id = '" . $datos['empresa_id_sd'] . "'
			LEFT JOIN medida AS me 
				ON p.medida_id = me.medida_id
			LEFT JOIN almacen AS bo 
				ON m.almacen_id = bo.almacen_id
			INNER JOIN tipo_producto AS tp 
				ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
				AND tp.tipo_producto_id = 1  -- Solo productos
				$barCode
				$bodega
			GROUP BY
				p.productos_id, m.almacen_id
			UNION ALL
			-- Consulta para servicios
			SELECT
				NULL AS 'almacen_id',
				NULL AS 'movimientos_id',
				p.barCode AS 'barCode',
				p.nombre AS 'nombre',
				me.nombre AS 'medida',
				0 AS 'entrada',
				0 AS 'salida',
				0 AS 'cantidad',
				NULL AS 'almacen',
				NULL AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				p.precio_compra AS 'precio_compra',
				p.precio_venta,
				p.precio_mayoreo,
				p.cantidad_mayoreo,
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
				CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END AS 'isv',
				tp.nombre AS 'tipo_producto_nombre',
				CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END AS 'isv_venta',
				CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END AS 'isv_compra',
				(SELECT id_producto_superior FROM productos WHERE productos_id = p.productos_id) AS 'id_producto_superior'
			FROM
				productos AS p
			LEFT JOIN medida AS me 
				ON p.medida_id = me.medida_id
			INNER JOIN tipo_producto AS tp 
				ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
				AND tp.tipo_producto_id = 2  -- Solo servicios
				$barCode
			ORDER BY
				tipo_producto_id ASC, nombre ASC;
		";
		$result = self::connection()->query($query);
		return $result;
	}
	
	public function getProductosConInventarioYServiciosCotizacion($datos)
	{
		$bodega = '';
		$barCode = '';

		// Filtro por bodega (solo para productos, no para servicios)
		if ($datos['bodega'] != '' && $datos['bodega'] != '0') {
			$bodega = "AND (m.almacen_id = '" . $datos['bodega'] . "' OR m.almacen_id IS NULL OR m.almacen_id = 0)";
		}

		// Filtro por código de barras
		if ($datos['barcode'] != '') {
			$barCode = "AND p.barCode = '" . $datos['barcode'] . "'";
		}

		// Consulta unificada: Productos + Servicios
		$query = "
			-- Consulta para productos (siempre incluye todos)
			SELECT
				m.almacen_id,
				m.movimientos_id AS 'movimientos_id',
				p.barCode AS 'barCode',
				p.nombre AS 'nombre',
				me.nombre AS 'medida',
				IFNULL(SUM(m.cantidad_entrada), 0) AS 'entrada',
				IFNULL(SUM(m.cantidad_salida), 0) AS 'salida',
				IFNULL((SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)), 0) AS 'cantidad',
				bo.nombre AS 'almacen',
				DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				p.precio_compra AS 'precio_compra',
				p.precio_venta,
				p.precio_mayoreo,
				p.cantidad_mayoreo,
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
				CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END AS 'isv',
				tp.nombre AS 'tipo_producto_nombre',
				CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END AS 'isv_venta',
				CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END AS 'isv_compra',
				(SELECT id_producto_superior FROM productos WHERE productos_id = p.productos_id) AS 'id_producto_superior'
			FROM
				productos AS p
			LEFT JOIN movimientos AS m 
				ON m.productos_id = p.productos_id 
				AND m.empresa_id = '" . $datos['empresa_id_sd'] . "'
			LEFT JOIN medida AS me 
				ON p.medida_id = me.medida_id
			LEFT JOIN almacen AS bo 
				ON m.almacen_id = bo.almacen_id
			INNER JOIN tipo_producto AS tp 
				ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
				AND tp.tipo_producto_id = 1  -- Solo productos
				$barCode
				$bodega
			GROUP BY
				p.productos_id, m.almacen_id
			UNION ALL
			-- Consulta para servicios
			SELECT
				NULL AS 'almacen_id',
				NULL AS 'movimientos_id',
				p.barCode AS 'barCode',
				p.nombre AS 'nombre',
				me.nombre AS 'medida',
				0 AS 'entrada',
				0 AS 'salida',
				0 AS 'cantidad',
				NULL AS 'almacen',
				NULL AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				p.precio_compra AS 'precio_compra',
				p.precio_venta,
				p.precio_mayoreo,
				p.cantidad_mayoreo,
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
				CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END AS 'isv',
				tp.nombre AS 'tipo_producto_nombre',
				CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END AS 'isv_venta',
				CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END AS 'isv_compra',
				(SELECT id_producto_superior FROM productos WHERE productos_id = p.productos_id) AS 'id_producto_superior'
			FROM
				productos AS p
			LEFT JOIN medida AS me 
				ON p.medida_id = me.medida_id
			INNER JOIN tipo_producto AS tp 
				ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
				AND tp.tipo_producto_id = 2  -- Solo servicios
				$barCode
			ORDER BY
				tipo_producto_id ASC, nombre ASC;
		";
		$result = self::connection()->query($query);
		return $result;
	}
	public function getProductosCantidad($datos)
	{
		$bodega = '';
		$barCode = '';

		// Condición para filtrar por bodega
		if ($datos['bodega'] != '') {
			$bodega = "AND m.almacen_id = '" . $datos['bodega'] . "'";
		}
		if ($datos['bodega'] == '0') {
			$bodega = '';  // Si la bodega es 0, se ignora el filtro de bodega
		}

		// Condición para filtrar por código de barras
		if ($datos['barcode'] != '') {
			$barCode = "AND p.barCode = '" . $datos['barcode'] . "'";
		}

		// Consulta ajustada con filtros dinámicos para bodega y código de barras
		$query = "
			SELECT
				m.almacen_id,
				m.movimientos_id AS 'movimientos_id',
				p.barCode AS 'barCode',
				p.nombre AS 'nombre',
				me.nombre AS 'medida',
				IFNULL(SUM(m.cantidad_entrada), 0) AS 'entrada',
				IFNULL(SUM(m.cantidad_salida), 0) AS 'salida',
				IFNULL((SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)), 0) AS 'cantidad',
				bo.nombre AS 'almacen',
				DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				p.precio_compra AS 'precio_compra',
				p.precio_venta,
				p.precio_mayoreo,
				p.cantidad_mayoreo,
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END AS 'estado',
				CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END AS 'isv',
				tp.nombre AS 'tipo_producto_nombre',
				CASE WHEN p.isv_venta = '1' THEN 'Si' ELSE 'No' END AS 'isv_venta',
				CASE WHEN p.isv_compra = '1' THEN 'Si' ELSE 'No' END AS 'isv_compra'
			FROM
				productos AS p
			LEFT JOIN movimientos AS m ON m.productos_id = p.productos_id 
				AND m.empresa_id = '" . $datos['empresa_id_sd'] . "' 
				$bodega
			LEFT JOIN medida AS me ON p.medida_id = me.medida_id
			LEFT JOIN almacen AS bo ON m.almacen_id = bo.almacen_id
			INNER JOIN tipo_producto AS tp ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
				AND tp.tipo_producto_id IN (1, 2)
				$barCode
				$bodega
			GROUP BY
				p.productos_id, m.almacen_id
			ORDER BY
				p.fecha_registro ASC;
		";

		$result = self::connection()->query($query);

		return $result;
	}

	/*public function getProductosCantidad($datos)
	{
		if ($datos['bodega'] == 1) {
			$bodega = '';
			$barCode = '';

			if ($datos['bodega'] != '') {
				$bodega = "AND m.almacen_id = '" . $datos['bodega'] . "'";
			}
			if ($datos['bodega'] == '0') {
				$bodega = '';
			}

			if ($datos['barcode'] != '') {
				$barCode = "AND p.barCode  = '" . $datos['barcode'] . "'";
			}

			$query = "
				SELECT
					m.almacen_id,
					m.movimientos_id AS 'movimientos_id',
					p.barCode AS 'barCode',
					p.nombre AS 'nombre',
					me.nombre AS 'medida',
					SUM(m.cantidad_entrada) AS 'entrada',
					SUM(m.cantidad_salida) AS 'salida',
					(SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)) AS 'cantidad',
					bo.nombre AS 'almacen',
					DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
					p.productos_id AS 'productos_id',
					p.id_producto_superior,
					p.precio_compra AS 'precio_compra',
					p.precio_venta,
					p.precio_mayoreo,
					p.cantidad_mayoreo,
					p.isv_venta AS 'impuesto_venta',
					p.isv_compra AS 'isv_compra',
					p.file_name AS 'image',
					tp.tipo_producto_id AS 'tipo_producto_id',
					tp.nombre AS 'tipo_producto',
					CASE
						WHEN p.estado = '1' THEN 'Activo'
						ELSE 'Inactivo'
					END AS 'estado',
					CASE
						WHEN p.isv_venta = '1' THEN 'Sí'
						ELSE 'No'
					END AS 'isv',
					tp.nombre AS 'tipo_producto_nombre',
					CASE
						WHEN p.isv_venta = '1' THEN 'Si'
						ELSE 'No'
					END AS 'isv_venta',
					CASE
						WHEN p.isv_compra = '1' THEN 'Si'
						ELSE 'No'
					END AS 'isv_compra'
				FROM
					movimientos AS m
				RIGHT JOIN productos AS p ON m.productos_id = p.productos_id
				LEFT JOIN medida AS me ON p.medida_id = me.medida_id
				LEFT JOIN almacen AS bo ON m.almacen_id = bo.almacen_id
				INNER JOIN tipo_producto AS tp ON p.tipo_producto_id = tp.tipo_producto_id
				WHERE
					p.empresa_id = '" . $datos['empresa_id_sd'] . "' AND  p.estado = 1
				AND tp.tipo_producto_id IN (1, 2) -- Agregamos esta condición para incluir Productos y Servicios
				$barCode
				GROUP BY
					p.productos_id, m.almacen_id
				ORDER BY
					p.fecha_registro ASC";
		} else {
			$bodega = '';
			$barCode = '';

			if ($datos['bodega'] != '') {
				$bodega = "AND m.almacen_id = '" . $datos['bodega'] . "'";
			}
			if ($datos['bodega'] == '0') {
				$bodega = '';
			}

			if ($datos['barcode'] != '') {
				$barCode = "AND p.barCode  = '" . $datos['barcode'] . "'";
			}

			$query = "
				SELECT
					m.almacen_id,
					m.movimientos_id AS 'movimientos_id',
					p.barCode AS 'barCode',
					p.nombre AS 'nombre',
					me.nombre AS 'medida',
					SUM(m.cantidad_entrada) AS 'entrada',
					SUM(m.cantidad_salida) AS 'salida',
					(SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)) AS 'cantidad',
					bo.nombre AS 'almacen',
					DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
					p.productos_id AS 'productos_id',
					p.id_producto_superior,
					p.precio_compra AS 'precio_compra',
					p.precio_venta,
					p.precio_mayoreo,
					p.cantidad_mayoreo,
					p.isv_venta AS 'impuesto_venta',
					p.isv_compra AS 'isv_compra',
					p.file_name AS 'image',
					tp.tipo_producto_id AS 'tipo_producto_id',
					tp.nombre AS 'tipo_producto',
					CASE
						WHEN p.estado = '1' THEN 'Activo'
						ELSE 'Inactivo'
					END AS 'estado',
					CASE
						WHEN p.isv_venta = '1' THEN 'Sí'
						ELSE 'No'
					END AS 'isv',
					tp.nombre AS 'tipo_producto_nombre',
					CASE
						WHEN p.isv_venta = '1' THEN 'Si'
						ELSE 'No'
					END AS 'isv_venta',
					CASE
						WHEN p.isv_compra = '1' THEN 'Si'
						ELSE 'No'
					END AS 'isv_compra'
				FROM
					movimientos AS m
				RIGHT JOIN productos AS p ON m.productos_id = p.productos_id
				LEFT JOIN medida AS me ON p.medida_id = me.medida_id
				LEFT JOIN almacen AS bo ON m.almacen_id = bo.almacen_id
				INNER JOIN tipo_producto AS tp ON p.tipo_producto_id = tp.tipo_producto_id
				WHERE
					p.empresa_id = '" . $datos['empresa_id_sd'] . "' AND p.estado = 1
				AND tp.tipo_producto_id IN (1, 2) -- Agregamos esta condición para incluir Productos y Servicios
				$bodega
				$barCode
				GROUP BY
					p.productos_id, m.almacen_id
				ORDER BY
					p.fecha_registro ASC";
		}

		$result = self::connection()->query($query);

		return $result;
	}*/

	public function getProductosCantidadCompras($datos)
	{
		$query = "SELECT p.productos_id AS 'productos_id', p.barCode AS 'barCode', p.productos_id AS 'productos_id', p.nombre AS 'nombre', p.descripcion AS 'descripcion', m.nombre AS 'medida',
			(CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END) AS 'estado', (CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END) AS 'isv',
			tp.tipo_producto_id AS 'tipo_producto_id', tp.nombre AS 'tipo_producto', p.colaborador_id AS 'colaborador_id', p.file_name AS 'image', p.precio_compra
				FROM productos AS p
				INNER JOIN medida AS m
				ON p.medida_id = m.medida_id
				INNER JOIN tipo_producto AS tp
				ON p.tipo_producto_id = tp.tipo_producto_id
				WHERE
					p.empresa_id = '" . $datos['empresa_id_sd'] . "' AND p.estado = 1
				GROUP BY
					p.productos_id
				ORDER BY
					p.fecha_registro ASC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProductosFacturas($datos)
	{
		$bodega = '';
		$barCode = '';

		if ($datos['bodega'] != '') {
			$bodega = "AND a.almacen_id = '" . $datos['bodega'] . "'";
		}
		if ($datos['bodega'] == '0') {
			$bodega = '';
		}

		if ($datos['barcode'] != '') {
			$barCode = "AND p.barCode  = '" . $datos['barcode'] . "'";
		}

		$query = "
			SELECT
				p.productos_id AS 'productos_id',
				p.barCode AS 'barCode',
				p.productos_id AS 'productos_id',
				p.nombre AS 'nombre',
				p.descripcion AS 'descripcion',
				p.cantidad AS 'cantidad',
				p.precio_compra AS 'precio_compra',
				p.precio_venta AS 'precio_venta',
				m.nombre AS 'medida',
				a.nombre AS 'almacen',
				a.almacen_id,
				u.nombre AS 'ubicacion',
				e.nombre AS 'empresa',
				(
					CASE
					WHEN p.estado = '1' THEN
						'Activo'
					ELSE
						'Inactivo'
					END
				) AS 'estado',
				(
					CASE
					WHEN p.isv_venta = '1' THEN
						'Sí'
					ELSE
						'No'
					END
				) AS 'isv',
				tp.tipo_producto_id AS 'tipo_producto_id',
				tp.nombre AS 'tipo_producto',
				p.isv_venta AS 'impuesto_venta',
				p.isv_compra AS 'isv_compra',
				p.file_name AS 'image',
				p.cantidad_mayoreo AS 'cantidad_mayoreo',
				p.precio_mayoreo AS 'precio_mayoreo'
			FROM
				productos AS p
			INNER JOIN medida AS m ON p.medida_id = m.medida_id
			INNER JOIN almacen AS a ON p.almacen_id = a.almacen_id
			INNER JOIN ubicacion AS u ON a.ubicacion_id = u.ubicacion_id
			INNER JOIN empresa AS e ON u.empresa_id = e.empresa_id
			INNER JOIN tipo_producto AS tp ON p.tipo_producto_id = tp.tipo_producto_id
			WHERE
				p.estado = 1
			AND tp.nombre NOT IN ('Insumos')
			$bodega
			$barCode
			";

		$result = self::connection()->query($query);
		return $result;
	}

	public function getProductosMovimientos($datos)
	{
		$query = "SELECT p.productos_id AS 'productos_id', p.barCode AS 'barCode', p.productos_id AS 'productos_id', p.nombre AS 'nombre', p.descripcion AS 'descripcion', p.cantidad AS 'cantidad', p.precio_compra AS 'precio_compra', p.precio_venta AS 'precio_venta',m.nombre AS 'medida', a.nombre AS 'almacen', u.nombre AS 'ubicacion', e.nombre AS 'empresa',
			(CASE WHEN p.estado = '1' THEN 'Activo' ELSE 'Inactivo' END) AS 'estado', (CASE WHEN p.isv_venta = '1' THEN 'Sí' ELSE 'No' END) AS 'isv',
			tp.tipo_producto_id AS 'tipo_producto_id', tp.nombre AS 'tipo_producto', p.isv_venta AS 'impuesto_venta', p.isv_compra AS 'isv_compra', p.colaborador_id AS 'colaborador_id'
				FROM productos AS p
				INNER JOIN medida AS m
				ON p.medida_id = m.medida_id
				INNER JOIN almacen AS a
				ON p.almacen_id = a.almacen_id
				INNER JOIN ubicacion AS u
				ON a.ubicacion_id = u.ubicacion_id
				INNER JOIN empresa AS e
				ON u.empresa_id = e.empresa_id
				INNER JOIN tipo_producto AS tp
				ON p.tipo_producto_id = tp.tipo_producto_id
				WHERE p.empresa_id = '" . $datos['empresa_id_sd'] . "' AND p.estado = 1 AND p.tipo_producto_id = '" . $datos['categoria'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	function getProductoTipoProducto($tipo_producto_id)
	{
		$query = "SELECT *
				FROM productos
				WHERE tipo_producto_id = '$tipo_producto_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMedida($estado)
	{
		$query = "
			SELECT
			*
			FROM
			medida
			WHERE estado = '$estado'
			ORDER BY medida_id ASC";
			
		$result = self::connection()->query($query);
		return $result;
	}

	public function getPlanSistema()
	{
		$query = 'SELECT *
				FROM plan';

		$result = self::connection()->query($query);
		return $result;
	}

	public function sms_proveedor()
	{
		$query = '
				SELECT
				*
				FROM
				sms_proveedor
				WHERE estado = 1';

		$result = self::connection()->query($query);
		return $result;
	}

	public function insertSMS($data)
	{
		$query = "INSERT INTO `sms`(`sms_id`, `proveedor_id`, `number`, `text`, `response`, `date`) VALUES ('{$data['id']}','{$data['proveedor_id']}','{$data['msisdn']}','{$data['message']}','{$data['response']}','{$data['date']}')";

		$result = self::connection()->query($query);
		return $result;
	}

	public function getCorreo()
	{
		$query = "SELECT c.correo_id AS 'correo_id', c.server AS 'server', c.correo AS 'correo', c.port AS 'port', c.smtp_secure AS 'smtp_secure', c.estado AS 'estado', ct.nombre AS 'tipo_correo'
				FROM correo AS c
				INNER JOIN correo_tipo AS ct
				ON c.correo_tipo_id = ct.correo_tipo_id
				WHERE c.estado = 1
				ORDER BY c.correo_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDiarios()
	{
		$query = "SELECT d.diarios_id AS 'diarios_id', d.nombre AS 'diario', d.cuentas_id AS 'cuentas_id', c.nombre AS 'cuenta', d.estado AS 'estado'
				FROM diarios AS d
				INNER JOIN cuentas AS c
				ON d.cuentas_id = c.cuentas_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDiariosEdit($diarios_id)
	{
		$query = "SELECT d.diarios_id AS 'diarios_id', d.nombre AS 'diario', d.cuentas_id AS 'cuentas_id', c.nombre AS 'cuenta', d.estado AS 'estado'
				FROM diarios AS d
				INNER JOIN cuentas AS c
				ON d.cuentas_id = c.cuentas_id
				WHERE diarios_id = '$diarios_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAlmacen($datos)
	{
		$estado = $datos["estado"] ?? 1;

		if ($datos['privilegio_colaborador'] === 'Super Administrador' && $datos['privilegio_colaborador'] === 'Administrador') {
			$where = "WHERE a.estado = '$estado'";
		} else {
			$where = "WHERE a.estado = '$estado'  AND a.empresa_id = '" . $datos['empresa_id'] . "'";
		}

		$query = "SELECT a.almacen_id AS 'almacen_id', a.nombre AS 'almacen', u.nombre AS 'ubicacion', e.nombre AS 'empresa', a.estado,
				a.facturar_cero
				FROM almacen AS a
				INNER JOIN ubicacion AS u
				ON a.ubicacion_id = u.ubicacion_id
				INNER JOIN empresa AS e
				ON a.empresa_id = e.empresa_id
				" . $where . '
				ORDER BY a.nombre ASC';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoPagoContabilidad($estado)
	{
		$query = "SELECT tp.nombre AS 'nombre', c.codigo AS 'codigo', c.nombre AS 'cuenta', tp.tipo_pago_id AS 'tipo_pago_id', tp.estado
				FROM tipo_pago AS tp
				INNER JOIN cuentas As c
				ON tp.cuentas_id = c.cuentas_id
				WHERE tp.estado = '$estado'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getUbicacion($datos)
	{
		$estado = $datos['estado'] ?? 1;

		if ($datos['privilegio_colaborador'] === 'Super Administrador' && $datos['privilegio_colaborador'] === 'Administrador') {
			$where = "WHERE u.estado = '$estado'";
		} else {
			$where = "WHERE u.estado = '$estado' AND u.empresa_id = '" . $datos['empresa_id'] . "'";
		}

		$query = "SELECT u.ubicacion_id AS 'ubicacion_id', u.nombre AS 'ubicacion', e.nombre AS 'empresa', u.estado
				FROM ubicacion AS u
				INNER JOIN empresa AS e
				ON u.empresa_id = e.empresa_id
				" . $where . '
				ORDER BY u.nombre ASC';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getUbicacionSelect($datos)
	{
		if ($datos['privilegio_colaborador'] === 'Super Administrador' && $datos['privilegio_colaborador'] === 'Administrador') {
			$where = 'WHERE u.estado = 1';
		} else {
			$where = "WHERE u.estado = 1 AND u.empresa_id = '" . $datos['empresa_id'] . "'";
		}

		$query = "SELECT u.ubicacion_id AS 'ubicacion_id', u.nombre AS 'ubicacion', e.nombre AS 'empresa'
				FROM ubicacion AS u
				INNER JOIN empresa AS e
				ON u.empresa_id = e.empresa_id
				" . $where . '
				ORDER BY u.nombre ASC';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoProductos()
	{
		$query = 'SELECT *

				FROM tipo_producto';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoCorreo()
	{
		$query = 'SELECT *

				FROM correo_tipo';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoProductosMovimientos()
	{
		$query = "SELECT *
				FROM tipo_producto
				WHERE nombre NOT IN ('Servicio')";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentasContabilidad($estado)
	{
		$query = "SELECT *
				FROM cuentas
				WHERE estado = '$estado'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentasIngresos($datos)
	{
		$query = "SELECT sum(total) AS 'ingresos'
				FROM ingresos
				WHERE cuentas_id = '" . $datos['cuentas_id'] . "' AND CAST(fecha_registro AS DATE) BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentaEgresos($datos)
	{
		$query = "SELECT sum(total) AS 'egresos'
				FROM egresos
				WHERE cuentas_id = '" . $datos['cuentas_id'] . "' AND CAST(fecha_registro AS DATE) BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMovimientosCuentasContables($datos)
	{
		$query = "SELECT mc.movimientos_cuentas_id AS 'movimientos_cuentas_id', mc.fecha_registro AS 'fecha', c.codigo as 'codigo', c.nombre AS 'nombre', mc.ingreso As 'ingreso', mc.egreso AS 'egreso', mc.saldo AS 'saldo'
				FROM movimientos_cuentas AS mc
				INNER JOIN cuentas AS c
				ON mc.cuentas_id = c.cuentas_id
				AND fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'
				ORDER BY mc.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getIngresosContables($datos)
	{
		$query = "SELECT
					i.ingresos_id,
					i.fecha,
					c.codigo,
					c.nombre,
					cli.nombre AS cliente,
					i.factura,
					i.subtotal,
					i.impuesto,
					i.descuento,
					i.recibide,
					COALESCE(cli.nombre, i.recibide) AS cliente,
					i.nc,
					i.total,
					i.fecha_registro,
					i.observacion,
					CASE i.tipo_ingreso
						WHEN 1 THEN 'Ingresos por Ventas'
						WHEN 2 THEN 'Ingresos Manuales'
						ELSE 'Otro'
					END AS tipo_ingreso,
					i.estado
				FROM
					ingresos AS i
				INNER JOIN
					cuentas AS c ON i.cuentas_id = c.cuentas_id
				LEFT JOIN
					clientes AS cli ON i.clientes_id = cli.clientes_id
				WHERE 
					CAST(i.fecha_registro AS DATE) BETWEEN '".$datos['fechai']."' AND '".$datos['fechaf']."' 
					AND i.estado = ".$datos['estado']."
				ORDER BY i.fecha_registro DESC";

		$result = self::connection()->query($query);

		if(!$result) {
			// Registrar error si la consulta falla
			error_log("Error en getIngresosContables: ".self::connection()->error);
			return false;
		}

		return $result;
	}

	public function ejecutar_consulta_simple($query)
	{
		$result = self::connection()->query($query);

		return $result;
	}

	public function consulta_total_gastos($query)
	{
		$result = self::connection()->query($query);

		return $result;
	}

	public function getReporteCategoriaGastos($datos)
	{
		$query = "SELECT cg.nombre AS 'categoria', SUM(e.total) As 'monto'
				FROM egresos AS e
				INNER JOIN categoria_gastos AS cg
				ON e.categoria_gastos_id = cg.categoria_gastos_id
				WHERE CAST(e.fecha_registro AS DATE) BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND e.estado = 1
				GROUP BY e.categoria_gastos_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEgresosContables($datos)
	{
		$query = "SELECT e.egresos_id AS 'egresos_id', e.fecha AS 'fecha', c.codigo as 'codigo', c.nombre AS 'nombre', p.nombre AS 'proveedor', e.factura AS 'factura', e.subtotal as 'subtotal', e.impuesto AS 'impuesto', e.descuento AS 'descuento', e.nc AS 'nc', e.total AS 'total', e.fecha_registro As 'fecha_registro', cg.nombre AS 'categoria', e.observacion, e.estado
				FROM egresos AS e
				INNER JOIN cuentas AS c
				ON e.cuentas_id = c.cuentas_id
				INNER JOIN proveedores AS p
				ON e.proveedores_id = p.proveedores_id
				LEFT
				 JOIN categoria_gastos AS cg
				ON e.categoria_gastos_id = cg.categoria_gastos_id
				WHERE CAST(e.fecha_registro AS DATE) BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'
				AND e.estado = '" . $datos['estado'] . "'
				ORDER BY e.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEgresosContablesReporte($egresos_id)
	{
		$query = "SELECT e.egresos_id AS 'egresos_id', e.fecha AS 'fecha', c.codigo as 'codigo', c.nombre AS 'nombre', p.nombre AS 'proveedor', p.rtn AS 'rtn_proveedor', p.localidad AS 'localidad', p.telefono AS 'telefono', e.factura AS 'factura', e.fecha_registro As 'fecha_registro', emp.nombre AS 'empresa', emp.ubicacion AS 'direccion_empresa', emp.telefono AS 'empresa_telefono', emp.celular AS 'empresa_celular', emp.correo AS 'empresa_correo', emp.otra_informacion As 'otra_informacion', emp.eslogan AS 'eslogan', DATE_FORMAT(e.fecha, '%d/%m/%Y') AS 'fecha', time(e.fecha_registro) AS 'hora', e.observacion AS 'observacion', co.nombre AS 'colaborador_nombre', e.estado AS 'estado', emp.rtn AS 'rtn_empresa', e.subtotal AS 'subtotal', e.descuento AS 'descuento', e.nc AS 'nc', e.impuesto AS 'impuesto', e.total AS 'total', DATE_FORMAT(e.fecha_registro, '%d/%m/%Y') AS 'fecha_registro_consulta', emp.logotipo AS 'logotipo', emp.firma_documento AS 'firma_documento', cg.nombre AS 'categoria'
				FROM egresos AS e
				INNER JOIN cuentas AS c
				ON e.cuentas_id = c.cuentas_id
				INNER JOIN proveedores AS p
				ON e.proveedores_id = p.proveedores_id
				INNER JOIN empresa AS emp
				ON e.empresa_id = emp.empresa_id
				INNER JOIN colaboradores AS co
				ON e.colaboradores_id = co.colaboradores_id
				LEFT JOIN categoria_gastos AS cg
				ON e.categoria_gastos_id = cg.categoria_gastos_id\t\t\t\t
				WHERE e.egresos_id = '$egresos_id'
				ORDER BY e.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getIngresosContablesReporte($ingresos_id)
	{
		$query = "SELECT i.ingresos_id AS 'ingresos_id', i.fecha AS 'fecha', c.codigo as 'codigo', c.nombre AS 'nombre', cl.nombre AS 'cliente', cl.rtn AS 'rtn_cliente', cl.localidad AS 'localidad', cl.telefono AS 'telefono', i.factura AS 'factura', i.fecha_registro As 'fecha_registro', emp.nombre AS 'empresa', emp.ubicacion AS 'direccion_empresa', emp.telefono AS 'empresa_telefono', emp.celular AS 'empresa_celular', emp.correo AS 'empresa_correo', emp.otra_informacion As 'otra_informacion', emp.eslogan AS 'eslogan', DATE_FORMAT(i.fecha, '%d/%m/%Y') AS 'fecha', time(i.fecha_registro) AS 'hora', i.observacion AS 'observacion', co.nombre AS 'colaborador_nombre', i.estado AS 'estado', emp.rtn AS 'rtn_empresa', i.subtotal AS 'subtotal', i.descuento AS 'descuento', i.nc AS 'nc', i.impuesto AS 'impuesto', i.total AS 'total', DATE_FORMAT(i.fecha_registro, '%d/%m/%Y') AS 'fecha_registro_consulta', emp.logotipo AS 'logotipo', emp.firma_documento AS 'firma_documento'
				FROM ingresos AS i
				INNER JOIN cuentas AS c
				ON i.cuentas_id = c.cuentas_id
				INNER JOIN clientes AS cl
				ON i.clientes_id = cl.clientes_id
				INNER JOIN empresa AS emp
				ON i.empresa_id = emp.empresa_id
				INNER JOIN colaboradores AS co
				ON i.colaboradores_id = co.colaboradores_id
				WHERE i.ingresos_id = '$ingresos_id'
				ORDER BY i.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getChequesContables($datos)
	{
		$query = "SELECT ck.fecha AS 'fecha', ck.factura AS 'factura', ck.importe AS 'importe', c.codigo AS 'codigo', c.nombre AS 'nombre', ck.observacion AS 'observacion', p.nombre AS 'proveedor'

				FROM cheque AS ck

				INNER JOIN cuentas AS c

				ON ck.cuentas_id = c.cuentas_id

				INNER JOIN proveedores AS p

				ON ck.proveedores_id = p.proveedores_id

				WHERE ck.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'

				ORDER BY ck.fecha_registro DESC";

		$result = self::connection()->query($query);

		return $result;
	}

	/* INICIO FUNCIONES ACCIONES CONSULTAS EDITAR FORMULARIOS */

	public function getCuentasContabilidadEdit($cuentas_id)
	{
		$query = "SELECT *

				FROM cuentas

				WHERE cuentas_id = '$cuentas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getClientesEdit($clientes_id)
	{
		$query = "SELECT *

				FROM clientes

				WHERE clientes_id = '$clientes_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getProveedoresEdit($proveedores_id)
	{
		$query = "SELECT *
				FROM proveedores
				WHERE proveedores_id = '$proveedores_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getColaboradoresEdit($colaboradores_id)
	{
		$query = "SELECT *
				FROM colaboradores
				WHERE colaboradores_id = '$colaboradores_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPuestosEdit($puestos_id)
	{
		$query = "SELECT *
				FROM puestos
				WHERE puestos_id = '$puestos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDestinatarios()
	{
		$query = 'SELECT notificaciones_id, correo, nombre
				FROM notificaciones';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getUsersEdit($users_id)
	{
		$query = "SELECT u.users_id AS 'users_id', c.colaboradores_id AS 'colaborador_id', c.nombre AS 'colaborador', u.email AS 'correo', u.tipo_user_id AS 'tipo_user_id', u.estado AS 'estado', u.empresa_id AS 'empresa_id', u.privilegio_id AS 'privilegio_id', u.server_customers_id
				FROM users AS u
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE u.users_id = '$users_id'
				ORDER BY c.nombre";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSecuenciaFacturacionEdit($secuencia_facturacion_id)
	{
		$query = "SELECT *
				FROM secuencia_facturacion
				WHERE secuencia_facturacion_id = '$secuencia_facturacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getFactura($noFactura)
	{
		$query = "SELECT c.clientes_id As 'clientes_id', c.nombre AS 'cliente', c.rtn AS 'rtn_cliente', c.telefono AS 'telefono', c.localidad AS 'localidad', e.nombre AS 'empresa', e.ubicacion AS 'direccion_empresa', e.telefono AS 'empresa_telefono', e.celular AS 'empresa_celular', e.correo AS 'empresa_correo', co.nombre AS 'colaborador_nombre', sf.prefijo AS 'prefijo', sf.siguiente AS 'numero', sf.relleno AS 'relleno', DATE_FORMAT(f.fecha, '%d/%m/%Y') AS 'fecha', time(f.fecha_registro) AS 'hora', sf.cai AS 'cai', e.rtn AS 'rtn_empresa', sf.fecha_activacion AS 'fecha_activacion', sf.fecha_limite AS 'fecha_limite', f.estado AS 'estado', sf.rango_inicial AS 'rango_inicial', sf.rango_final AS 'rango_final', f.number AS 'numero_factura', f.notas AS 'notas', e.otra_informacion As 'otra_informacion', e.eslogan AS 'eslogan', e.celular As 'celular', (CASE WHEN f.tipo_factura = 1 THEN 'Contado' ELSE 'Crédito' END) AS 'tipo_documento', e.rtn AS 'rtn', f.fecha_dolar AS 'fecha_dolar', e.logotipo AS 'logotipo', e.firma_documento AS 'firma_documento', e.MostrarFirma
				FROM facturas AS f
				INNER JOIN clientes AS c
				ON f.clientes_id = c.clientes_id
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				INNER JOIN empresa AS e
				ON sf.empresa_id = e.empresa_id
				INNER JOIN colaboradores AS co
				ON f.colaboradores_id = co.colaboradores_id
				WHERE f.facturas_id = '$noFactura'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getComprobanteCaja($apertura_id)
	{
		$query = "SELECT
					tp.nombre AS tipo_pago_nombre,
					SUM(pd.efectivo) AS total_efectivo,
					f.empresa_id
				FROM
					tipo_pago tp
				INNER JOIN
					pagos_detalles pd ON tp.tipo_pago_id = pd.tipo_pago_id
				INNER JOIN
					pagos p ON pd.pagos_id = p.pagos_id
				INNER JOIN
					facturas f ON p.facturas_id = f.facturas_id
				WHERE
					f.apertura_id = '$apertura_id'
				GROUP BY
					tp.nombre;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMetodoPagoFactura($factura_id)
	{
		$query = "SELECT
					tp.nombre AS tipo_pago_nombre,
					SUM(pd.efectivo) AS total_efectivo,
					f.empresa_id
				FROM
					tipo_pago tp
				INNER JOIN
					pagos_detalles pd ON tp.tipo_pago_id = pd.tipo_pago_id
				INNER JOIN
					pagos p ON pd.pagos_id = p.pagos_id
				INNER JOIN
					facturas f ON p.facturas_id = f.facturas_id
				WHERE
					f.facturas_id = '$factura_id'
				GROUP BY
					tp.nombre;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getConsultaFacturaProforma($facturas_id)
	{
		// Escapar el valor de $facturas_id para prevenir inyecciones SQL
		$facturas_id = self::connection()->real_escape_string($facturas_id);

		$query = "SELECT
						facturas_proforma_id
				\t  FROM
						facturas_proforma
				\t  WHERE
						facturas_id = '$facturas_id' AND estado = 0";

		$result = self::connection()->query($query);

		if (!$result) {
			die('Error en la consulta: ' . self::connection()->error);
		}

		return $result;
	}

	public function getMontoAperturaCaja($apertura_id)
	{
		$query = "SELECT
					apertura
				FROM
					apertura
				WHERE
					apertura_id = '$apertura_id';";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getFacturasCaja($apertura_id)
	{
		$query = "SELECT 
					s.prefijo, s.relleno, f.number, f.importe
				FROM 
					facturas AS f
				INNER JOIN 
					secuencia_facturacion AS s
				ON f.secuencia_facturacion_id  = s.secuencia_facturacion_id\t
				WHERE 
					f.apertura_id = '$apertura_id';";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAcciones($acciones)
	{
		$query = "SELECT 
				activar
			FROM 
				config
			WHERE 
				accion = '$acciones';";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCotizacion($noCotizacion)
	{
		$query = "SELECT cl.nombre AS 'cliente', cl.rtn AS 'rtn_cliente', cl.telefono AS 'telefono', cl.localidad AS 'localidad',
		\t e.nombre AS 'empresa', e.ubicacion AS 'direccion_empresa', e.telefono AS 'empresa_telefono', e.celular AS 'empresa_celular',
		\t  e.correo AS 'empresa_correo', co.nombre AS 'colaborador_nombre',
		\t   DATE_FORMAT(c.fecha, '%d/%m/%Y') AS 'fecha', c.fecha_dolar,
		\t    time(c.fecha_registro) AS 'hora',  c.estado AS 'estado', c.number AS 'numero_factura', c.notas AS 'notas', e.otra_informacion As 'otra_informacion', e.eslogan AS 'eslogan', e.celular As 'celular', (CASE WHEN c.tipo_factura = 1 THEN 'Contado' ELSE 'Crédito'END) AS 'tipo_documento', vg.valor AS 'vigencia_cotizacion', e.rtn AS 'rtn_empresa', e.logotipo AS 'logotipo', e.firma_documento AS 'firma_documento', e.MostrarFirma
				FROM cotizacion AS c
				INNER JOIN clientes AS cl
				ON c.clientes_id = cl.clientes_id
				INNER JOIN colaboradores AS co
				ON c.colaboradores_id = co.colaboradores_id
				INNER JOIN empresa AS e
				ON co.empresa_id = e.empresa_id
				INNER JOIN vigencia_cotizacion AS vg
				ON c.vigencia_cotizacion_id = vg.vigencia_cotizacion_id
				WHERE c.cotizacion_id = '$noCotizacion'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCompra($noCompra)
	{
		$query = "SELECT p.nombre AS 'proveedor', p.rtn AS 'rtn_proveedor', p.telefono AS 'telefono', p.localidad AS 'localidad', e.nombre AS 'empresa', e.ubicacion AS 'direccion_empresa', e.telefono AS 'empresa_telefono', e.celular AS 'empresa_celular', e.correo AS 'empresa_correo', co.nombre AS 'colaborador_nombre', DATE_FORMAT(c.fecha, '%d/%m/%Y') AS 'fecha', time(c.fecha_registro) AS 'hora',  c.estado AS 'estado', c.number AS 'numero_factura', c.notas AS 'notas', e.otra_informacion As 'otra_informacion', e.eslogan AS 'eslogan', e.celular As 'celular', (CASE WHEN c.tipo_compra = 1 THEN 'Contado' ELSE 'Crédito' END) AS 'tipo_documento', e.rtn AS 'rtn_empresa', c.proveedores_id AS 'proveedores_id', e.logotipo AS 'logotipo', e.firma_documento AS 'firma_documento'
				FROM compras AS c
				INNER JOIN proveedores AS p
				ON c.proveedores_id = p.proveedores_id
				INNER JOIN colaboradores AS co
				ON c.colaboradores_id = co.colaboradores_id
				INNER JOIN empresa AS e
				ON co.empresa_id = e.empresa_id
				WHERE c.compras_id = '$noCompra'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDetalleFactura($noFactura)
	{
		$query = "SELECT
				p.barCode AS 'barCode',
				p.nombre AS 'producto',
				p.precio_compra AS costo,
				p.precio_venta AS precio_venta,
				p.cantidad_mayoreo AS cantidad_mayoreo,
				p.precio_mayoreo AS precio_mayoreo,
				p.isv_venta AS 'isv_venta',
				p.almacen_id AS 'almacen_id',
				p.medida_id AS 'medida_id',
				fd.facturas_detalle_id,
				SUM(fd.cantidad) AS 'cantidad',
				fd.precio AS 'precio',
				SUM(fd.descuento) AS 'descuento',
				fd.productos_id AS 'productos_id',
				SUM(fd.isv_valor) AS 'isv_valor',
				med.nombre As 'medida'
			FROM
				facturas_detalles AS fd
			INNER JOIN productos AS p ON fd.productos_id = p.productos_id
			INNER JOIN medida as med ON p.medida_id = med.medida_id
			WHERE fd.facturas_id = '$noFactura'
			GROUP BY fd.productos_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpresaFacturaCorreo($usuario)
	{
		$query = "SELECT e.telefono AS 'telefono', e.celular AS 'celular', e.correo AS 'correo', e.horario AS 'horario', e.eslogan AS 'eslogan', e.facebook AS 'facebook', e.sitioweb AS 'sitioweb'
			FROM users AS u
			INNER JOIN empresa AS e
			ON u.empresa_id = e.empresa_id
			WHERE u.colaboradores_id = '$usuario'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpresaFacturaCorreoUsuario($users_id)
	{
		$query = "SELECT e.telefono AS 'telefono', e.celular AS 'celular', e.correo AS 'correo', e.horario AS 'horario', e.eslogan AS 'eslogan', e.facebook AS 'facebook', e.sitioweb AS 'sitioweb'
			FROM users AS u
			INNER JOIN empresa AS e
			ON u.empresa_id = e.empresa_id
			WHERE u.users_id = '$users_id'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function geFacturaCorreo($facturas_id)
	{
		$query = "SELECT c.nombre AS 'cliente', c.correo AS 'correo', f.number AS 'numero', sf.prefijo AS 'prefijo', sf.relleno AS 'relleno'
			FROM facturas AS f
			INNER JOIN clientes AS c
			ON f.clientes_id = c.clientes_id
			INNER JOIN secuencia_facturacion AS sf
			ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
			WHERE f.facturas_id = '$facturas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCotizacionCorreo($cotizacion_id)
	{
		$query = "SELECT cl.nombre AS 'cliente', cl.correo AS 'correo', c.number AS 'numero'
			FROM cotizacion AS c
			INNER JOIN clientes AS cl
			ON c.clientes_id = cl.clientes_id
			WHERE c.cotizacion_id = '$cotizacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCorreoServer($correo_tipo_id)
	{
		$query = "SELECT c.correo_id AS 'correo_id', c.correo_tipo_id AS 'correo_tipo_id', ct.nombre AS 'tipo_correo', c.server AS 'server', c.correo AS 'correo', c.port AS 'port', c.smtp_secure AS 'smtp_secure', c.estado AS 'estado', c.password AS 'password'
			FROM correo AS c
			INNER JOIN correo_tipo AS ct
			ON c.correo_tipo_id = ct.correo_tipo_id
			WHERE ct.nombre = '$correo_tipo_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNumeroFactura($facturas_id)
	{
		$query = "SELECT f.number AS 'numero', sf.prefijo AS 'prefijo', sf.relleno AS 'relleno'
				FROM facturas AS f
				INNER JOIN clientes AS c
				ON f.clientes_id = c.clientes_id
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				WHERE f.facturas_id = '$facturas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNumeroCompra($compras_id)
	{
		$query = "SELECT c.number AS 'numero'
				FROM compras AS c
				INNER JOIN proveedores AS p
				ON c.proveedores_id = p.proveedores_id
				WHERE c.compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNombreCliente($clientes_id)
	{
		$query = "SELECT nombre
			FROM clientes
			WHERE clientes_id = '$clientes_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNombreClienteLike($clientes_id)
	{
		$query = "SELECT nombre
			FROM clientes
			WHERE nombre LIKE '%$clientes_id%'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSaldoPorLote($productos_id, $lote_id)
	{
		// Obtenemos la conexión a la base de datos
		$conexion = mainModel::connection();

		// Consulta SQL para obtener el saldo del producto en el lote específico
		$query = "SELECT saldo 
				FROM movimientos 
				WHERE productos_id = ? AND lote_id = ? 
				ORDER BY fecha_registro DESC LIMIT 1"; // FIFO (First In, First Out)

		// Preparamos la consulta
		$stmt = $conexion->prepare($query);
		
		// Vinculamos los parámetros (producto_id y lote_id)
		$stmt->bind_param("ii", $producto_id, $lote_id);
		
		// Ejecutamos la consulta
		$stmt->execute();
		
		// Obtenemos el resultado
		$result = $stmt->get_result();
		
		// Verificamos si existe un saldo para este producto en el lote especificado
		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			return $row['saldo']; // Devolvemos el saldo
		} else {
			return 0; // Si no se encuentra el saldo, devolvemos 0 en lugar de null
		}
	}

	function getProductosLike($searchText)
	{
		$query = "
			SELECT p.productos_id, p.nombre, p.barCode, p.tipo_producto_id				   
			FROM productos p
			WHERE p.barCode LIKE ? OR p.nombre LIKE ?
			GROUP BY p.productos_id
			LIMIT 10";
		
		$stmt = self::connection()->prepare($query);
		$stmt->bind_param("ss", $param1, $param2);  // Solo se usan los parámetros de búsqueda
		
		// Prepara los parámetros de búsqueda
		$param1 = "%$searchText%";
		$param2 = "%$searchText%";
		
		// Ejecuta la consulta
		$stmt->execute();
		$result = $stmt->get_result();
		
		return $result;
	}
	
	public function getNombreClienteFactura($factura_id)
	{
		$query = "SELECT c.nombre 'nombre'
			FROM facturas AS f
			INNER JOIN clientes AS c
			ON f.clientes_id = c.clientes_id
			WHERE f.facturas_id = '$factura_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getNombreClienteFacturaCompras($compras_id)
	{
		$query = "SELECT p.nombre AS 'nombre'
			FROM compras AS c
			INNER JOIN proveedores AS p
			ON c.proveedores_id = p.proveedores_id
			WHERE c.compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getImporteCompras($compras_id)
	{
		$query = "SELECT importe
			FROM compras
			WHERE compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getImporteFacturas($facturas_id)
	{
		$query = "SELECT importe
			FROM facturas
			WHERE facturas_id = '$facturas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getRTNCliente($clientes_id, $rtn)
	{
		$query = "SELECT rtn
			FROM clientes
			WHERE rtn = '$rtn'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function actualizarRTNCliente($clientes_id, $rtn)
	{
		$update = " UPDATE clientes
				SET rtn = '$rtn'
				WHERE clientes_id = '$clientes_id'";

		$result = self::connection()->query($update);
		return $result;
	}

	public function getBarCode($productos_id, $barcode)
	{
		$query = "SELECT productos_id
			FROM productos
			WHERE barCode = '$barcode'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function actualizarBarCode($productos_id, $barcode)
	{
		$update = " UPDATE productos
				SET barCode = '$barcode'
				WHERE productos_id = '$productos_id'";

		$result = self::connection()->query($update);
		return $result;
	}

	public function getNombreProveedor($proveedores_id)
	{
		$query = "SELECT nombre
			FROM proveedores
			WHERE proveedores_id = '$proveedores_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getRTNProveedor($proveedores_id, $rtn)
	{
		$query = "SELECT rtn
			FROM proveedores
			WHERE rtn = '$rtn'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function actualizarRTNProveedor($proveedores_id, $rtn)
	{
		$update = " UPDATE proveedores
				SET rtn = '$rtn'
				WHERE proveedores_id = '$proveedores_id'";

		$result = self::connection()->query($update);
		return $result;
	}

	public function getNumeroCotizacion($cotizacion_id)
	{
		$query = "SELECT c.number AS 'numero', cl.nombre AS 'cliente'
			FROM cotizacion AS c
			INNER JOIN clientes AS cl
			ON c.clientes_id = cl.clientes_id
			WHERE c.cotizacion_id = '$cotizacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	function rellenarDigitos($valor, $long)
	{
		$numero = str_pad($valor, $long, '0', STR_PAD_LEFT);

		return $numero;
	}

	function getMontoTipoPago($apertura_id)
	{
		$query = "SELECT tp.cuentas_id AS 'cuentas_id', tp.nombre AS 'tipo_pago', SUM(pd.efectivo) AS 'monto'
				FROM facturas AS f
				INNER JOIN pagos AS p
				ON f.facturas_id = p.facturas_id
				INNER JOIN pagos_detalles AS pd
				ON p.pagos_id = pd.pagos_id
				INNER JOIN tipo_pago AS tp
				ON pd.tipo_pago_id = tp.tipo_pago_id
				WHERE f.apertura_id = '$apertura_id'
				GROUP BY tp.cuentas_id";
		$result = self::connection()->query($query);
		return $result;
	}

	function getMontoTipoPagoCompras($compras_id)
	{
		$query = "SELECT tp.tipo_pago_id As 'tipo_pago_id', SUM(cd.isv_valor) AS 'isv_valor', SUM(cd.descuento) AS 'descuento', SUM(pd.efectivo) AS 'monto', tp.cuentas_id As 'cuentas_id'
				FROM compras AS c
				INNER JOIN compras_detalles AS cd
				ON c.compras_id = cd.compras_detalles_id
				INNER JOIN pagoscompras AS p
				ON c.compras_id = p.compras_id
				INNER JOIN pagoscompras_detalles AS pd
				ON p.pagoscompras_id = pd.pagoscompras_id
				INNER JOIN tipo_pago As tp
				ON pd.tipo_pago_id = tp.tipo_pago_id
				WHERE c.compras_id = '$compras_id'
				GROUP BY tp.cuentas_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDetalleCompra($noFactura)
	{
		$query = "SELECT p.nombre AS 'producto', cd.cantidad As 'cantidad', cd.precio AS 'precio', cd.descuento AS 'descuento', cd.productos_id  AS 'productos_id', cd.isv_valor AS 'isv_valor'
				FROM compras_detalles AS cd
				INNER JOIN productos AS p
				ON cd.productos_id = p.productos_id
				WHERE cd.compras_id = '$noFactura'
				GROUP BY cd.productos_id";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDetalleCotizaciones($noCotizacion)
	{
		$query = "SELECT 
				p.barCode AS 'barCode', 
				p.nombre AS 'producto',
				SUM(cd.cantidad) AS 'cantidad',
				cd.precio AS 'precio',
				SUM(cd.descuento) AS 'descuento',
				cd.productos_id AS 'productos_id',
				SUM(cd.isv_valor) AS 'isv_valor',
				med.nombre AS 'medida'
			FROM 
				cotizacion_detalles AS cd
			INNER JOIN 
				productos AS p ON cd.productos_id = p.productos_id
			INNER JOIN 
				medida as med ON p.medida_id = med.medida_id
			WHERE 
				cd.cotizacion_id = '$noCotizacion'
			GROUP BY 
				cd.productos_id
			";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDetalleCompras($compras_id)
	{
		$query = "SELECT
			p.nombre AS 'producto',
			cd.cantidad AS 'cantidad',
			cd.precio AS 'precio',
			cd.descuento AS 'descuento',
			cd.productos_id AS 'productos_id',
			cd.isv_valor AS 'isv_valor',
			med.nombre AS 'medida'
			FROM
				compras_detalles AS cd
			INNER JOIN productos AS p ON cd.productos_id = p.productos_id
			INNER JOIN medida as med ON p.medida_id = med.medida_id
			WHERE cd.compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEmpresasEdit($empresa_id)
	{
		$query = "SELECT *
				FROM empresa
				WHERE empresa_id = '$empresa_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPrivilegiosEdit($privilegio_id)
	{
		$query = "SELECT *
				FROM privilegio
				WHERE privilegio_id = '$privilegio_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoUsuariosAcceso($privilegio_id)
	{
		$query = "SELECT *
				FROM permisos
				WHERE tipo_user_id = '$privilegio_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPrivilegiosAccesoMenu($privilegio_id)
	{
		$query = "SELECT am.acceso_menu_id AS 'acceso_menu_id ', m.name AS 'menu', am.estado AS 'estado'
				FROM acceso_menu am
				INNER JOIN menu AS m
				ON am.menu_id = m.menu_id
				WHERE am.privilegio_id = '$privilegio_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPrivilegiosAccesoSubMenu($privilegio_id)
	{
		$query = "SELECT asm.acceso_submenu_id AS 'acceso_menu_id ', sm.name AS 'submenu', asm.estado AS 'estado'
				FROM acceso_submenu asm
				INNER JOIN submenu AS sm
				ON asm.submenu_id = sm.submenu_id
				WHERE asm.privilegio_id = '$privilegio_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPrivilegiosAccesoSubMenu1($privilegio_id)
	{
		$query = "SELECT asm.acceso_submenu1_id AS 'acceso_menu_id ', sm.name AS 'submenu1', asm.estado AS 'estado', asm.privilegio_id
				FROM acceso_submenu1 asm
				INNER JOIN submenu1 AS sm
				ON asm.submenu1_id = sm.submenu1_id
				WHERE asm.privilegio_id = '$privilegio_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function consultar_usuario($colaborador_id, $contraseña_anterior)
	{
		$query = "SELECT *

			FROM users

			WHERE colaboradores_id = '$colaborador_id' AND password = '$contraseña_anterior'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCajasEdit($apertura_id)
	{
		$query = "SELECT a.fecha AS 'fecha', a.factura_inicial AS 'factura_inicial', a.factura_final AS 'factura_final', a.apertura AS 'monto_apertura', (CASE WHEN a.estado = '1' THEN 'Activa' ELSE 'Inactiva' END) AS 'caja', c.nombre AS 'usuario', a.colaboradores_id AS 'colaboradores_id', a.apertura_id AS 'apertura_id'

				FROM apertura AS a

				INNER JOIN colaboradores AS c

				ON a.colaboradores_id = c.colaboradores_id

				WHERE a.apertura_id = '$apertura_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoUsuarioEdit($tipo_user_id)
	{
		$query = "SELECT *

				FROM tipo_user

				WHERE tipo_user_id = '$tipo_user_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoProductosEdit($productos_id)
	{
		$query = "SELECT p.*, tp.nombre AS 'tipo_producto'

				FROM productos AS p

				INNER JOIN tipo_producto AS tp

				ON p.tipo_producto_id = tp.tipo_producto_id

				WHERE productos_id = '$productos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getUbicacionEdit($ubicacion_id)
	{
		$query = "SELECT *
				FROM ubicacion
				WHERE ubicacion_id = '$ubicacion_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMedidaEdit($medida_id)
	{
		$query = "SELECT *

				FROM medida

				WHERE medida_id = '$medida_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCategoriaProductoEdit($categoria_id)
	{
		$query = "SELECT *

				FROM categoria

				WHERE categoria_id = '$categoria_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAlmacenEdit($almacen_id)
	{
		$query = "SELECT *

				FROM almacen

				WHERE almacen_id = '$almacen_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTotalFacturasDisponiblesDB($empresa_id)
	{
		$query = "SELECT siguiente AS 'numero'
				FROM secuencia_facturacion
				WHERE activo = 1 AND empresa_id = '$empresa_id' AND documento_id = 1
				ORDER BY siguiente DESC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	// Obtiene el último número de factura usado realmente en la base de datos
    public function getUltimoNumeroFacturaUsado($empresa_id) {
        $query = "SELECT MAX(CAST(numero AS UNSIGNED)) as numero 
                  FROM facturas 
                  WHERE empresa_id = ? AND estado = 1";
        $stmt = $this->connection()->prepare($query);
        $stmt->bind_param("i", $empresa_id);
        $stmt->execute();
        return $stmt->get_result();
    }

	public function getNumeroMaximoPermitido($empresa_id) {
		$query = "SELECT rango_final AS 'numero', rango_inicial, rango_final
				  FROM secuencia_facturacion
				  WHERE activo = 1 
					AND empresa_id = '$empresa_id' 
					AND documento_id = 1
				  LIMIT 1";
	
		$result = self::connection()->query($query);
		return $result;
	}

	public function getFechaLimiteFactura($empresa_id)
	{
		$query = "SELECT DATEDIFF(fecha_limite, NOW()) AS 'dias_transcurridos', fecha_limite AS 'fecha_limite'

				FROM secuencia_facturacion

				WHERE activo = 1 AND empresa_id = '$empresa_id' AND documento_id = 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCorreoEdit($correo_id)
	{
		$query = "SELECT c.correo_id AS 'correo_id', c.correo_tipo_id AS 'correo_tipo_id', ct.nombre AS 'tipo_correo', c.server AS 'server', c.correo AS 'correo', c.port AS 'port', c.smtp_secure AS 'smtp_secure', c.estado AS 'estado', c.password AS 'password'

				FROM correo AS c

				INNER JOIN correo_tipo AS ct

				ON c.correo_tipo_id = ct.correo_tipo_id

				WHERE c.correo_id = '$correo_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoPagoEdit($tipo_pago_id)
	{
		$query = "SELECT *

				FROM tipo_pago

				WHERE tipo_pago_id = '$tipo_pago_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAsistenciaId($asistencia_id)
	{
		$query = "SELECT asistencia_id, colaboradores_id, fecha, horai, CONVERT(horaf, TIME) AS 'horaf', estado, comentario
				FROM asistencia
				WHERE asistencia_id = '$asistencia_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getBancosEdit($banco_id)
	{
		$query = "SELECT *
				FROM banco
				WHERE banco_id = '$banco_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getEgresosEdit($egresos_id)
	{
		$query = "SELECT *

				FROM egresos

				WHERE egresos_id = '$egresos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getIngresosEdit($ingresos_id)
	{
		$query = "SELECT *
				FROM ingresos
				WHERE ingresos_id = '$ingresos_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getVigenciaCotizacion()
	{
		$query = 'SELECT *
				FROM vigencia_cotizacion';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getMovimientosProductos($datos)
	{
		$producto = '';
		$cliente = '';
		$tipo = '';
		$fecha = '';
		$bodega = '';  // Asegúrate de que $bodega tenga un valor por defecto

		$fecha_actual = date('Y-m-d');

		if ($datos['fechai'] != $fecha_actual) {
			$fecha = "AND CAST(m.fecha_registro AS DATE) BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";
		}

		if ($datos['bodega'] != '') {
			$bodega = "AND a.almacen_id = '" . $datos['bodega'] . "'";
		}

		if ($datos['bodega'] == '0') {
			$bodega = '';  // Si bodega es '0', la variable debe ser vacía
		}

		if ($datos['producto'] != '') {
			$producto = "AND p.productos_id = '" . $datos['producto'] . "'";
		}

		if ($datos['cliente'] != '') {
			$cliente = "AND m.clientes_id = '" . $datos['cliente'] . "'";
		}

		if ($datos['tipo_producto_id'] != '') {
			$tipo = "AND p.tipo_producto_id = '" . $datos['tipo_producto_id'] . "'";
		}

		$query = "
		SELECT 
			m.movimientos_id,
			m.documento,
			m.cantidad_entrada,
			m.cantidad_salida,
			m.saldo,
			m.fecha_registro,
			p.nombre AS producto_nombre,
			c.nombre AS cliente,
			a.nombre AS almacen_nombre,
			p.barCode AS 'barCode',
			m.comentario,
			p.nombre AS 'producto',
			md.nombre AS 'medida',
			m.cantidad_entrada AS 'entrada',
			m.cantidad_salida AS 'salida',
			COALESCE(  -- Usamos COALESCE para asegurarnos de que cuando sea NULL, devuelva 0
				(SELECT SUM(CASE WHEN mm.cantidad_entrada > 0 THEN mm.cantidad_entrada ELSE 0 END) - 
						SUM(CASE WHEN mm.cantidad_salida > 0 THEN mm.cantidad_salida ELSE 0 END)
				FROM movimientos mm
				WHERE mm.productos_id = m.productos_id 
				AND mm.almacen_id = m.almacen_id  -- Se filtra por la bodega específica
				AND mm.fecha_registro < m.fecha_registro 
				AND mm.empresa_id = '" . $datos['empresa_id_sd'] . "' 
				$fecha
				), 
			0) AS saldo_anterior,  -- Aquí es donde usamos COALESCE para asegurar que NULL se convierta en 0
			a.nombre AS 'bodega',
			a.almacen_id,
			p.productos_id,
			p.file_name AS 'image',
			COALESCE(l.numero_lote, '') AS 'numero_lote'
		FROM 
			movimientos m
		LEFT JOIN 
			productos p ON m.productos_id = p.productos_id
		LEFT JOIN 
			clientes c ON m.clientes_id = c.clientes_id
		LEFT JOIN 
			almacen a ON m.almacen_id = a.almacen_id
		LEFT JOIN 
			medida AS md ON p.medida_id = md.medida_id
		LEFT JOIN 
			lotes l ON m.lote_id = l.lote_id
		WHERE
			p.estado = 1 
			AND m.empresa_id = '" . $datos['empresa_id_sd'] . "'
			$fecha
			$bodega
			$producto
			$cliente
			$tipo
		";        

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTranferenciaProductos($datos)
	{
		$bodega = '';
		$tipo_product = '';
		$id_producto = '';
		
		// Validación mejorada para la bodega
		$bodega = (!empty($datos['bodega']) && $datos['bodega'] != '0') ? "AND bo.almacen_id = '" . $datos['bodega'] . "'" : '';
		
		if (!empty($datos['tipo_producto_id'])) {
			$tipo_product = "AND p.tipo_producto_id = '" . $datos['tipo_producto_id'] . "'";
		}
		
		if (!empty($datos['productos_id'])) {
			$id_producto = "AND p.productos_id = '" . $datos['productos_id'] . "'";
		}
		
		$query = "
			SELECT
				m.almacen_id AS 'almacen_id',
				m.movimientos_id AS 'movimientos_id',
				m.empresa_id,
				p.barCode AS 'barCode',
				p.nombre AS 'producto',
				me.nombre AS 'medida',
				p.file_name AS 'image',
				SUM(m.cantidad_entrada) AS 'entrada',
				SUM(m.cantidad_salida) AS 'salida',
				(
					SUM(m.cantidad_entrada) - SUM(m.cantidad_salida)
				) AS 'saldo',
				bo.nombre AS 'bodega',
				DATE_FORMAT(m.fecha_registro, '%d/%m/%Y %H:%i:%s') AS 'fecha_registro',
				p.productos_id AS 'productos_id',
				p.id_producto_superior,
				COALESCE(l.numero_lote, '') AS 'numero_lote',
				COALESCE(l.lote_id, 0) AS 'lote_id',
				COALESCE((
					SELECT IFNULL(SUM(m2.cantidad_entrada) - SUM(m2.cantidad_salida), 0)
					FROM movimientos AS m2
					WHERE m2.productos_id = p.productos_id
					AND m2.almacen_id = m.almacen_id
					AND m2.fecha_registro < m.fecha_registro
					AND m2.empresa_id = '" . $datos['empresa_id_sd'] . "'
				), 0) AS 'saldo_anterior'
			FROM
				movimientos AS m
			LEFT JOIN productos AS p ON m.productos_id = p.productos_id
			LEFT JOIN medida AS me ON p.medida_id = me.medida_id
			LEFT JOIN almacen AS bo ON m.almacen_id = bo.almacen_id
			LEFT JOIN lotes l ON m.lote_id = l.lote_id
			WHERE m.empresa_id = '" . $datos['empresa_id_sd'] . "' 
			AND p.estado = 1
			$tipo_product
			$bodega
			$id_producto
			GROUP BY p.productos_id, m.almacen_id, p.nombre, me.nombre, p.file_name, bo.nombre, p.fecha_registro, p.id_producto_superior, l.numero_lote, l.lote_id
			ORDER BY p.fecha_registro ASC";
		
		$result = self::connection()->query($query);
		return $result;
	}	
		
	public function consultaVentas($datos)
	{
		$tipo_factura_reporte = '';
		$facturador = '';
		$vendedor = '';
	
		if ($datos['tipo_factura_reporte'] == 1) {
			$tipo_factura_reporte = 'AND f.estado IN(2,3)';
		}
	
		if ($datos['tipo_factura_reporte'] == 2) {
			$tipo_factura_reporte = 'AND f.estado = 4';
		}
	
		if ($datos['facturador'] != '') {
			$facturador = "AND f.usuario = '" . $datos['facturador'] . "'";
		}
	
		if ($datos['vendedor'] != '') {
			$vendedor = "AND f.colaboradores_id = '" . $datos['vendedor'] . "'";
		}
	
		$query = "
			SELECT 
				f.facturas_id AS 'facturas_id', 
				DATE_FORMAT(f.fecha, '%d/%m/%Y') AS 'fecha', 
				c.nombre AS 'cliente',
				CASE 
					WHEN d.documento_id = 4 THEN CONCAT('PROFORMA-', sf.prefijo, LPAD(f.number, sf.relleno, 0)) 
					ELSE CONCAT(sf.prefijo, '', LPAD(f.number, sf.relleno, 0))
				END AS 'numero', 
				f.importe AS 'total',
				CASE 
					WHEN f.tipo_factura = 1 THEN 'Contado' 
					ELSE 'Crédito' 
				END AS 'tipo_documento', 
				co.nombre AS 'vendedor', 
				co1.nombre, ' ' AS 'facturador',
				-- Cálculo de subtotal, ISV, costo y descuento en una sola consulta
				(SELECT SUM(fd.cantidad * fd.precio) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS 'subtotal',
				(SELECT SUM(fd.cantidad * p.precio_compra) FROM facturas_detalles AS fd INNER JOIN productos AS p ON fd.productos_id = p.productos_id WHERE fd.facturas_id = f.facturas_id) AS 'subCosto',
				(SELECT SUM(fd.isv_valor) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS 'isv',
				(SELECT SUM(fd.descuento) FROM facturas_detalles AS fd WHERE fd.facturas_id = f.facturas_id) AS 'descuento',
				-- Determinar si la factura tiene pagos pendientes
				(SELECT COUNT(*) FROM cobrar_clientes WHERE facturas_id = f.facturas_id AND estado = 2) AS 'pagos_realizados'
			FROM 
				facturas AS f
				INNER JOIN clientes AS c ON f.clientes_id = c.clientes_id
				INNER JOIN colaboradores AS co ON f.colaboradores_id = co.colaboradores_id
				INNER JOIN colaboradores AS co1 ON f.usuario = co1.colaboradores_id
				INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				INNER JOIN documento AS d ON sf.documento_id = d.documento_id
			WHERE 
				f.empresa_id = '" . $datos['empresa_id_sd'] . "' 
				AND f.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' 
				AND sf.documento_id = '" . $datos['factura'] . "' 
				$tipo_factura_reporte
				$facturador
				$vendedor
			ORDER BY 
				f.number DESC";
	
		$result = self::connection()->query($query);
		if (!$result) {
			die('Error en la consulta SQL: ' . self::connection()->error);
		}
		return $result;
	}

	public function consultaCXPagoFactura($facturas_id)
	{
		$query = "SELECT cobrar_clientes_id  FROM cobrar_clientes WHERE facturas_id = '" . $facturas_id . "' AND estado = 2";

		$result = self::connection()->query($query);
		return $result;
	}

	public function consultaCXPagoFacturaCompras($compras_id)
	{
		$query = "SELECT pagar_proveedores_id  FROM pagar_proveedores WHERE compras_id = '" . $compras_id . "' AND estado = 2";

		$result = self::connection()->query($query);
		return $result;
	}

	public function consultaImpresora()
	{
		// Base de la consulta
		$query = '
			SELECT
				*
			FROM
				`impresora`
		';
	
		// Ejecutar la consulta
		$result = self::connection()->query($query);
		return $result;
	}	

	public function consultaImpresoraFormato($formato)
	{
		// Base de la consulta
		$query = '
			SELECT
				*
			FROM
				`impresora`
			WHERE
				`estado` = 1
		';
	
		// Si se especifica un formato, se agrega el filtro
		if ($formato !== null) {
			$query .= ' AND `descripcion` LIKE "%' . self::connection()->real_escape_string($formato) . '%"';
		}
		
		// Ejecutar la consulta
		$result = self::connection()->query($query);
		return $result;
	}	

	public function getTipoDocumento()
	{
		// Obtén el valor de empresa_id de la sesión o una fuente interna
		$empresa_id = $_SESSION['empresa_id_sd'];  // Asume que se guarda en sesión, ajusta según sea necesario.

		$query = "
		SELECT
			s.documento_id
		FROM secuencia_facturacion AS s
		INNER JOIN 
			documento AS d ON s.documento_id = d.documento_id
		WHERE d.nombre = 'Factura Electronica' 
		AND s.empresa_id = $empresa_id
		AND s.activo = 1";

		// Ejecutar la consulta directamente
		$result = self::connection()->query($query);

		return $result;
	}

	public function updateImpresora($id, $estado)
	{
		$fecha_registro = date('Y-m-d H:i:s');
		$conexion = self::connection();
		$conexion->begin_transaction();

		try {
			// Si se intenta activar la impresora
			if ($estado == 1) {
				// Lista de palabras clave a verificar
				$palabrasClave = ['Factura', 'Comprobante']; // Puedes hacer esto dinámico desde una base de datos.

				// Verificar si la descripción contiene alguna palabra clave
				$queryDescripcion = "SELECT descripcion FROM impresora WHERE impresora_id = '$id'";
				$resultDescripcion = $conexion->query($queryDescripcion);

				if ($resultDescripcion->num_rows > 0) {
					$row = $resultDescripcion->fetch_assoc();

					foreach ($palabrasClave as $clave) {
						if (strpos($row['descripcion'], $clave) !== false) {
							// Desactivar todas las impresoras con la palabra clave
							$desactivarImpresoras = "UPDATE impresora 
													SET estado = 0 
													WHERE descripcion LIKE '{$clave}%'";
							$conexion->query($desactivarImpresoras);
							break; // Romper el bucle una vez que se encuentra una coincidencia
						}
					}
				}
			}

			// Actualizar el estado de la impresora seleccionada
			$update = "UPDATE impresora
					SET estado = '$estado',
						fecha_registro = '$fecha_registro'
					WHERE impresora_id = '$id'";
			$conexion->query($update);

			$conexion->commit();
			return true;
		} catch (Exception $e) {
			$conexion->rollback();
			return false;
		}
	}

	public function consultaBillDraft($datos)
	{
		$query = "SELECT f.facturas_id AS 'facturas_id', DATE_FORMAT(f.fecha, '%d/%m/%Y') AS 'fecha', c.nombre AS 'cliente', CONCAT(sf.prefijo,'',LPAD(f.number, sf.relleno, 0)) AS 'numero', FORMAT(f.importe,2) As 'total', (CASE WHEN f.tipo_factura = 1 THEN 'Contado' ELSE 'Crédito' END) AS 'tipo_documento', f.number AS 'numero_factura'
				FROM facturas AS f
				INNER JOIN clientes AS c
				ON f.clientes_id = c.clientes_id
				INNER JOIN secuencia_facturacion AS sf
				ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
				WHERE f.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND f.estado = 1 AND f.number = 0";

		$result = self::connection()->query($query);

		return $result;
	}

	public function consultaCompras($datos)
	{
		if ($datos['tipo_compra_reporte'] == 1) {
			$where = "WHERE c.empresa_id = '" . $datos['empresa_id_sd'] . "' AND c.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND c.estado IN(2,3)";
		} else {
			$where = "WHERE c.empresa_id = '" . $datos['empresa_id_sd'] . "' AND c.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND c.estado = 4";
		}

		$query = "SELECT c.compras_id AS 'compras_id', DATE_FORMAT(c.fecha, '%d/%m/%Y') AS 'fecha', p.nombre AS 'proveedor', c.number AS 'numero', c.importe As 'total', (CASE WHEN c.tipo_compra = 1 THEN 'Contado' ELSE 'Crédito' END) AS 'tipo_documento', ct.nombre AS cuenta
				FROM compras AS c
				INNER JOIN proveedores AS p
				ON c.proveedores_id = p.proveedores_id
				LEFT JOIN cuentas AS ct ON c.cuentas_id = ct.cuentas_id
				" . $where;

		$result = self::connection()->query($query);

		return $result;
	}

	public function consultaCotizaciones($datos)
	{
		if ($datos['tipo_cotizacion_reporte'] == 1) {
			$where = "WHERE c.empresa_id = '" . $datos['empresa_id_sd'] . "' AND c.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND c.estado = 1";
		} else {
			$where = "WHERE c.empresa_id = '" . $datos['empresa_id_sd'] . "' AND c.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "' AND c.estado = 2";
		}

		$query = "SELECT c.cotizacion_id AS 'cotizacion_id', DATE_FORMAT(c.fecha, '%d/%m/%Y') AS 'fecha', cl.nombre AS 'cliente', c.number AS 'numero', c.importe As 'total', (CASE WHEN c.tipo_factura = 1 THEN 'Contado' ELSE 'Crédito' END) AS 'tipo_documento'
				FROM cotizacion AS c
				INNER JOIN clientes AS cl
				ON c.clientes_id = cl.clientes_id
				" . $where . '
				ORDER BY c.cotizacion_id DESC';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getBanco($estado)
	{
		$query = "SELECT * FROM banco WHERE estado = '$estado'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getValesPendientes()
	{
		$query = "SELECT v.vale_id, c.nombre AS empleado, v.monto, v.nota, v.colaboradores_id
				FROM vale AS v
				INNER JOIN colaboradores AS c ON v.colaboradores_id = c.colaboradores_id
				WHERE v.estado = 0;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getConsultaValesEmpleado($colaboradores_id)
	{
		$query = "SELECT SUM(monto) AS monto
				FROM vale \t\t\t\t
				WHERE colaboradores_id = '$colaboradores_id' AND estado = 0;";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getImpuestos()
	{
		$query = "SELECT isv_id, valor, (CASE WHEN isv_tipo_id = 1 THEN 'Factura' ELSE 'Compra' END) AS 'tipo_isv_nombre'
				FROM isv";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getImpuestosEdit($isv_id)
	{
		$query = "SELECT isv_id, valor, (CASE WHEN isv_tipo_id = 1 THEN 'Factura' ELSE 'Compra' END) AS 'tipo_isv_nombre', isv_tipo_id

				FROM isv

				WHERE isv_id = '$isv_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTipoPago()
	{
		$query = 'SELECT * FROM tipo_pago';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDatosCompras($compras_id)
	{
		$query = "SELECT
				c.compras_id AS compras_id,
				DATE_FORMAT(c.fecha, '%d/%m/%Y') AS fecha,
				c.proveedores_id AS proveedores_id,
				p.nombre AS proveedor,
				p.rtn AS rtn,
				c.estado AS estado,
				c.fecha AS fecha_compra,
				c.notas AS notas,
				c.tipo_compra,
				pagar_proveedores.saldo
			FROM compras AS c
			INNER JOIN proveedores AS p ON c.proveedores_id = p.proveedores_id
			INNER JOIN pagar_proveedores ON pagar_proveedores.compras_id = c.compras_id
			WHERE c.compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDatosFactura($facturas_id)
	{
		$query = "SELECT
			f.facturas_id AS facturas_id,
			DATE_FORMAT(f.fecha, '%d/%m/%Y') AS fecha,
			c.clientes_id AS clientes_id,
			c.nombre AS cliente,
			c.rtn AS rtn,
			ven.nombre AS profesional,
			f.colaboradores_id AS colaborador_id,
			f.estado AS estado,
			f.fecha AS fecha_factura,
			f.notas AS notas,
			f.tipo_factura AS credito,
			cobrar_clientes.saldo
			FROM facturas AS f
			INNER JOIN clientes AS c ON f.clientes_id = c.clientes_id
			INNER JOIN colaboradores AS ven ON f.colaboradores_id = ven.colaboradores_id
			INNER JOIN cobrar_clientes ON f.facturas_id = cobrar_clientes.facturas_id
			WHERE
				f.facturas_id = '$facturas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getDetalleProductosFactura($facturas_id)
	{
		$query = "SELECT fd.productos_id AS 'productos_id', p.nombre AS 'producto', fd.cantidad AS 'cantidad', fd.precio AS 'precio', fd.isv_valor AS 'isv_valor', fd.descuento AS 'descuento'
				FROM facturas_detalles AS fd
				INNER JOIN facturas As f
				ON fd.facturas_id = f.facturas_id
				INNER JOIN productos AS p
				ON fd.productos_id = p.productos_id
				WHERE fd.facturas_id = '$facturas_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function saldo_cuentas_por_pagar($compras_id)
	{
		$query = "SELECT saldo  FROM pagar_proveedores WHERE compras_id = '" . $compras_id . "' ";

		$result = self::connection()->query($query);
		return $result;
	}

	public function getDetalleProductosCompras($compras_id)
	{
		$query = "SELECT cd.productos_id AS 'productos_id', p.nombre AS 'producto', cd.cantidad AS 'cantidad', cd.precio AS 'precio', cd.isv_valor AS 'isv_valor', cd.descuento AS 'descuento'
				FROM compras_detalles AS cd
				INNER JOIN compras As c
				ON cd.compras_id = c.compras_id
				INNER JOIN productos AS p
				ON cd.productos_id = p.productos_id
				WHERE cd.compras_id = '$compras_id'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentasporCobrarClientes($datos)
	{
		$clientes_id = '';
		$fecha_actual = date('Y-m-d');
		$fecha = '';

		if ($datos['fechai'] != $fecha_actual) {
			$fecha = "AND cc.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";
		}

		if ($datos['clientes_id'] != 0 && $datos['clientes_id'] != '') {
			$clientes_id = "AND cc.clientes_id = '" . $datos['clientes_id'] . "'";
		}

		$query = "SELECT 
					cc.cobrar_clientes_id AS 'cobrar_clientes_id', 
					f.facturas_id AS 'facturas_id', 
					c.nombre AS 'cliente',
					f.fecha AS 'fecha', 
					cc.saldo AS 'saldo', 
					CASE 
						WHEN d.documento_id = 4 THEN CONCAT('PROFORMA-', sf.prefijo, LPAD(f.number, sf.relleno, 0)) 
						ELSE CONCAT(sf.prefijo,'',LPAD(f.number, sf.relleno, 0))
					END AS 'numero', 
					cc.estado,
					f.importe, 
					co.nombre AS 'vendedor'
				FROM 
					cobrar_clientes AS cc
					INNER JOIN clientes AS c ON cc.clientes_id = c.clientes_id
					INNER JOIN facturas AS f ON cc.facturas_id = f.facturas_id
					INNER JOIN secuencia_facturacion AS sf ON f.secuencia_facturacion_id = sf.secuencia_facturacion_id
					INNER JOIN colaboradores AS co ON f.colaboradores_id = co.colaboradores_id
					INNER JOIN documento AS d ON sf.documento_id = d.documento_id\t\t
				WHERE cc.empresa_id = '" . $datos['empresa_id_sd'] . "' AND cc.estado = '" . $datos['estado'] . "'
				$fecha
				$clientes_id
				ORDER BY cc.fecha ASC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAbonosCobrarClientes($facturas_id)
	{
		$query = "SELECT SUM(importe) As 'total'
			FROM pagos
			WHERE facturas_id = '$facturas_id'";
		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentasporPagarProveedores($datos)
	{
		$proveedores_id = '';
		$fecha_actual = date('Y-m-d');
		$fecha = '';

		if ($datos['fechai'] !== $fecha_actual) {
			$fecha = "AND proveedores.fecha BETWEEN '" . $datos['fechai'] . "' AND '" . $datos['fechaf'] . "'";
		}

		if (!empty($datos['proveedores_id']) && $datos['proveedores_id'] !== 0) {
			$proveedores_id = "AND proveedores.proveedores_id = '" . $datos['proveedores_id'] . "'";
		}

		$query = "SELECT
			proveedores.nombre AS proveedores,
			compras.compras_id,
			compras.number AS factura,
			compras.importe,
			compras.fecha,
			pagar_proveedores.saldo,
			pagar_proveedores.estado
			FROM
			proveedores
			INNER JOIN compras ON proveedores.proveedores_id = compras.proveedores_id
			INNER JOIN pagar_proveedores ON pagar_proveedores.compras_id = compras.compras_id
			WHERE pagar_proveedores.estado = '" . $datos['estado'] . "'
			$fecha
			$proveedores_id
			ORDER BY proveedores.fecha ASC";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getAbonosPagarProveedores($compras_id)
	{
		$query = "SELECT SUM(importe) As 'total'
			FROM pagoscompras
			WHERE compras_id = '$compras_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getCuentasporPagarClientes()
	{
		$query = '';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getlastUpdate($entidad)
	{
		$query = 'SELECT * FROM ' . $entidad . '

				ORDER BY ' . $entidad . '_id DESC LIMIT 1';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getlastUpdateHistorialAccessos()
	{
		$query = 'SELECT * FROM historial_acceso
				ORDER BY historial_acceso_id  DESC LIMIT 1';

		$result = self::connection()->query($query);

		return $result;
	}

	protected function getMenuAccesoLoginConsulta($privilegio_id, $menu)
	{
		$query = "SELECT am.acceso_menu_id AS 'acceso_menu_id', m.name AS 'name'
				FROM acceso_menu AS am
				INNER JOIN menu AS m
				ON am.menu_id = m.menu_id
				WHERE am.privilegio_id = '$privilegio_id' AND m.name = '$menu' AND am.estado = 1";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	function getSubMenuAccesoLoginConsulta($privilegio_id, $menu)
	{
		$query = "SELECT asm.acceso_submenu_id AS 'acceso_menu_id', sm.name AS 'name'

				FROM acceso_submenu AS asm

				INNER JOIN submenu AS sm

				ON asm.submenu_id = sm.submenu_id

				WHERE asm.privilegio_id = '$privilegio_id' AND sm.name = '$menu' AND asm.estado = 1";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	function getSubMenu1AccesoLoginConsulta($privilegio_id, $menu)
	{
		$query = "SELECT asm.acceso_submenu1_id AS 'acceso_menu_id', sm.name AS 'name'

				FROM acceso_submenu1 AS asm

				INNER JOIN submenu1 AS sm

				ON asm.submenu1_id = sm.submenu1_id

				WHERE asm.privilegio_id = '$privilegio_id' AND sm.name = '$menu' AND asm.estado = 1";

		$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

		return $sql;
	}

	public function getTotalBills()
	{
		$fecha = date('Y-m-d');
		$año = date('Y', strtotime($fecha));
		$mes = date('m', strtotime($fecha));
		$dia = date('d', mktime(0, 0, 0, $mes + 1, 0, $año));
		$dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));  // PRIMER DIA DEL MES
		$dia2 = date('d', mktime(0, 0, 0, $mes, $dia, $año));  // ULTIMO DIA DEL MES
		$fecha_inicial = date('Y-m-d', strtotime($año . '-' . $mes . '-' . $dia1));
		$fecha_final = date('Y-m-d', strtotime($año . '-' . $mes . '-' . $dia2));

		$query = "SELECT SUM(importe) AS 'total'
				FROM facturas
				WHERE fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function nombremes($mes, $año = null)
	{
		// Lista de nombres de meses en español
		$meses = [
			1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 
			5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 
			9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'
		];
	
		// Si el año no se pasa como parámetro, utiliza el año actual
		$año = $año ?? date('Y');
	
		// Retorna el nombre del mes y el año especificado si el número de mes es válido
		return isset($meses[$mes]) ? $meses[$mes] . ' ' . $año : 'Mes inválido';
	}
	

	public function getTotalPurchases()
	{
		$fecha = date('Y-m-d');
		$año = date('Y', strtotime($fecha));
		$mes = date('m', strtotime($fecha));
		$dia = date('d', mktime(0, 0, 0, $mes + 1, 0, $año));
		$dia1 = date('d', mktime(0, 0, 0, $mes, 1, $año));  // PRIMER DIA DEL MES
		$dia2 = date('d', mktime(0, 0, 0, $mes, $dia, $año));  // ULTIMO DIA DEL MES
		$fecha_inicial = date('Y-m-d', strtotime($año . '-' . $mes . '-' . $dia1));
		$fecha_final = date('Y-m-d', strtotime($año . '-' . $mes . '-' . $dia2));

		$query = "SELECT sum(importe) AS 'total'
				FROM compras
				WHERE fecha BETWEEN '$fecha_inicial' AND '$fecha_final'";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTotalCustomers()
	{
		$query = "SELECT COUNT(clientes_id) AS 'total'
				FROM clientes
				WHERE estado = 1";

		$result = self::connection()->query($query);

		return $result;
	}

	public function getPlanesConsulta()
	{
		$query = 'SELECT planes_id, nombre
				FROM planes
				WHERE estado = 1';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getSistemas()
	{
		$query = 'SELECT sistema_id, nombre
				FROM sistema
				WHERE estado = 1';

		$result = self::connection()->query($query);

		return $result;
	}

	public function getTotalSuppliers()
	{
		$query = "SELECT COUNT(proveedores_id) AS 'total'
				FROM proveedores
				WHERE estado = 1";

		$result = self::connection()->query($query);

		return $result;
	}

	function getTheDay($date, $hora)
	{
		if ($date != '') {
			$curr_date = strtotime(date('Y-m-d H:i:s'));
			$the_date = strtotime($date);
			$diff = floor(($curr_date - $the_date) / (60 * 60 * 24));
			switch ($diff) {
				case 0:
					return 'Hoy ' . $hora;
				case 1:
					return 'Ayer ' . $hora;
				default:
					return ' Hace ' . $diff . ' Días';
			}
		} else {
			return 'No se encontraron actualizaciones';
		}
	}

	function getUserSistema($colaboradores_id)
	{
		$query = "SELECT colaboradores_id, nombre AS 'colaborador'
				FROM colaboradores
				WHERE colaboradores_id = '$colaboradores_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	function getConsumidorVenta()
	{
		$query = "SELECT clientes_id, nombre AS 'cliente', rtn

				FROM clientes

				ORDER BY clientes_id ASC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	function getCajero($colaborador_id_sd)
	{
		$query = "SELECT colaboradores_id AS 'colaboradores_id', nombre AS 'colaborador'
				FROM colaboradores
				WHERE colaboradores_id = '$colaborador_id_sd'";

		$result = self::connection()->query($query);

		return $result;
	}

	function getNombreUsuario($users_id)
	{
		$query = "SELECT c.nombre AS 'usuario', c.identidad AS 'identidad'
				FROM users AS u
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE u.users_id = '$users_id'";

		$result = self::connection()->query($query);

		return $result;
	}

	function getAperturaCajaUsuario($colaborador_id_sd, $fecha)
	{
		$query = "SELECT apertura_id, estado
				FROM apertura
				WHERE fecha = '$fecha' AND colaboradores_id = '$colaborador_id_sd'
				ORDER BY apertura_id DESC LIMIT 1";

		$result = self::connection()->query($query);

		return $result;
	}

	function getAlmacenId($almacen_id)
	{
		$query = "
			SELECT
				almacen.almacen_id,
				almacen.ubicacion_id,
				almacen.nombre,
				almacen.estado,
				almacen.empresa_id,
				almacen.facturar_cero,
				almacen.fecha_registro
				FROM
				almacen
				WHERE almacen_id = '$almacen_id' ";

		$result = self::connection()->query($query);

		return $result;
	}
	
	function getFacturasAnual($año)
	{
		$query = "SELECT fecha as 'fecha', SUM(importe) as 'total'
				FROM facturas
				WHERE YEAR(fecha) = '$año'
				GROUP BY MONTH(fecha)
				ORDER BY MONTH(fecha) ASC";
		$result = self::connection()->query($query);

		return $result;
	}

	function getComprasAnual($año)
	{
		$query = "SELECT fecha as 'fecha', SUM(importe) as 'total'
				FROM compras
				WHERE YEAR(fecha) = '$año'
				GROUP BY MONTH(fecha)
				ORDER BY MONTH(fecha) ASC";

		$result = self::connection()->query($query);

		return $result;
	}

	function testingMail($servidor, $correo, $contraseña, $puerto, $SMTPSecure, $CharSet)
	{
		$cabeceras = "MIME-Version: 1.0\r\n";
		$cabeceras .= "Content-type: text/html; charset=iso-8859-1\r\n";
		$cabeceras .= "From: $correo \r\n";

		// incluyo la clase phpmailer

		include_once ('phpmailer/class.phpmailer.php');
		include_once ('phpmailer/class.smtp.php');

		$mail = new PHPMailer();  // creo un objeto de tipo PHPMailer
		$mail->SMTPDebug = 1;
		$mail->IsSMTP();  // protocolo SMTP
		$mail->IsHTML(true);
		$mail->CharSet = $CharSet;
		$mail->SMTPAuth = true;  // autenticación en el SMTP
		$mail->SMTPSecure = $SMTPSecure;
		$mail->Host = $servidor;  // servidor de SMTP de gmail
		$mail->Port = $puerto;  // puerto seguro del servidor SMTP de gmail
		$mail->From = $correo;  // Remitente del correo
		$mail->FromName = $correo;  // Remitente del correo
		$mail->AddAddress($correo);  // Destinatario
		$mail->Username = $correo;  // Aqui pon tu correo
		$mail->Password = $contraseña;  // Aqui pon tu contraseña de gmail
		$mail->WordWrap = 50;  // No. de columnas

		if ($mail->SmtpConnect()) {  // enviamos el correo por PHPMailer
			echo 1;  // MENSAJE ENVIADO
		} else {
			echo 2;  // MENSAJE NO ENVIADO
		}
	}

	/* FIN FUNCIONES ACCIONES EDITAR CONSULTAS FORMULARIOS */

	/* INICIO CONVERTIR NUMEROS A LETRAS */

	function unidad($numuero)
	{
		switch ($numuero) {
			case 9:
				{
					$numu = 'NUEVE';

					break;
				}

			case 8:
				{
					$numu = 'OCHO';

					break;
				}

			case 7:
				{
					$numu = 'SIETE';

					break;
				}

			case 6:
				{
					$numu = 'SEIS';

					break;
				}

			case 5:
				{
					$numu = 'CINCO';

					break;
				}

			case 4:
				{
					$numu = 'CUATRO';

					break;
				}

			case 3:
				{
					$numu = 'TRES';

					break;
				}

			case 2:
				{
					$numu = 'DOS';

					break;
				}

			case 1:
				{
					$numu = 'UNO';

					break;
				}

			case 0:
				{
					$numu = '';

					break;
				}
		}

		return $numu;
	}

	function decena($numdero)
	{
		if ($numdero >= 90 && $numdero <= 99) {
			$numd = 'NOVENTA ';

			if ($numdero > 90)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 90));
		} else if ($numdero >= 80 && $numdero <= 89) {
			$numd = 'OCHENTA ';

			if ($numdero > 80)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 80));
		} else if ($numdero >= 70 && $numdero <= 79) {
			$numd = 'SETENTA ';

			if ($numdero > 70)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 70));
		} else if ($numdero >= 60 && $numdero <= 69) {
			$numd = 'SESENTA ';

			if ($numdero > 60)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 60));
		} else if ($numdero >= 50 && $numdero <= 59) {
			$numd = 'CINCUENTA ';

			if ($numdero > 50)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 50));
		} else if ($numdero >= 40 && $numdero <= 49) {
			$numd = 'CUARENTA ';

			if ($numdero > 40)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 40));
		} else if ($numdero >= 30 && $numdero <= 39) {
			$numd = 'TREINTA ';

			if ($numdero > 30)
				$numd = $numd . 'Y ' . (self::unidad($numdero - 30));
		} else if ($numdero >= 20 && $numdero <= 29) {
			if ($numdero == 20)
				$numd = 'VEINTE ';
			else
				$numd = 'VEINTI' . (self::unidad($numdero - 20));
		} else if ($numdero >= 10 && $numdero <= 19) {
			switch ($numdero) {
				case 10:
					{
						$numd = 'DIEZ ';

						break;
					}

				case 11:
					{
						$numd = 'ONCE ';

						break;
					}

				case 12:
					{
						$numd = 'DOCE ';

						break;
					}

				case 13:
					{
						$numd = 'TRECE ';

						break;
					}

				case 14:
					{
						$numd = 'CATORCE ';

						break;
					}

				case 15:
					{
						$numd = 'QUINCE ';

						break;
					}

				case 16:
					{
						$numd = 'DIECISEIS ';

						break;
					}

				case 17:
					{
						$numd = 'DIECISIETE ';

						break;
					}

				case 18:
					{
						$numd = 'DIECIOCHO ';

						break;
					}

				case 19:
					{
						$numd = 'DIECINUEVE ';

						break;
					}
			}
		} else
			$numd = self::unidad($numdero);

		return $numd;
	}

	function centena($numc)
	{
		if ($numc >= 100) {
			if ($numc >= 900 && $numc <= 999) {
				$numce = 'NOVECIENTOS ';

				if ($numc > 900)
					$numce = $numce . (self::decena($numc - 900));
			} else if ($numc >= 800 && $numc <= 899) {
				$numce = 'OCHOCIENTOS ';

				if ($numc > 800)
					$numce = $numce . (self::decena($numc - 800));
			} else if ($numc >= 700 && $numc <= 799) {
				$numce = 'SETECIENTOS ';

				if ($numc > 700)
					$numce = $numce . (self::decena($numc - 700));
			} else if ($numc >= 600 && $numc <= 699) {
				$numce = 'SEISCIENTOS ';

				if ($numc > 600)
					$numce = $numce . (self::decena($numc - 600));
			} else if ($numc >= 500 && $numc <= 599) {
				$numce = 'QUINIENTOS ';

				if ($numc > 500)
					$numce = $numce . (self::decena($numc - 500));
			} else if ($numc >= 400 && $numc <= 499) {
				$numce = 'CUATROCIENTOS ';

				if ($numc > 400)
					$numce = $numce . (self::decena($numc - 400));
			} else if ($numc >= 300 && $numc <= 399) {
				$numce = 'TRESCIENTOS ';

				if ($numc > 300)
					$numce = $numce . (self::decena($numc - 300));
			} else if ($numc >= 200 && $numc <= 299) {
				$numce = 'DOSCIENTOS ';

				if ($numc > 200)
					$numce = $numce . (self::decena($numc - 200));
			} else if ($numc >= 100 && $numc <= 199) {
				if ($numc == 100)
					$numce = 'CIEN ';
				else
					$numce = 'CIENTO ' . (self::decena($numc - 100));
			}
		} else
			$numce = self::decena($numc);

		return $numce;
	}

	function miles($nummero)
	{
		if ($nummero >= 1000 && $nummero < 2000) {
			$numm = 'MIL ' . (self::centena($nummero % 1000));
		}

		if ($nummero >= 2000 && $nummero < 10000) {
			$numm = self::unidad(Floor($nummero / 1000)) . ' MIL ' . (self::centena($nummero % 1000));
		}

		if ($nummero < 1000)
			$numm = self::centena($nummero);

		return $numm;
	}

	function decmiles($numdmero)
	{
		if ($numdmero == 10000)
			$numde = 'DIEZ MIL';

		if ($numdmero > 10000 && $numdmero < 20000) {
			$numde = self::decena(Floor($numdmero / 1000)) . 'MIL ' . (self::centena($numdmero % 1000));
		}

		if ($numdmero >= 20000 && $numdmero < 100000) {
			$numde = self::decena(Floor($numdmero / 1000)) . ' MIL ' . (self::miles($numdmero % 1000));
		}

		if ($numdmero < 10000)
			$numde = self::miles($numdmero);

		return $numde;
	}

	function cienmiles($numcmero)
	{
		if ($numcmero == 100000)
			$num_letracm = 'CIEN MIL';

		if ($numcmero >= 100000 && $numcmero < 1000000) {
			$num_letracm = self::centena(Floor($numcmero / 1000)) . ' MIL ' . (self::centena($numcmero % 1000));
		}

		if ($numcmero < 100000)
			$num_letracm = self::decmiles($numcmero);

		return $num_letracm;
	}

	function millon($nummiero)
	{
		if ($nummiero >= 1000000 && $nummiero < 2000000) {
			$num_letramm = 'UN MILLON ' . (self::cienmiles($nummiero % 1000000));
		}

		if ($nummiero >= 2000000 && $nummiero < 10000000) {
			$num_letramm = self::unidad(Floor($nummiero / 1000000)) . ' MILLONES ' . (self::cienmiles($nummiero % 1000000));
		}

		if ($nummiero < 1000000)
			$num_letramm = self::cienmiles($nummiero);

		return $num_letramm;
	}

	function decmillon($numerodm)
	{
		if ($numerodm == 10000000)
			$num_letradmm = 'DIEZ MILLONES';

		if ($numerodm > 10000000 && $numerodm < 20000000) {
			$num_letradmm = self::decena(Floor($numerodm / 1000000)) . 'MILLONES ' . (self::cienmiles($numerodm % 1000000));
		}

		if ($numerodm >= 20000000 && $numerodm < 100000000) {
			$num_letradmm = self::decena(Floor($numerodm / 1000000)) . ' MILLONES ' . (self::millon($numerodm % 1000000));
		}

		if ($numerodm < 10000000)
			$num_letradmm = self::millon($numerodm);

		return $num_letradmm;
	}

	function cienmillon($numcmeros)
	{
		if ($numcmeros == 100000000)
			$num_letracms = 'CIEN MILLONES';

		if ($numcmeros >= 100000000 && $numcmeros < 1000000000) {
			$num_letracms = self::centena(Floor($numcmeros / 1000000)) . ' MILLONES ' . (self::millon($numcmeros % 1000000));
		}

		if ($numcmeros < 100000000)
			$num_letracms = self::decmillon($numcmeros);

		return $num_letracms;
	}

	function milmillon($nummierod)
	{
		if ($nummierod >= 1000000000 && $nummierod < 2000000000) {
			$num_letrammd = 'MIL ' . (self::cienmillon($nummierod % 1000000000));
		}

		if ($nummierod >= 2000000000 && $nummierod < 10000000000) {
			$num_letrammd = self::unidad(Floor($nummierod / 1000000000)) . ' MIL ' . (self::cienmillon($nummierod % 1000000000));
		}

		if ($nummierod < 1000000000)
			$num_letrammd = self::cienmillon($nummierod);

		return $num_letrammd;
	}

	function convertir($numero)
	{
		$num = str_replace(',', '', $numero);
		$num = number_format($num, 2, '.', '');
		$cents = substr($num, strlen($num) - 2, strlen($num) - 1);
		$num = (int) $num;
		$numf = self::milmillon($num);

		return $numf . ' CON ' . $cents . '/100';
	}

	/* FIN CONVERTIR NUMEROS A LETRAS */

	// CONSULTA EN EL SERVIDOR DE KIREDS PARA VALIDAR QUE EL CLIENTE EXISTA
	function connect_mysqli_main_server()
	{
		$mysqli_main = mysqli_connect(SERVER_MAIN, USER, PASS, DB_MAIN);

		$mysqli_main->set_charset('utf8');

		if ($mysqli_main->connect_errno) {
			echo 'Fallo al conectar a MySQL: ' . $mysqli_main->connect_error;
			exit;
		}

		return $mysqli_main;
	}	
}
