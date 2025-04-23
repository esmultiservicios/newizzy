<?php
    if($peticionAjax){
        require_once "../modelos/diariosModelo.php";
    }else{
        require_once "./modelos/diariosModelo.php";
    }
	
	class diariosControlador extends diariosModelo{		
		public function edit_diarios_controlador(){
			$diarios_id = $_POST['diarios_id'];
			$cuentas_id = $_POST['confCuenta'];

			$datos = [
				"diarios_id" => $diarios_id,
				"cuentas_id" => $cuentas_id				
			];		

			if(!diariosModelo::edit_diarios_modelo($datos)){
				return mainModel::showNotification([
					"type" => "error",
					"title" => "Error",
					"text" => "No se pudo actualizar el diario",                
				]);
			}

			return mainModel::showNotification([
				"type" => "success",
				"title" => "Actualización exitosa",
				"text" => "Diario actualizado correctamente",
				"funcion" => "listar_diarios_configuracion();getCuentaDiarios();",
			]);
		}
	}