<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class impuestosModelo extends mainModel{				
		protected function edit_impuestos_modelo($datos){
			$update = "UPDATE isv
			SET 
				valor = '".$datos['valor']."'
				WHERE isv_id = '".$datos['isv_id']."'";
			
			$sql = mainModel::connection()->query($update) or die(mainModel::connection()->error);
			
			return $sql;			
		}
	}
?>