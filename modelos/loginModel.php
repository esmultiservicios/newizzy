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
			
			$estatus = 1;
		
			$mysqli = mainModel::connectionDBLocal($db);
			
			$query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
				FROM users AS u
				INNER JOIN tipo_user AS tu
				ON u.tipo_user_id = tu.tipo_user_id 
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE BINARY u.email = ? AND u.password = ? AND u.estado = ?
				GROUP BY u.tipo_user_id";
		
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("ssi", $username, $password, $estatus);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}		

		protected function iniciar_sesion_admin_modelo($datos){
			$username = $datos['username'];
			$password = $datos['password'];
			$db = $datos['db'];
			
			$estatus = 1;
		
			$mysqli = mainModel::connectionDBLocal($db);
			
			$query = "SELECT u.*, tu.nombre AS 'cuentaTipo', c.identidad
				FROM users AS u
				INNER JOIN tipo_user AS tu
				ON u.tipo_user_id = tu.tipo_user_id 
				INNER JOIN colaboradores AS c
				ON u.colaboradores_id = c.colaboradores_id
				WHERE BINARY u.username = ? AND u.password = ? AND u.estado = ?
				GROUP BY u.tipo_user_id";
		
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("ssi", $username, $password, $estatus);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}

        protected function obtener_server_customer_por_email_modelo($username){
            $mysqli_main = mainModel::connect_mysqli_main_server();

            $query = "SELECT 
                    COALESCE(s.server_customers_id, 0) AS server_customers_id,
                    COALESCE(s.db, ?) AS db,
                    COALESCE(s.codigo_cliente, 0) AS codigo_cliente,
                    COALESCE(s.clientes_id, 0) AS clientes_id,
                    COALESCE(s.planes_id, 0) AS planes_id,
                    COALESCE(s.sistema_id, 0) AS sistema_id,
                    COALESCE(s.validar, 1) AS validar,
                    COALESCE(s.estado, 0) AS estado
                FROM users AS u
                LEFT JOIN server_customers AS s 
                    ON u.server_customers_id = s.server_customers_id
                WHERE BINARY u.email = ?
                LIMIT 1";

            $stmt = $mysqli_main->prepare($query);
            $dbDefault = DB_MAIN;
            $stmt->bind_param("ss", $dbDefault, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            return $result;
        }			
		
		protected function getMenuAccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			$query = "SELECT am.acceso_menu_id AS 'acceso_menu_id', m.name AS 'name'
				FROM acceso_menu AS am
				INNER JOIN menu AS m
				ON am.menu_id = m.menu_id
				WHERE am.privilegio_id = ? AND m.name = 'dashboard' AND am.estado = 1
				ORDER BY am.menu_id ASC
				LIMIT 1";
		
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("i", $privilegio_id);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}		
		
		protected function getSubMenuAccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			$query = "SELECT asm.acceso_submenu_id AS 'acceso_menu_id', sm.name AS 'name'
				FROM acceso_submenu AS asm
				INNER JOIN submenu AS sm
				ON asm.submenu_id = sm.submenu_id
				WHERE asm.privilegio_id = ? AND asm.estado = 1 AND sm.submenu_id NOT IN(7,8,9)
				ORDER BY asm.submenu_id ASC
				LIMIT 1";
		
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("i", $privilegio_id);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}		

		protected function getSubMenu1AccesoLogin($privilegio_id){
			$mysqli = mainModel::connection();
			
			$estado = 1;
			
			$query = "SELECT asm.acceso_submenu1_id AS 'acceso_menu_id', sm.name AS 'name'
				FROM acceso_submenu1 AS asm
				INNER JOIN submenu1 AS sm
				ON asm.submenu1_id = sm.submenu1_id
				WHERE asm.privilegio_id = ? AND asm.estado = ?
				ORDER BY asm.submenu1_id ASC";
			
			$stmt = $mysqli->prepare($query);
			$stmt->bind_param("ii", $privilegio_id, $estado);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
			
			return $result;
		}		
		
		protected function cerrar_sesion_modelo($datos){
			if($datos['usuario'] != "" && $datos['token_s'] == $datos['token']){
				$abitacora = mainModel::actualizar_hora_salida_bitacora($datos['codigo'], $datos['hora']);
				
				if($abitacora){
					session_unset();
					session_destroy();
					$respuesta = 1;
				} else {
					$respuesta = 2;
				}
				
			} else {
				$respuesta = 2;
			}
			
			return $respuesta;
		}		

		protected function validar_pago_pendiente_main_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
			$estado = 1;
		
			$query = "SELECT sc.clientes_id AS 'clientes_id'
				FROM server_customers AS sc
				INNER JOIN clientes AS c
				ON sc.clientes_id = c.clientes_id
				LEFT JOIN cobrar_clientes AS cc
				ON cc.clientes_id = sc.clientes_id
				WHERE cc.estado = ? 
				AND sc.db = ?";
		
			$stmt = $mysqli_main->prepare($query);
			$stmt->bind_param("is", $estado, $GLOBALS['db']);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}		

		protected function validar_cliente_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
		
			$query = "SELECT 
                    sc.server_customers_id AS 'server_customers_id',
                    sc.clientes_id AS 'clientes_id',
                    sc.codigo_cliente AS 'codigo_cliente',
                    sc.db AS 'db',
                    sc.validar AS 'validar',
                    sc.estado AS 'estado'
				FROM server_customers AS sc
				INNER JOIN clientes AS c
				ON sc.clientes_id = c.clientes_id
				WHERE sc.db = ?
                LIMIT 1";
		
			$stmt = $mysqli_main->prepare($query);
			$stmt->bind_param("s", $GLOBALS['db']);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}		
		
		protected function validar_cliente_pagos_vencidos_main_server_modelo(){
			$mysqli_main = mainModel::connect_mysqli_main_server();
			$estado = 1;
		
			$query = "SELECT DISTINCT sc.clientes_id AS 'clientes_id'
				FROM server_customers AS sc
				INNER JOIN clientes AS c 
                    ON sc.clientes_id = c.clientes_id
				LEFT JOIN cobrar_clientes AS cc 
                    ON cc.clientes_id = sc.clientes_id
				WHERE cc.estado = ? 
				AND sc.db = ? 
				AND (
					cc.fecha < DATE_FORMAT(CURDATE(), '%Y-%m-01')
					OR 
					(
                        DAY(CURDATE()) >= 16 
                        AND MONTH(cc.fecha) = MONTH(CURDATE()) 
                        AND YEAR(cc.fecha) = YEAR(CURDATE())
                    )
				)";
		
			$stmt = $mysqli_main->prepare($query);
			$stmt->bind_param("is", $estado, $GLOBALS['db']);
			$stmt->execute();
			$result = $stmt->get_result();
			$stmt->close();
		
			return $result;
		}						
	}