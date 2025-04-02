<?php
    if($peticionAjax){
        require_once "../core/mainModel.php";
    }else{
        require_once "./core/mainModel.php";	
    }
	
	class menuAccesosModelo extends mainModel{
		protected function agregar_menuAccesos_modelo($datos){
			$acceso_menu_id = mainModel::correlativo("acceso_menu_id", "acceso_menu");
			$insert = "INSERT INTO acceso_menu 
				VALUES('$acceso_menu_id','".$datos['menu_id']."','".$datos['privilegio_id']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);

			return $sql;
		}
		
		protected function agregar_subMenuAccesos_modelo($datos){
			$acceso_submenu_id = mainModel::correlativo("acceso_submenu_id", "acceso_submenu");
			$insert = "INSERT INTO acceso_submenu 
				VALUES('$acceso_submenu_id','".$datos['submenu_id']."','".$datos['privilegio_id']."','".$datos['estado']."','".$datos['fecha_registro']."')";

			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);
			
			return $sql;
		}
		
		protected function agregar_subMenu1Accesos_modelo($datos){
			$acceso_submenu1_id = mainModel::correlativo("acceso_submenu1_id", "acceso_submenu1");
			$insert = "INSERT INTO acceso_submenu1 
				VALUES('$acceso_submenu1_id','".$datos['submenus_id']."','".$datos['privilegio_id']."','".$datos['estado']."','".$datos['fecha_registro']."')";
			$sql = mainModel::connection()->query($insert) or die(mainModel::connection()->error);

			return $sql;
		}		
		
		protected function valid_menuAccesos_modelo($datos){
			$query = "SELECT acceso_menu_id FROM acceso_menu WHERE menu_id = '".$datos['menu_id']."' AND privilegio_id = '".$datos['privilegio_id']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			return $sql;
		}
		
		protected function valid_subMenuAccesos_modelo($datos){
			$query = "SELECT acceso_submenu_id FROM acceso_submenu WHERE submenu_id = '".$datos['submenu_id']."' AND privilegio_id = '".$datos['privilegio_id']."'";

			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);

			return $sql;
		}	

		protected function valid_sub1MenuAccesos_modelo($datos){
			$query = "SELECT acceso_submenu1_id FROM acceso_submenu1 WHERE submenu1_id = '".$datos['submenus_id']."' AND privilegio_id = '".$datos['privilegio_id']."'";
			$sql = mainModel::connection()->query($query) or die(mainModel::connection()->error);
		
			return $sql;
		}	
	}
?>	