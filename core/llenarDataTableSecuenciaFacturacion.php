<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	require_once "Database.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}
	
	$database = new Database();
	
	$tablaPrivilegio = "privilegio";
	$camposPrivilegio = ["nombre"];
	$condicionesPrivilegio = ["privilegio_id" => $_SESSION['privilegio_sd']];
	$orderBy = "";
	$tablaJoin = "";
	$condicionesJoin = [];
	$resultadoPrivilegio = $database->consultarTabla($tablaPrivilegio, $camposPrivilegio, $condicionesPrivilegio, $orderBy, $tablaJoin, $condicionesJoin);

	$privilegio_colaborador = "";

	if (!empty($resultadoPrivilegio)) {
		$privilegio_colaborador = $resultadoPrivilegio[0]['nombre'];
	}

	$datos = [
		"privilegio_id" => $_SESSION['privilegio_sd'],
		"colaborador_id" => $_SESSION['colaborador_id_sd'],	
		"privilegio_colaborador" => $privilegio_colaborador,	
		"empresa_id" => $_SESSION['empresa_id_sd']	
	];	

	$result = $insMainModel->getSecuenciaFacturacion($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"secuencia_facturacion_id"=>$row['secuencia_facturacion_id'],
			"empresa"=>$row['empresa'],
			"documento"=>$row['documento'],
			"cai"=>$row['cai'],
			"prefijo"=>$row['prefijo'],
			"siguiente"=>$row['siguiente'],
			"rango_inicial"=>$row['rango_inicial'],
			"rango_final"=>$row['rango_final'],
			"prefijo"=>$row['prefijo'],
			"fecha_limite"=>$row['fecha_limite']			
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