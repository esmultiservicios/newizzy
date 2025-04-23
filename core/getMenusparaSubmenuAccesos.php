<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['privilegio_id'];
	$result = $insMainModel->getMenusparaSubmenuAccesos($privilegio_id);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['menu_id'].'">'.$consulta2['name'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}