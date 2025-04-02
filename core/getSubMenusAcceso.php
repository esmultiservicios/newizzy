<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$data = [
		"menu_id" => $_POST['menu_id']	
	];

	$result = $insMainModel->getSubMenusAcceso($data);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['submenu_id'].'">'.$consulta2['name'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}