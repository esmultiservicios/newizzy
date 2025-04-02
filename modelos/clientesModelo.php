<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class clientesModelo extends mainModel{
		protected function agregar_clientes_modelo($datos){
			$cliente_id = mainModel::correlativo("clientes_id", "clientes");
			$insert = "INSERT INTO clientes VALUES('$cliente_id','".$datos['nombre']."','".$datos['rtn']."','".$datos['fecha']."','".$datos['departamento_id']."','".$datos['municipio_id']."','".$datos['localidad']."','".$datos['telefono']."','".$datos['correo']."','".$datos['estado_clientes']."','".$datos['colaborador_id']."','".$datos['fecha_registro']."','','','','')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
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
	}
?>