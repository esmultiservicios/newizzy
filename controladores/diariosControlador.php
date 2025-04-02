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

			$query = diariosModelo::edit_diarios_modelo($datos);
			
			if($query){				
				$alert = [
					"alert" => "edit",
					"title" => "Registro modificado",
					"text" => "El registro se ha modificado correctamente",
					"type" => "success",
					"btn-class" => "btn-primary",
					"btn-text" => "¡Bien Hecho!",
					"form" => "formConfCuentasEntidades",	
					"id" => "pro_ConfCuentasEntidades",
					"valor" => "Editar",
					"funcion" => "listar_diarios_configuracion();getCuentaDiarios();",
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