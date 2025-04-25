<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";	
}

class usuarioModelo extends mainModel{
    /*----------- Modelo para agregar colaborador -----------*/
    protected function agregar_colaborador_modelo($datos){
        $conexion = mainModel::connection();
		
		try {
			$conexion->autocommit(false);
	
			$colaboradores_id = mainModel::correlativo("colaboradores_id", "colaboradores");
	
			$sql = "INSERT INTO colaboradores (
						colaboradores_id, puestos_id, nombre, identidad, estado, 
						telefono, empresa_id, fecha_registro, fecha_ingreso, fecha_egreso
					) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
	
			$stmt = $conexion->prepare($sql);
	
			if (!$stmt) {
				throw new Exception("Error al preparar la consulta: " . $conexion->error);
			}
	
			$stmt->bind_param(
				"iissisisss", // ojo: revisa si todos los campos son estos tipos (fecha como string)
				$colaboradores_id,
				$datos['puesto'],
				$datos['nombre'],
				$datos['identidad'],
				$datos['estado'],
				$datos['telefono'],
				$datos['empresa'],
				$datos['fecha_registro'],
				$datos['fecha_ingreso'],
				$datos['fecha_egreso']
			);
	
			if (!$stmt->execute()) {
				throw new Exception("Error en ejecución: " . $stmt->error);
			}

            // Obtener el ID insertado
			$id_insertado = $conexion->insert_id ?: $colaboradores_id;
	
			$conexion->commit();
			$stmt->close();

			return $id_insertado;
	
		} catch (Exception $e) {
			$conexion->rollback();
			return false;
		}
    }

    /*----------- Modelo para agregar usuario -----------*/
    protected function agregar_usuario_modelo($datos){
        $conexion = mainModel::connection();
        
        try {
            $conexion->autocommit(false);
            
            // Obtener el próximo ID disponible
            $users_id = mainModel::correlativo("users_id", "users");
            
            // Sentencia preparada para insertar usuario
            $stmt = $conexion->prepare("INSERT INTO users 
                (users_id, colaboradores_id, privilegio_id, password, email, tipo_user_id, estado, fecha_registro, empresa_id, server_customers_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            
            $stmt->bind_param("iiissiiii", 
                $users_id,
                $datos['colaborador_id'],
                $datos['privilegio_id'],
                $datos['pass'],
                $datos['email'],
                $datos['tipo_user'],
                $datos['estado'],
                $datos['empresa'],
                $datos['server_customers_id']
            );
        
            
            if(!$stmt->execute()) {
                throw new Exception($stmt->error);
            }
            
            $conexion->commit();
            return $users_id;
            
        } catch(Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }

    protected function valid_colaborador_modelo($identidad){
		$conexion = mainModel::connection();
	
		try {
			$sql = "SELECT colaboradores_id FROM colaboradores WHERE identidad = ?";
			$stmt = $conexion->prepare($sql);
			if (!$stmt) throw new Exception($conexion->error);
	
			$stmt->bind_param("s", $identidad);
			$stmt->execute();
			$resultado = $stmt->get_result();
			$stmt->close();
	
			return $resultado;
		} catch (Exception $e) {
			return false;
		}
	}	
    
    /*----------- Modelo para validar usuario existente -----------*/
    protected function valid_user_modelo($colaborador_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT users_id FROM users WHERE colaboradores_id = ?");
            $stmt->bind_param("i", $colaborador_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        } catch(Exception $e) {
            return false;
        }
    }	

    /*----------- Modelo para validar correo existente -----------*/
    protected function valid_correo_modelo($email){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT users_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        } catch(Exception $e) {
            return false;
        }
    }			

    /*----------- Modelo para editar usuario -----------*/
    protected function edit_user_modelo($datos){
        $conexion = mainModel::connection();
        
        try {
            $conexion->autocommit(false);
            
            $stmt = $conexion->prepare("UPDATE users SET 
                tipo_user_id = ?,
                privilegio_id = ?,
                empresa_id = ?,
                estado = ?
                WHERE users_id = ?");
            
            $stmt->bind_param("iiiii", 
                $datos['tipo_user'],
                $datos['privilegio_id'],
                $datos['empresa_id'],
                $datos['estado'],
                $datos['usuarios_id']
            );
            
            $result = $stmt->execute();
            
            if(!$result) {
                throw new Exception($stmt->error);
            }
            
            $conexion->commit();
            return true;
            
        } catch(Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }
    
    /*----------- Modelo para eliminar usuario -----------*/
    protected function delete_user_modelo($users_id){
        $conexion = mainModel::connection();
        
        try {
            $conexion->autocommit(false);
            
            $stmt = $conexion->prepare("DELETE FROM users WHERE users_id = ?");
            $stmt->bind_param("i", $users_id);
            
            $result = $stmt->execute();
            
            if(!$result) {
                throw new Exception($stmt->error);
            }
            
            $conexion->commit();
            return true;
            
        } catch(Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }
    
    /*----------- Modelo para resetear contraseña -----------*/
    protected function resetear_password_modelo($users_id, $new_password){
        $conexion = mainModel::connection();
        
        try {
            $conexion->autocommit(false);
            
            $stmt = $conexion->prepare("UPDATE users SET password = ? WHERE users_id = ?");
            $stmt->bind_param("si", $new_password, $users_id);
            
            $result = $stmt->execute();
            
            if(!$result) {
                throw new Exception($stmt->error);
            }
            
            $conexion->commit();
            return true;
            
        } catch(Exception $e) {
            $conexion->rollback();
            return false;
        } finally {
            $conexion->autocommit(true);
        }
    }
    
    /*----------- Modelo para validar usuario en bitácora -----------*/
    protected function valid_user_bitacora($user_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT b.colaboradores_id
                FROM bitacora as b
                INNER JOIN users AS u ON b.colaboradores_id = u.colaboradores_id
                WHERE u.users_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        } catch(Exception $e) {
            return false;
        }
    }

    /*----------- Modelo para obtener el total de usuarios extras -----------*/
    protected function getTotalUsuariosExtras(){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT user_extra FROM plan");
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc()['user_extra'] ?? 0;
        } catch(Exception $e) {
            return 0;
        }
    }	

    /*----------- Modelo para contar usuarios activos -----------*/
    protected function getTotalUsuarios(){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT COUNT(*) AS 'total_usuarios' FROM users WHERE estado = 1 AND tipo_user_id NOT IN(1)");
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc()['total_usuarios'] ?? 0;
        } catch(Exception $e) {
            return 0;
        }
    }		

    /*----------- Modelo para obtener configuración del plan -----------*/
    protected function getPlanConfiguracion(){       
        $config = mainModel::getPlanConfiguracionMainModel(); // devuelve un array
        return is_array($config) ? $config : [];
    }
    
    
    /*----------- Modelo para obtener correos de administradores -----------*/
    protected function getCorrreosAdmin(){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT users.email, CONCAT(colaboradores.nombre, ' ', colaboradores.apellido) AS nombre_completo
                FROM colaboradores
                INNER JOIN users ON colaboradores.colaboradores_id = users.colaboradores_id
                WHERE users.privilegio_id IN(1,2) AND users.estado = 1");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch(Exception $e) {
            return [];
        }
    }
    
    /*----------- Modelo para obtener información de un colaborador -----------*/
    public function get_colaborador_info($colaborador_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT nombre, identidad, telefono FROM colaboradores WHERE colaboradores_id = ?");
            $stmt->bind_param("i", $colaborador_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch(Exception $e) {
            return false;
        }
    }
    
    /*----------- Modelo para obtener información de un usuario -----------*/
    public function get_usuario_info($users_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT u.email, c.nombre 
                FROM users u 
                JOIN colaboradores c ON u.colaboradores_id = c.colaboradores_id 
                WHERE u.users_id = ?");
            $stmt->bind_param("i", $users_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch(Exception $e) {
            return false;
        }
    }
    
    /*----------- Modelo para obtener información de un usuario revendedor -----------*/
    public function get_usuario_revendedor($users_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT u.email, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo
                FROM users u
                JOIN colaboradores c ON u.colaboradores_id = c.colaboradores_id
                WHERE u.users_id = ? AND u.privilegio_id = 3");
            $stmt->bind_param("i", $users_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch(Exception $e) {
            return false;
        }
    }
    
    /*----------- Modelo para obtener información de un privilegio -----------*/
    public function get_privilegio_info($privilegio_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT nombre FROM privilegio WHERE privilegio_id = ?");
            $stmt->bind_param("i", $privilegio_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch(Exception $e) {
            return false;
        }
    }
    
    /*----------- Modelo para obtener información de una empresa -----------*/
    public function get_empresa_info($empresa_id){
        $conexion = mainModel::connection();
        
        try {
            $stmt = $conexion->prepare("SELECT nombre FROM empresa WHERE empresa_id = ?");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch(Exception $e) {
            return false;
        }
    }
}