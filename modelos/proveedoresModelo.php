<?php
	if($peticionAjax){
		require_once "../core/mainModel.php";
	}else{
		require_once "./core/mainModel.php";
	}
	
	class proveedoresModelo extends mainModel{
		protected function agregar_proveedores_model($datos){
			$proveedores_id = mainModel::correlativo("proveedores_id","proveedores");
			$insert = "INSERT INTO proveedores VALUES('$proveedores_id','".$datos['nombre']."','".$datos['rtn']."','".$datos['fecha']."','".$datos['departamento_id']."','".$datos['municipio_id']."','".$datos['localidad']."','".$datos['telefono']."','".$datos['correo']."','".$datos['estado']."','".$datos['colaborador_id']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;		
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