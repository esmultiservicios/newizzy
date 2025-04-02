<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['permisos_tipo_user_id'];
	
	$result = $insMainModel->getTipoUsuariosAcceso($privilegio_id);
	
	$arreglo = array();

	while($data = $result->fetch_assoc()){				
		$arreglo[] = $data;		
	}
	
	echo json_encode($arreglo);
?>	