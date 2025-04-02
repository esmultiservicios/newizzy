<?php
    if($peticionAjax){
        require_once "../modelos/puestosModelo.php";
    }else{
        require_once "./modelos/puestosModelo.php";
    }
	
	class puestosControlador extends puestosModelo{
		public function agregar_puestos_controlador(){
			$puesto = mainModel::cleanStringConverterCase($_POST['puesto']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"puesto" => $puesto,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultPuestos = puestosModelo::valid_puestos_modelo($puesto);
			
			if($resultPuestos->num_rows==0){
				$query = puestosModelo::agregar_puestos_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formPuestos",
						"id" => "proceso_puestos",
						"valor" => "Registro",	
						"funcion" => "listar_puestos();",
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

		public function edit_puestos_controlador(){
			$puestos_id = $_POST['puestos_id'];
			
			if(isset($_POST['puesto'])){//COMPRUEBO SI LA VARIABLE ESTA DIFINIDA
				if($_POST['puesto'] == ""){
					$puesto = 0;
				}else{
					$puesto = mainModel::cleanStringConverterCase($_POST['puesto']);
				}
			}else{
				$puesto = 0;
			}			
			
			if (isset($_POST['puestos_activo'])){
				$estado = $_POST['puestos_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"puestos_id" => $puestos_id,
				"puesto" => $puesto,
				"estado" => $estado,				
			];		

			$query = puestosModelo::edit_puestos_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formPuestos",	
					"id" => "proceso_puestos",
					"valor" => "Editar",
					"funcion" => "listar_puestos();",
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
		
		public function delete_puestos_controlador(){
			$puestos_id = $_POST['puestos_id'];
			
			$result_valid_puestos_colaborador_modelo = puestosModelo::valid_puestos_colaborador_modelo($puestos_id);
			
			if($result_valid_puestos_colaborador_modelo->num_rows==0 ){
				$query = puestosModelo::delete_puestos_modelo($puestos_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formPuestos",	
						"id" => "proceso_puestos",
						"valor" => "Eliminar",
						"funcion" => "listar_puestos();",
						"modal" => "modal_registrar_puestos",
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