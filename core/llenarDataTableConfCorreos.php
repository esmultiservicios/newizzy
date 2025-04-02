<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->getCorreo();
	
	$arreglo = array();
	$data = array();	
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"correo_id"=>$row['correo_id'],
			"tipo_correo"=>$row['tipo_correo'],
			"server"=>$row['server'],
			"correo"=>$row['correo'],
			"port"=>$row['port'],
			"smtp_secure"=>$row['smtp_secure']		  
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