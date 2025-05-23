<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class cuentaContabilidadModelo extends mainModel{
		protected function agregar_cuenta_contabilidad_modelo($datos){
			$cuentas_id = mainModel::correlativo("cuentas_id", "cuentas");
			$insert = "INSERT INTO cuentas VALUES('$cuentas_id','".$datos['codigo']."','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_cuenta_contable_modelo($nombre){
			$query = "SELECT cuentas_id FROM cuentas WHERE nombre = '$nombre'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function edit_cuentas_contabilidad_modelo($datos){
			$update = "UPDATE cuentas
			SET 
				nombre = '".$datos['nombre']."',
				estado = '".$datos['estado']."'
			WHERE cuentas_id = '".$datos['cuentas_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_cuenta_contabilidad_modelo($cuentas_id){
			$delete = "DELETE FROM cuentas WHERE cuentas_id = '$cuentas_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function valid_cuenta_contable_movimientos_modelo($cuentas_id){
			$query = "SELECT movimientos_cuentas_id FROM movimientos_cuentas WHERE cuentas_id = '$cuentas_id'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function getTotalCuentasRegistradas() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar cuentas contables activas (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(cuentas_id) AS total FROM  cuentas WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar cuentas contables: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalCuentasRegistradas: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}