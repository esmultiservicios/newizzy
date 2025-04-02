<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['privilegio_id_accesos'];

	$result = $insMainModel->getSubMenu1AccesosDataTable($privilegio_id);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"submenu"=>$row['submenu'],
			"submenu1"=>$row['submenu1'],
			"privilegio"=>$row['privilegio'],
			"acceso_submenu_id"=>$row['acceso_submenu_id'],	
			"submenu_id"=>$row['submenu_id'],
			"privilegio_id"=>$row['privilegio_id'],							
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);
?>	