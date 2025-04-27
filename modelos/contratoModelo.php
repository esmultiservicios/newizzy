<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class contratoModelo extends mainModel{
		protected function agregar_contrato_modelo($datos){
			$contrato_id = mainModel::correlativo("contrato_id", "contrato");
			$insert = "INSERT INTO contrato VALUES('$contrato_id','".$datos['colaborador_id']."','".$datos['tipo_contrato_id']."','".$datos['pago_planificado_id']."','".$datos['tipo_empleado_id']."','".$datos['salario_mensual']."','".$datos['salario']."','".$datos['fecha_inicio']."','".$datos['fecha_fin']."','".$datos['notas']."','".$datos['usuario']."','".$datos['estado']."','".$datos['fecha_registro']."','".$datos['calculo_semanal']."')";
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_contrato_modelo($colaborador_id){
			$query = "SELECT contrato_id FROM contrato WHERE colaborador_id = '$colaborador_id' AND estado = 1";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			return $sql;
		}
		
		protected function edit_contrato_modelo($datos){
			$update = "UPDATE contrato
			SET 
				salario = '".$datos['salario']."',
				salario = '".$datos['salario']."',
				fecha_fin = '".$datos['fecha_fin']."',
				notas = '".$datos['notas']."',
				estado = '".$datos['estado']."'
			WHERE contrato_id = '".$datos['contrato_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_contrato_modelo($contrato_id){
			$delete = "DELETE FROM contrato WHERE contrato_id = '$contrato_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_contrato_nomina_modelo($contrato_id){
			$query = "SELECT c.contrato_id
				FROM contrato As c
				INNER JOIN nomina_detalles AS nd ON c.colaborador_id = nd.colaboradores_id
				WHERE c.contrato_id = '$contrato_id'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function getTotalContratosRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar contratos activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(contrato_id) AS total FROM contrato WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar contratos: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalContratosRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}