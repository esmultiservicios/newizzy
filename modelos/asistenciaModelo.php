<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class asistenciaModelo  extends mainModel{
		protected function agregar_asistencia_modelo($datos){
			$asistencia_id = mainModel::correlativo("asistencia_id ", "asistencia");
			$insert = "INSERT INTO asistencia
					VALUES('$asistencia_id','".$datos['colaborador']."','".$datos['fecha']."','".$datos['horai']."','".$datos['horaf']."','".$datos['comentario']."','".$datos['estado']."','".$datos['fecha_registro']."')";
					$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function valid_asistencia_horai_modelo($datos){
			$query = "SELECT a.asistencia_id AS 'asistencia_id', a.fecha AS 'fecha', CONCAT(c.nombre, ' ', c.apellido) AS 'colaborador', a.horai AS 'horai'
				FROM asistencia AS a
				INNER JOIN colaboradores AS c ON a.colaboradores_id = c.colaboradores_id
				WHERE a.colaboradores_id = '".$datos['colaborador']."' AND a.fecha = '".$datos['fecha']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}

		protected function valid_asistencia_horaf_modelo($datos){
			$query = "SELECT a.asistencia_id AS 'asistencia_id', a.fecha AS 'fecha', CONCAT(c.nombre, ' ', c.apellido) AS 'colaborador', a.horaf AS 'horaf'
				FROM asistencia AS a
				INNER JOIN colaboradores AS c ON a.colaboradores_id = c.colaboradores_id
				WHERE a.colaboradores_id = '".$datos['colaborador']."' AND a.fecha = '".$datos['fecha']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}

		protected function update_asistencia_marcaje_modelo($datos){
			$query = "UPDATE asistencia
				SET 
					horai = '".$datos['horaf']."',
					horaf = '".$datos['horaf']."',
					comentario = '".$datos['comentario']."'
				WHERE colaboradores_id = '".$datos['colaborador']."' AND fecha = '".$datos['fecha']."'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
						
			return $sql;					
		}

		protected function getComentarioAsistenciaModelo($datos){
			$query = "SELECT comentario
				FROM asistencia
				WHERE colaboradores_id = '".$datos['colaborador']."' AND fecha = '".$datos['fecha']."'";
			
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	