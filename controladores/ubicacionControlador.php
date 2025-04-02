<?php
    if($peticionAjax){
        require_once "../modelos/ubicacionModelo.php";
    }else{
        require_once "./modelos/ubicacionModelo.php";
    }
	
	class ubicacionControlador extends ubicacionModelo{
		public function agregar_ubicacion_controlador(){
			$ubicacion = mainModel::cleanStringConverterCase($_POST['ubicacion_ubicacion']);
			$empresa = mainModel::cleanStringConverterCase($_POST['empresa_ubicacion']);
			$estado = 1;
			
			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"ubicacion" => $ubicacion,
				"empresa" => $empresa,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultUbicacion = ubicacionModelo::valid_ubicacion_modelo($ubicacion);
			
			if($resultUbicacion->num_rows==0){
				$query = ubicacionModelo::agregar_ubicacion_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formUbicacion",
						"id" => "pro_ubicacion",
						"valor" => "Registro",	
						"funcion" => "listar_ubicacion();getEmpresaUbicacion();",
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
		
		public function edit_ubicacion_controlador(){
			$ubicacion_id = $_POST['ubicacion_id'];
			$ubicacion = mainModel::cleanStringConverterCase($_POST['ubicacion_ubicacion']);
			
			if (isset($_POST['ubicacion_activo'])){
				$estado = $_POST['ubicacion_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"ubicacion_id" => $ubicacion_id,
				"ubicacion" => $ubicacion,
				"estado" => $estado,				
			];	

			$query = ubicacionModelo::edit_ubicacion_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formUbicacion",	
					"id" => "pro_ubicacion",
					"valor" => "Editar",
					"funcion" => "listar_ubicacion();getEmpresaUbicacion();",
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
		
		public function delete_ubicacion_controlador(){
			$ubicacion_id = $_POST['ubicacion_id'];
			
			$result_valid_ubicacion_almacen_modelo = ubicacionModelo::valid_ubicacion_almacen_modelo($ubicacion_id);
			
			if($result_valid_ubicacion_almacen_modelo->num_rows==0 ){
				$query = ubicacionModelo::delete_ubicacion_modelo($ubicacion_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formUbicacion",	
						"id" => "pro_ubicacion",
						"valor" => "Eliminar",
						"funcion" => "listar_ubicacion();getEmpresaUbicacion();",
						"modal" => "modal_ubicacion",
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