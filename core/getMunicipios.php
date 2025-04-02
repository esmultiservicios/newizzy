<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$departamentos_id = $_POST['departamentos_id'];
	$result = $insMainModel->getMunicipios($departamentos_id);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['municipios_id'].'">'.$consulta2['nombre'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}
?>	