<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class productosModelo extends mainModel{
		protected function agregar_productos_modelo($datos){
			$productos_id = mainModel::correlativo("productos_id", "productos");
			$insert = "INSERT INTO productos VALUES('$productos_id','".$datos['bar_code_product']."','".$datos['almacen_id']."',
			'".$datos['medida_id']."','".$datos['categoria_id']."','".$datos['nombre']."','".$datos['descripcion']."',
			'".$datos['tipo_producto']."','".$datos['precio_compra']."','".$datos['porcentaje_venta']."',
			'".$datos['precio_venta']."','".$datos['cantidad_mayoreo']."','".$datos['precio_mayoreo']."','".$datos['cantidad_minima']."',
			'".$datos['cantidad_maxima']."','".$datos['estado']."','".$datos['isv_venta']."','".$datos['isv_compra']."',
			'".$datos['colaborador_id']."','".$datos['file']."','".$datos['empresa']."','".$datos['fecha_registro']."',
			'".$datos['id_producto_superior']."')";		
			
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;			
		}
		
		protected function agregar_movimientos_productos_modelo($datos){
			$movimientos_id = mainModel::correlativo("movimientos_id", "movimientos");
			$documento = "Entrada Productos ".$movimientos_id;
			$insert = "INSERT INTO movimientos 
				VALUES(
					'$movimientos_id','".$datos['productos_id']."','$documento','".$datos['cantidad_entrada']."',
				'".$datos['cantidad_salida']."','".$datos['saldo']."','".$datos['empresa']."','".$datos['fecha_registro']."',
				'".$datos['clientes_id']."','".$datos['comentario']."','".$datos['almacen_id']."')";
					
			$result = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
		
			return $result;			
		}
		
		protected function valid_bar_code_productos_modelo($bar_code_product, $empresa){
			$query = "SELECT productos_id FROM productos WHERE barCode = '$bar_code_product' AND empresa_id = '$empresa'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}	

		protected function valid_nombre_producto_modelo($nombre, $empresa){
			$query = "SELECT productos_id FROM productos WHERE nombre = '$nombre' AND empresa_id = '$empresa'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;
		}			

		protected function edit_productos_modelo($datos){
			if($datos['cargarLogo'] === true) {
				$update = "UPDATE productos
				SET
					nombre = '".$datos['nombre']."',
					descripcion = '".$datos['descripcion']."',
					precio_compra = '".$datos['precio_compra']."',
					porcentaje_venta = '".$datos['porcentaje_venta']."',
					precio_venta = '".$datos['precio_venta']."',
					precio_mayoreo = '".$datos['precio_mayoreo']."',
					estado = '".$datos['estado']."',
					isv_venta = '".$datos['isv_venta']."',
					isv_compra = '".$datos['isv_compra']."',
					file_name = '".$datos['file']."'			
				WHERE productos_id = '".$datos['productos_id']."'";
			}else{
				$update = "UPDATE productos
				SET
					nombre = '".$datos['nombre']."',
					descripcion = '".$datos['descripcion']."',
					precio_compra = '".$datos['precio_compra']."',
					porcentaje_venta = '".$datos['porcentaje_venta']."',
					precio_venta = '".$datos['precio_venta']."',
					precio_mayoreo = '".$datos['precio_mayoreo']."',
					estado = '".$datos['estado']."',
					isv_venta = '".$datos['isv_venta']."',
					isv_compra = '".$datos['isv_compra']."'			
				WHERE productos_id = '".$datos['productos_id']."'";
			}

			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}

		protected function edit_bodega_productos_modelo($datos){
			$update = "UPDATE productos
			SET				
				almacen_id = '".$datos['bodega']."'			
			WHERE productos_id = '".$datos['productos_id']."'";
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			return $sql;			
		}
		
		protected function delete_productos_modelo($productos_id){
			$delete = "DELETE FROM productos WHERE productos_id = '$productos_id'";
			$sql = mainModel::connection()->query($delete) or die(mainModel::connection()->error);
		
			return $sql;			
		}

		protected function valid_productos_movimientos($productos_id){
			$query = "SELECT movimientos_id  
				FROM movimientos 
				WHERE productos_id = '$productos_id'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;			
		}		
		
		protected function consultar_codigo_producto($producto){
			$query = "SELECT productos_id
				FROM productos
				WHERE nombre = '$producto'";
				
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			
			return $sql;					
		}

		protected function consultar_productos_superior($id_producto){
			$query = "SELECT productos.productos_id
			FROM productos
			WHERE id_producto_superior = '$id_producto'";
				
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
			return $sql;					
		}
		
		protected function tipo_producto_modelo($productos_id){
			$result = mainModel::getTipoProducto($productos_id);
			
			return $result;			
		}	
		
		protected function getTotalProductosRegistrados() {
			try {
				// Obtener conexión a la base de datos
				$conexion = $this->connection();
				
				// Consulta SQL para contar clientes activos (ajusta según tu esquema de BD)
				$query = "SELECT COUNT(productos_id) AS total FROM productos WHERE estado = 1";
				
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