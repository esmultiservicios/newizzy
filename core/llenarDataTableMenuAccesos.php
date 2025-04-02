<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$privilegio_id = $_POST['privilegio_id_accesos'];

	$result = $insMainModel->getMenuAccesosDataTable($privilegio_id);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"menu"=>$row['menu'],
			"privilegio"=>$row['privilegio'],
			"acceso_menu_id"=>$row['acceso_menu_id'],
			"privilegio_id"=>$row['privilegio_id'],
			"menu_id"=>$row['menu_id'],			
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