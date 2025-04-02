<?php
    if($peticionAjax){
        require_once "../modelos/impuestosModelo.php";
    }else{
        require_once "./modelos/impuestosModelo.php";
    }
	
	class impuestosControlador extends impuestosModelo{
		public function edit_impuestos_controlador(){
			$isv_id = $_POST['isv_id'];
			$valor = $_POST['valor'];			
			
			$datos = [
				"isv_id" => $isv_id,
				"valor" => $valor,				
			];		

			$query = impuestosModelo::edit_impuestos_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formImpuestos",	
					"id" => "pro_impuestos",
					"valor" => "Editar",
					"funcion" => "listar_impuestos_contabilidad();",
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
	}
?>	