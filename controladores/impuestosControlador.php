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

			if(!impuestosModelo::edit_impuestos_modelo($datos)){
				return mainModel::showNotification([
					"title" => "Error",
					"text" => "No se pudo registrar el impuesto",
					"type" => "error"
				]);
			}
						
			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Impuesto actualizado correctamente",
				"funcion" => "listar_impuestos_contabilidad();"
			]);			
		}
	}	