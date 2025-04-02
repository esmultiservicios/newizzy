<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class diariosModelo extends mainModel{
		protected function edit_diarios_modelo($datos){
			$update = "UPDATE diarios
			SET 
				cuentas_id = '".$datos['cuentas_id']."'
			WHERE diarios_id = '".$datos['diarios_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>	