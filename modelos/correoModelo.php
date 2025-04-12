<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class correoModelo extends mainModel{		
		protected function edit_correo_modelo($datos) {
			$conexion = mainModel::connection();
		
			try {
				// Iniciar transacción
				$conexion->autocommit(false);
		
				// Preparar la sentencia UPDATE
				$stmt = $conexion->prepare("
					UPDATE correo SET 
						server = ?, 
						correo = ?, 
						password = ?, 
						port = ?, 
						smtp_secure = ?
					WHERE correo_id = ?
				");
		
				$stmt->bind_param("sssssi", 
					$datos['server'],
					$datos['correo'],
					$datos['password'],
					$datos['port'],
					$datos['smtp_secure'],
					$datos['correo_id']
				);
		
				$ejecutado = $stmt->execute();
		
				if (!$ejecutado) {
					throw new Exception($stmt->error);
				}
		
				// Confirmar la transacción
				$conexion->commit();
		
				return true;
		
			} catch (Exception $e) {
				// Revertir en caso de error
				$conexion->rollback();
				return false;
			}
		}		

		protected function agregar_destinatarios_modelo($datos) {
			$conexion = mainModel::connection();
		
			try {
				// Desactivar autocommit para iniciar transacción
				$conexion->autocommit(false);
		
				// Obtener el próximo ID disponible
				$notificaciones_id = mainModel::correlativo("notificaciones_id", "notificaciones");
		
				// Sentencia preparada
				$stmt = $conexion->prepare("INSERT INTO notificaciones (notificaciones_id, correo, nombre, activo) VALUES (?, ?, ?, ?)");
		
				$activo = 1; // valor por defecto como en tu estructura
		
				$stmt->bind_param("isss",
					$notificaciones_id,
					$datos['correo'],
					$datos['nombre'],
					$activo
				);
		
				$ejecutado = $stmt->execute();
		
				if (!$ejecutado) {
					throw new Exception($stmt->error);
				}
		
				// Confirmar la transacción
				$conexion->commit();
		
				return $notificaciones_id;
		
			} catch (Exception $e) {
				// Revertir si hay error
				$conexion->rollback();
				return false;
			}
		}			

		protected function valid_pdestinatarios_modelo($correo) {
			$conexion = mainModel::connection();
		
			try {
				$stmt = $conexion->prepare("SELECT notificaciones_id FROM notificaciones WHERE correo = ?");
				$stmt->bind_param("s", $correo);
		
				$stmt->execute();
				$resultado = $stmt->get_result();
		
				return $resultado;
		
			} catch (Exception $e) {
				return false;
			}
		}		
	}