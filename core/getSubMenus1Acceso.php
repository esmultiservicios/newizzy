<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['privilegio_id'];
	$result = $insMainModel->getSubMenus1Acceso($privilegio_id);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['submenu_id'].'">'.$consulta2['submenu'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}