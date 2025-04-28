<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";		
    }

	class loginModel extends mainModel{
		protected function iniciar_sesion_modelo($datos){
			$username = $datos['username'];
			$password = $datos['password'];
			$db = $datos['db'];
			
			$estatus = 1; // USUARIO ACTIVO
		
			$mysqli = mainModel::connectionDBLocal($db);
			
			// Preparar la consulta con parámetros
			$query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
				FROM users AS u
				INNER JOIN tipo_user AS tu
				ON u.tipo_user_id = tu.tipo_user_id 
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE BINARY u.email = ? AND u.password = ? AND u.estado = ?
				GROUP BY u.tipo_user_id";
		
			// Preparar la declaración
			$stmt = $mysqli->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("ssi", $username, $password, $estatus);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}		

		protected function iniciar_sesion_admin_modelo($datos){
			$username = $datos['username'];
			$password = $datos['password'];
			$db = $datos['db'];
			
			$estatus = 1; // USUARIO ACTIVO
		
			$mysqli = mainModel::connectionDBLocal($db);
			
			// Preparar la consulta con parámetros
			$query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
				FROM users AS u
				INNER JOIN tipo_user AS tu
				ON u.tipo_user_id = tu.tipo_user_id 
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE BINARY u.username = ? AND u.password = ? AND u.estado = ?
				GROUP BY u.tipo_user_id";
		
			// Preparar la declaración
			$stmt = $mysqli->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("ssi", $username, $password, $estatus);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}			
		
		protected function getMenuAccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			// Preparar la consulta con parámetros
			$query = "SELECT am.acceso_menu_id AS 'acceso_menu_id', m.name AS 'name'
				FROM acceso_menu AS am
				INNER JOIN menu AS m
				ON am.menu_id = m.menu_id
				WHERE am.privilegio_id = ? AND m.name = 'dashboard' AND am.estado = 1
				ORDER BY am.menu_id ASC
				LIMIT 1";
		
			// Preparar la declaración
			$stmt = $mysqli->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("i", $privilegio_id);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}		
		
		protected function getSubMenuAccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			// Preparar la consulta con parámetros
			$query = "SELECT asm.acceso_submenu_id AS 'acceso_menu_id', sm.name AS 'name'
				FROM acceso_submenu AS asm
				INNER JOIN submenu AS sm
				ON asm.submenu_id = sm.submenu_id
				WHERE asm.privilegio_id = ? AND asm.estado = 1 AND sm.submenu_id NOT IN(7,8,9)
				ORDER BY asm.submenu_id ASC
				LIMIT 1";
		
			// Preparar la declaración
			$stmt = $mysqli->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("i", $privilegio_id);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}		

		protected function getSubMenu1AccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			// Estado a validar
			$estado = 1;
			
			// Preparar la consulta con parámetros
			$query = "SELECT asm.acceso_submenu1_id AS 'acceso_menu_id', sm.name AS 'name'
				FROM acceso_submenu1 AS asm
				INNER JOIN submenu1 AS sm
				ON asm.submenu1_id = sm.submenu1_id
				WHERE asm.privilegio_id = ? AND asm.estado = ?
				ORDER BY asm.submenu1_id ASC";
			
			// Preparar la declaración
			$stmt = $mysqli->prepare($query);
			
			// Vincular parámetros
			$stmt->bind_param("ii", $privilegio_id, $estado);
			
			// Ejecutar la consulta
			$stmt->execute();
			
			// Obtener resultado
			$result = $stmt->get_result();
			
			// Cerrar la declaración
			$stmt->close();
			
			return $result;
		}		
		
		protected function cerrar_sesion_modelo($datos){
			if($datos['usuario'] != "" && $datos['token_s'] == $datos['token']){
				$abitacora = mainModel::actualizar_hora_salida_bitacora($datos['codigo'], $datos['hora']);
				
				if($abitacora){
					session_unset(); // VACIAR LA SESION
					session_destroy(); // DESTRUIR LA SESION
					$respuesta = 1;
				} else {
					$respuesta = 2;
				}
				
			} else {
				$respuesta = 2;
			}
			
			return $respuesta; // Retorna 1 si se cerró la sesión correctamente, 2 si hubo un error
		}		

		protected function validar_pago_pendiente_main_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
			$validar = 1; // SE VALIDARÁ EL CLIENTE PARA PODER INICIAR SESIÓN
		
			// Estado a validar
			$estado = 1;
		
			// Preparar la consulta con parámetros
			$query = "SELECT sc.clientes_id AS 'clientes_id'
				FROM server_customers AS sc
				INNER JOIN clientes AS c
				ON sc.clientes_id = c.clientes_id
				LEFT JOIN cobrar_clientes AS cc
				on cc.clientes_id = sc.clientes_id
				WHERE cc.estado = ? AND sc.db = ?";
		
			// Preparar la declaración
			$stmt = $mysqli_main->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("is", $estado, $GLOBALS['db']);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}		

		//CONSULTAMOS SI ES NECESARIO VALIDAR AL CLIENTE PARA SU INICIO DE SESION
		protected function validar_cliente_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
		
			// Preparar la consulta con parámetros
			$query = "SELECT sc.validar AS 'validar'
				FROM server_customers AS sc
				INNER JOIN clientes AS c
				ON sc.clientes_id = c.clientes_id
				WHERE sc.db = ?";
		
			// Preparar la declaración
			$stmt = $mysqli_main->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("s", $GLOBALS['db']);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}		
		
		//CONSULTAMOS SI EL CLIENTE TIENE PAGO PENDIENTE DE MESES ANTERIORES
		protected function validar_cliente_pagos_vencidos_main_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
		
			// Estado pendiente
			$estado = 1;
		
			// Preparar la consulta con parámetros
			$query = "SELECT DISTINCT sc.clientes_id AS 'clientes_id'
				FROM server_customers AS sc
				INNER JOIN clientes AS c ON sc.clientes_id = c.clientes_id
				LEFT JOIN cobrar_clientes AS cc ON cc.clientes_id = sc.clientes_id
				WHERE cc.estado = ? 
				AND sc.db = ? 
				AND (
					(cc.fecha < DATE_FORMAT(CURDATE(), '%Y-%m-01')) -- Facturas de meses anteriores
					OR 
					(DAY(CURDATE()) >= 16 AND MONTH(cc.fecha) = MONTH(CURDATE()) AND YEAR(cc.fecha) = YEAR(CURDATE())) -- Facturas del mes actual vencidas
				)";
		
			// Preparar la declaración
			$stmt = $mysqli_main->prepare($query);
		
			// Vincular parámetros
			$stmt->bind_param("is", $estado, $GLOBALS['db']);
		
			// Ejecutar la consulta
			$stmt->execute();
		
			// Obtener resultado
			$result = $stmt->get_result();
		
			// Cerrar la declaración
			$stmt->close();
		
			return $result;
		}						
	}