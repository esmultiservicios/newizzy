<?php
	if($peticionAjax){
		require_once "../core/mainModel.php";
	}else{
		require_once "./core/mainModel.php";
	}
	
	class proveedoresModelo extends mainModel{
		protected function agregar_proveedores_model($datos){
			$conexion = mainModel::connection();
			$stmt = null;
		
			try {
				// Usamos transacción por seguridad
				$conexion->autocommit(false);
		
				// Siguiente ID correlativo
				$proveedores_id = mainModel::correlativo("proveedores_id","proveedores");
		
				// 12 columnas = 12 placeholders
				$sql = "INSERT INTO proveedores
						(proveedores_id, nombre, rtn, fecha, departamentos_id, municipios_id, localidad, telefono, correo, estado, colaboradores_id, fecha_registro)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
				$stmt = $conexion->prepare($sql);
				if(!$stmt){
					throw new Exception("Prepare failed: ".$conexion->error);
				}
		
				// Tipos: i s s s i i s s s i i s  →  "isssiisssiis"
				$stmt->bind_param(
					"isssiisssiis",
					$proveedores_id,               // i
					$datos['nombre'],              // s
					$datos['rtn'],                 // s
					$datos['fecha'],               // s (YYYY-MM-DD)
					$datos['departamento_id'],     // i
					$datos['municipio_id'],        // i
					$datos['localidad'],           // s
					$datos['telefono'],            // s
					$datos['correo'],              // s
					$datos['estado'],              // i
					$datos['colaborador_id'],      // i
					$datos['fecha_registro']       // s (YYYY-MM-DD HH:MM:SS)
				);
		
				if(!$stmt->execute()){
					throw new Exception("Execute failed: ".$stmt->error);
				}
		
				// Confirmar transacción
				$conexion->commit();
				return true;
		
			} catch(Exception $e){
				// Revertir si algo falla
				if ($conexion && $conexion->errno === 0) {
					$conexion->rollback();
				}
				error_log("Error al insertar proveedor: ".$e->getMessage());
				return false;
		
			} finally {
				if($stmt){ $stmt->close(); }
				if($conexion){ $conexion->autocommit(true); }
			}
		}			
		
		protected function valid_proveedores_modelo($rtn){
			$query = "SELECT proveedores_id FROM proveedores WHERE rtn = '$rtn'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}	

		protected function edit_proveedores_modelo($datos){
			$update = "UPDATE proveedores
			SET
				nombre = '".$datos['nombre']."',
				departamentos_id = '".$datos['departamento_id']."',
				municipios_id = '".$datos['municipio_id']."',
				localidad = '".$datos['localidad']."',
				telefono = '".$datos['telefono']."',
				correo = '".$datos['correo']."',
				estado = '".$datos['estado']."',
				rtn = '".$datos['rtn']."'				
			WHERE proveedores_id = '".$datos['proveedores_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_proveedores_modelo($proveedores_id){
			$delete = "DELETE FROM proveedores WHERE proveedores_id = '$proveedores_id' AND proveedores_id NOT IN(1)";
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
		
			return $sql;			
		}
		
		protected function valid_proveedores_compras($proveedores_id){
			$query = "SELECT compras_id FROM compras WHERE proveedores_id = '$proveedores_id'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;				
		}

		protected function getTotalProveedoresRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar proveedores activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(proveedores_id) AS total FROM proveedores WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar proveedores: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalProveedoresRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}