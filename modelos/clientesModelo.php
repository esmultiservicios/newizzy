<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class clientesModelo extends mainModel{
		protected function agregar_clientes_modelo($datos) {
			$conexion = mainModel::connection();
						
			try {
				// Desactivar autocommit para la transacción
				$conexion->autocommit(false);
				
				// Obtener el próximo ID disponible
				$cliente_id = mainModel::correlativo("clientes_id", "clientes");
				
				// Sentencia preparada para seguridad
				$stmt = $conexion->prepare("INSERT INTO clientes VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '')");
				$stmt->bind_param("isssiisssiis", 
					$cliente_id,
					$datos['nombre'],
					$datos['rtn'],
					$datos['fecha'],
					$datos['departamento_id'],
					$datos['municipio_id'],
					$datos['localidad'],
					$datos['telefono'],
					$datos['correo'],
					$datos['estado_clientes'],
					$datos['colaborador_id'],
					$datos['fecha_registro']
				);
				
				$ejecutado = $stmt->execute();
				
				if(!$ejecutado) {
					throw new Exception($stmt->error);
				}
				
				// Obtener el ID insertado
				$id_insertado = $conexion->insert_id ?: $cliente_id;
				
				// Confirmar la transacción
				$conexion->commit();
				
				return $id_insertado;
				
			} catch(Exception $e) {			
				return false;
			}
		}

		// Método para DB principal
		protected function agregar_colaboradores_modelo($datos) {
			$conexion = mainModel::connection();
			
			try {
				$conexion->autocommit(false);
				
				$colaborador_id = mainModel::correlativo("colaboradores_id", "colaboradores");
				
				// Corregir la consulta SQL - 10 columnas, 10 marcadores
				$stmt = $conexion->prepare("INSERT INTO colaboradores 
										   (colaboradores_id, puestos_id, nombre, identidad, estado, telefono, empresa_id, fecha_registro, fecha_ingreso, fecha_egreso) 
										   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				
				// Asegurar que fecha_egreso tenga un valor por defecto si no está definido
				$fecha_egreso = isset($datos['fecha_egreso']) ? $datos['fecha_egreso'] : '';
				
				// Vincular 10 parámetros con tipos correctos
				$stmt->bind_param("iissiissss", 
					$colaborador_id,
					$datos['puestos_id'],
					$datos['nombre'],
					$datos['identidad'],
					$datos['estado'],
					$datos['telefono'],
					$datos['empresa_id'],
					$datos['fecha_registro'],
					$datos['fecha_ingreso'],
					$fecha_egreso
				);
				
				$ejecutado = $stmt->execute();
				
				if(!$ejecutado) {
					throw new Exception($stmt->error);
				}
				
				$id_insertado = $conexion->insert_id ?: $colaborador_id;
				$conexion->commit();
				
				return $id_insertado;
				
			} catch(Exception $e) {
				$conexion->rollback();
				error_log("Error al insertar colaborador: " . $e->getMessage());
				return false;
			} finally {
				if(isset($stmt)) {
					$stmt->close();
				}
				$conexion->autocommit(true);
			}
		}
			
		protected function valid_clientes_modelo($rtn){
			$query = "SELECT clientes_id FROM clientes WHERE rtn = '$rtn'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}	
		
		protected function edit_clientes_modelo($datos){
			$update = "UPDATE clientes
			SET
				nombre = '".$datos['nombre']."',
				departamentos_id = '".$datos['departamento_id']."',
				municipios_id = '".$datos['municipio_id']."',
				localidad = '".$datos['localidad']."',
				telefono = '".$datos['telefono']."',
				correo = '".$datos['correo']."',
				estado = '".$datos['estado']."',
				rtn = '".$datos['rtn']."'				
			WHERE clientes_id = '".$datos['clientes_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_clientes_modelo($clientes_id){
			$delete = "DELETE FROM clientes WHERE clientes_id = '$clientes_id' AND clientes_id NOT IN(1)";
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
		
			return $sql;			
		}
		
		protected function valid_clientes_facturas_modelo($clientes_id){
			$query = "SELECT facturas_id FROM facturas WHERE clientes_id = '$clientes_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function getTotalClientesRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar clientes activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(clientes_id) AS total FROM clientes WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar clientes: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalClientesRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}