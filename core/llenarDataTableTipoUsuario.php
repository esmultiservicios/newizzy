<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$datos = [
		"privilegio_id" => $_SESSION['privilegio_sd'],
		"colaborador_id" => $_SESSION['colaborador_id_sd'],	
		"db_cliente" => $_SESSION['db_cliente']
	];	

	$result = $insMainModel->getTipoUsuario($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"tipo_user_id"=>$row['tipo_user_id'],
			"nombre"=>$row['nombre']		  
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