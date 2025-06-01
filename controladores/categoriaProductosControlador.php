<?php
    if($peticionAjax){
        require_once "../modelos/categoriaProductosModelo.php";
    }else{
        require_once "./modelos/categoriaProductosModelo.php";
    }
	
	class categoriaProductosControlador extends categoriaProductosModelo{
		public function agregar_categoria_productos_controlador(){
			// Validar sesión primero
			$validacion = mainModel::validarSesion();
			if($validacion['error']) {
				return mainModel::showNotification([
					"title" => "Error de sesión",
					"text" => $validacion['mensaje'],
					"type" => "error",
					"funcion" => "window.location.href = '".$validacion['redireccion']."'"
				]);
			}
			
			$categoria_productos = mainModel::cleanStringConverterCase($_POST['categoria_productos']);
			$tipo_user_id_sd = $_SESSION['tipo_user_id_sd'];
			$estado = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $categoria_productos,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];

			if(categoriaProductosModelo::valid_categoria_productos_modelo($categoria_productos)->num_rows > 0){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo registrar la categoria de producto",                
				]);                
			}

			$mainModel = new mainModel();
			$planConfig = $mainModel->getPlanConfiguracionMainModel();
			
			// Solo evaluar si existe configuración de plan
			if (isset($planConfig['categorias'])) {
				$limiteCategorias = (int)$planConfig['categorias']; // No usamos ?? 0 aquí para no convertir "no definido" en 0
				
				// Caso 1: Límite es 0 (bloquear)
				if ($limiteCategorias === 0) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Acceso restringido",
						"text" => "Su plan actual no permite registrar categorias de productos."
					]);
				}
				
				// Caso 2: Si tiene límite > 0, validar disponibilidad
				$totalRegistrados = (int)categoriaProductosModelo::getTotalCategoriasRegistrados();
				
				if ($totalRegistrados >= $limiteCategorias) {
					return $mainModel->showNotification([
						"type" => "error",
						"title" => "Límite alcanzado",
						"text" => "Límite de categorias de productos alcanzado (Máximo: $limiteCategorias). Actualiza tu plan."
					]);
				}
			}

			if(!categoriaProductosModelo::agregar_categoria_productos_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar la categoria de producto",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Categoria de producto registrada correctamente",           
				"form" => "formCategoriaProductos",
				"funcion" => "listar_categoria_productos();"
			]);
		}
		
		public function edit_categoria_productos_controlador(){
			$categoria_id = $_POST['categoria_id'];
			$categoria_productos = mainModel::cleanStringConverterCase($_POST['categoria_productos']);
			
			if (isset($_POST['categoria_producto_activo'])){
				$estado = $_POST['categoria_producto_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"categoria_id" => $categoria_id,
				"nombre" => $categoria_productos,
				"estado" => $estado				
			];	

			if(!categoriaProductosModelo::edit_categoria_productos_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo actualizar la categoria de producto",
					"type" => "error"
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Registro exitoso",
				"text" => "Categoria de producto actualizada correctamente",           
				"form" => "formCategoriaProductos",
				"funcion" => "listar_categoria_productos();"
			]);
		}
		
		public function delete_categoria_productos_controlador(){
			$categoria_id = $_POST['categoria_id'];
			
			$campos = ['categoria_id'];
			$tabla = "categoria_productos";
			$condicion = "categoria_id = {$categoria_id}";

			$categoria_productos = mainModel::consultar_tabla($tabla, $campos, $condicion);
			
			if (empty($categoria_productos)) {
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "Categoria de producto no encontrado"
				]);
				exit();
			}
			
			$nombre = $categoria_productos[0]['nombre'] ?? '';

			// VALIDAMOS QUE EL PRODCUTO NO TENGA MOVIMIENTOS, PARA PODER ELIMINARSE
			if(categoriaProductosModelo::valid_categoria_productos_modelo($categoria_id)->num_rows > 0){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "No se puede eliminar",
					"message" => "La categoria de producto {$nombre} tiene productos asociados"
				]);
				exit();                
			}

			if(!categoriaProductosModelo::delete_categoria_productos_modelo($categoria_id)){
				header('Content-Type: application/json');
				echo json_encode([
					"status" => "error",
					"title" => "Error",
					"message" => "No se pudo eliminar la categoria de producto {$nombre}"
				]);
				exit();
			}
			
			header('Content-Type: application/json');
			echo json_encode([
				"status" => "success",
				"title" => "Eliminado",
				"message" => "Categoria de producto {$nombre} eliminada correctamente"
			]);
			exit();			
		}
	}