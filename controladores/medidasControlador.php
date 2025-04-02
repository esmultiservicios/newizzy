<?php
    if($peticionAjax){
        require_once "../modelos/medidasModelo.php";
    }else{
        require_once "./modelos/medidasModelo.php";
    }
	
	class medidasControlador extends medidasModelo{
		public function agregar_medidas_controlador(){
			$medidas_medidas = mainModel::cleanStringConverterCase($_POST['medidas_medidas']);
			$descripcion_medidas = mainModel::cleanStringConverterCase($_POST['descripcion_medidas']);
			$estado = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"medidas_medidas" => $medidas_medidas,
				"descripcion_medidas" => $descripcion_medidas,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultMedidas = medidasModelo::valid_medidas_modelo($medidas_medidas);
			
			if($resultMedidas->num_rows==0){
				$query = medidasModelo::agregar_medidas_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "edit",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formMedidas",
						"id" => "pro_ubicacion",
						"valor" => "Registro",	
						"funcion" => "listar_medidas();",
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
		
		public function edit_medidas_controlador(){
			$medida_id = $_POST['medida_id'];
			$medidas_medidas = mainModel::cleanStringConverterCase($_POST['medidas_medidas']);
			$descripcion_medidas = mainModel::cleanStringConverterCase($_POST['descripcion_medidas']);
			
			if (isset($_POST['medidas_activo'])){
				$estado = $_POST['medidas_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"medida_id" => $medida_id,
				"medidas_medidas" => $medidas_medidas,
				"descripcion_medidas" => $descripcion_medidas,
				"estado" => $estado,				
			];	

			$query = medidasModelo::edit_medidas_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formMedidas",	
					"id" => "pro_ubicacion",
					"valor" => "Editar",
					"funcion" => "listar_medidas();",
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
		
		public function delete_medidas_controlador(){
			$medida_id = $_POST['medida_id'];
			
			$result_valid_medidas_producto_modelo = medidasModelo::valid_medidas_producto_modelo($medida_id);
			
			if($result_valid_medidas_producto_modelo->num_rows==0 ){
				$query = medidasModelo::delete_medidas_modelo($medida_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formMedidas",	
						"id" => "pro_medidas",
						"valor" => "Eliminar",
						"funcion" => "listar_medidas();",
						"modal" => "modal_medidas",
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