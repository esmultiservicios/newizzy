<?php
    if($peticionAjax){
        require_once "../modelos/categoriaProductosModelo.php";
    }else{
        require_once "./modelos/categoriaProductosModelo.php";
    }
	
	class categoriaProductosControlador extends categoriaProductosModelo{
		public function agregar_categoria_productos_controlador(){
			if(!isset($_SESSION['user_sd'])){ 
				session_start(['name'=>'SD']); 
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
			
			$resultCategoria = categoriaProductosModelo::valid_categoria_productos_modelo($categoria_productos);
			
			if($resultCategoria->num_rows==0){
				$query = categoriaProductosModelo::agregar_categoria_productos_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formCategoriaProductos",
						"id" => "pro_categoria_productos",
						"valor" => "Registro",	
						"funcion" => "listar_categoria_productos();",
						"modal" => "",
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Resgistro ya existe",
					"text" => "Lo sentimos este registro ya existe",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);
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

			$query = categoriaProductosModelo::edit_categoria_productos_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formCategoriaProductos",	
					"id" => "pro_categoria_productos",
					"valor" => "Editar",
					"funcion" => "listar_categoria_productos();",
					"modal" => "",
				];
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Ocurrio un error inesperado",
					"text" => "No hemos podido procesar su solicitud",
					"type" => "error",
					"btn-class" => "btn-danger",					
				];				
			}			
			
			return mainModel::sweetAlert($alert);
		}
		
		public function delete_categoria_productos_controlador(){
			$categoria_id = $_POST['categoria_id'];
			
			$result_valid_categoria_productos_modelo = categoriaProductosModelo::valid_categoria_id_productos_modelo($categoria_id);
			
			if($result_valid_categoria_productos_modelo->num_rows==0 ){
				$query = categoriaProductosModelo::delete_categoria_productos_modelo($categoria_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formCategoriaProductos",	
						"id" => "pro_categoria_productos",
						"valor" => "Eliminar",
						"funcion" => "listar_categoria_productos();",
						"modal" => "modalcategoria_productos",
					];
				}else{
					$alert = [
						"alert" => "simple",
						"title" => "Ocurrio un error inesperado",
						"text" => "No hemos podido procesar su solicitud",
						"type" => "error",
						"btn-class" => "btn-danger",					
					];				
				}				
			}else{
				$alert = [
					"alert" => "simple",
					"title" => "Este registro cuenta con información almacenada",
					"text" => "No se puede eliminar este registro",
					"type" => "error",	
					"btn-class" => "btn-danger",						
				];				
			}
			
			return mainModel::sweetAlert($alert);			
		}
	}
?>	