<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['privilegio_id'];
		
	$result = $insMainModel->getPrivilegiosAccesoSubMenu($privilegio_id);
	
	$arreglo = array();

	while($data = $result->fetch_assoc()){				
		$arreglo[] = $data;		
	}
	
	echo json_encode($arreglo);