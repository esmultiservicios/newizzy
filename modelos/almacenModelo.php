<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class almacenModelo extends mainModel{
		protected function agregar_almacen_modelo($datos){
			$almacen_id = mainModel::correlativo("almacen_id", "almacen");
			$insert = "INSERT INTO almacen VALUES('$almacen_id','".$datos['ubicacion_almacen']."',
			'".$datos['almacen_almacen']."','".$datos['estado']."','".$datos['empresa']."','".$datos['facturar_cero']."',
			'".$datos['fecha_registro']."')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_almacen_modelo($almacen){
			$query = "SELECT almacen_id FROM almacen WHERE nombre = '$almacen'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_almacen_modelo($datos){
			$update = "UPDATE almacen
			SET 
				nombre = '".$datos['almacen_almacen']."',				
				estado = '".$datos['estado']."',
				facturar_cero = '".$datos['facturar_cero']."'
			WHERE almacen_id = '".$datos['almacen_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_almacen_modelo($almacen_id){
			$delete = "DELETE FROM almacen WHERE almacen_id = '$almacen_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_almacen_productos_modelo($almacen_id){
			$query = "SELECT productos_id FROM productos WHERE almacen_id = '$almacen_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function getTotalAlmacenesRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar almacenes activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(almacen_id) AS total FROM almacen WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar almacenes: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalAlmacenesRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}