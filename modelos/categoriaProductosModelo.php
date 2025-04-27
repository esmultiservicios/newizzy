<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class categoriaProductosModelo extends mainModel{
		protected function agregar_categoria_productos_modelo($datos){
			$categoria_id = mainModel::correlativo("categoria_id", "categoria");
			$insert = "INSERT INTO categoria VALUES('$categoria_id','".$datos['nombre']."','".$datos['estado']."','".$datos['fecha_registro']."')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_categoria_productos_modelo($categoria){
			$query = "SELECT categoria_id FROM categoria WHERE nombre = '$categoria'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
	
			return $sql;
		}
		
		protected function edit_categoria_productos_modelo($datos){
			$update = "UPDATE categoria
			SET 
				nombre = '".$datos['nombre']."',				
				estado = '".$datos['estado']."'
			WHERE categoria_id = '".$datos['categoria_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function delete_categoria_productos_modelo($categoria_id){
			$delete = "DELETE FROM categoria WHERE categoria_id = '$categoria_id'";
			
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function valid_categoria_id_productos_modelo($categoria_id){
			$query = "SELECT productos_id FROM productos WHERE categoria_id = '$categoria_id'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}	
		
		protected function getTotalCategoriasRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar categorias activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(categoria_id) AS total FROM categoria WHERE estado = 1";
				
				// Ejecutar consulta
				$resultado = $conexion->query($query);
				
				if (!$resultado) {
					throw new Exception("Error al contar categorias: " . $conexion->error);
				}
				
				// Obtener el total
				$fila = $resultado->fetch_assoc();
				return (int)$fila['total'];
				
			} catch (Exception $e) {
				error_log("Error en getTotalCategoriasRegistrados: " . $e->getMessage());
				return 0; // Retorna 0 si hay error para no bloquear el sistema
			}
		}
	}