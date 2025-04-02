<?php
    if($peticionAjax){
        require_once "../modelos/bancoModelo.php";
    }else{
        require_once "./modelos/bancoModelo.php";
    }
	
	class bancoControlador extends bancoModelo{
		public function agregar_banco_controlador(){
			$nombre = mainModel::cleanString($_POST['confbanco']);
			$estado = 1;

			$fecha_registro = date("Y-m-d H:i:s");	
			
			$datos = [
				"nombre" => $nombre,
				"estado" => $estado,
				"fecha_registro" => $fecha_registro,				
			];
			
			$resultPuestos = bancoModelo::valid_banco_modelo($nombre);
			
			if($resultPuestos->num_rows==0){
				$query = bancoModelo::agregar_banco_modelo($datos);
				
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro almacenado",
						"text" => "El registro se ha almacenado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formBancos",
						"id" => "pro_bancos",
						"valor" => "Registro",	
						"funcion" => "listar_banco_contabilidad();",
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
		
		public function edit_banco_controlador(){
			$banco_id = $_POST['banco_id'];
			$nombre = mainModel::cleanStringConverterCase($_POST['confbanco']);
			
			if (isset($_POST['confbanco_activo'])){
				$estado = $_POST['confbanco_activo'];
			}else{
				$estado = 2;
			}
			
			$datos = [
				"banco_id" => $banco_id,
				"nombre" => $nombre,
				"estado" => $estado,				
			];		

			$query = bancoModelo::edit_banco_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formBancos",	
					"id" => "pro_bancos",
					"valor" => "Editar",
					"funcion" => "listar_banco_contabilidad();",
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
		
		public function delete_banco_controlador(){
			$banco_id = $_POST['banco_id'];
			
			$result_valid_banco_pagos_modelo = bancoModelo::valid_banco_pagos_modelo($banco_id);
			
			if($result_valid_banco_pagos_modelo->num_rows==0 ){
				$query = bancoModelo::delete_banco_modelo($banco_id);
								
				if($query){
					$alert = [
						"alert" => "clear",
						"title" => "Registro eliminado",
						"text" => "El registro se ha eliminado correctamente",
						"type" => "success",
						"btn-class" => "btn-primary",
						"btn-text" => "¡Bien Hecho!",
						"form" => "formBancos",	
						"id" => "pro_bancos",
						"valor" => "Eliminar",
						"funcion" => "listar_banco_contabilidad();",
						"modal" => "modalConfBancos",
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