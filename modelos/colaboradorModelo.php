<?php
if($peticionAjax){
    require_once "../core/mainModel.php";
}else{
    require_once "./core/mainModel.php";    
}

class colaboradorModelo extends mainModel{
	protected function agregar_colaborador_modelo($datos) {
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
	
			$conexion->commit();
			$stmt->close();
			return true;
	
		} catch (Exception $e) {
			$conexion->rollback();
			error_log($e->getMessage()); // opcional para debugging
			return false;
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

	protected function editar_colaborador_modelo($datos){
		$conexion = mainModel::connection();
	
		try {
			$sql = "UPDATE colaboradores SET 
					puestos_id = ?, nombre = ?, estado = ?, telefono = ?, 
					empresa_id = ?, fecha_ingreso = ?, fecha_egreso = ?
					WHERE colaboradores_id = ?";
			$stmt = $conexion->prepare($sql);
			if (!$stmt) throw new Exception($conexion->error);
	
			$stmt->bind_param(
				"isiisssi",
				$datos['puesto'],
				$datos['nombre'],
				$datos['estado'],
				$datos['telefono'],
				$datos['empresa_id'],
				$datos['fecha_ingreso'],
				$datos['fecha_egreso'],
				$datos['colaborador_id']
			);
			$resultado = $stmt->execute();
			$stmt->close();
	
			return $resultado;
		} catch (Exception $e) {
			return false;
		}
	}
	
	protected function editar_colaborador_perfil_modelo($datos){
		$conexion = mainModel::connection();
	
		try {
			$sql = "UPDATE colaboradores SET nombre = ?, telefono = ? WHERE colaboradores_id = ?";
			$stmt = $conexion->prepare($sql);
			if (!$stmt) throw new Exception($conexion->error);
	
			$stmt->bind_param("ssi", $datos['nombre'], $datos['telefono'], $datos['colaborador_id']);
			$resultado = $stmt->execute();
			$stmt->close();
	
			return $resultado;
		} catch (Exception $e) {
			return false;
		}
	}
		 
    
	protected function valid_colaborador_bitacora($colaboradores_id){
		$conexion = mainModel::connection();
	
		try {
			$sql = "SELECT bitacora_id FROM bitacora WHERE colaboradores_id = ?";
			$stmt = $conexion->prepare($sql);
			if (!$stmt) throw new Exception($conexion->error);
	
			$stmt->bind_param("i", $colaboradores_id);
			$stmt->execute();
			$resultado = $stmt->get_result();
			$stmt->close();
	
			return $resultado;
		} catch (Exception $e) {
			return false;
		}
	}
	
    
	protected function delete_colaborador_modelo($colaboradores_id){
		$conexion = mainModel::connection();
	
		try {
			$sql = "DELETE FROM colaboradores WHERE colaboradores_id = ? AND colaboradores_id NOT IN(1)";
			$stmt = $conexion->prepare($sql);
			if (!$stmt) throw new Exception($conexion->error);
	
			$stmt->bind_param("i", $colaboradores_id);
			$resultado = $stmt->execute();
			$stmt->close();
	
			return $resultado;
		} catch (Exception $e) {
			return false;
		}
	}
			   
}