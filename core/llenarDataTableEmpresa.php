<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	require_once "Database.php";
	
	$insMainModel = new mainModel();	
	
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
	
	$result = $insMainModel->getEmpresa($datos);

	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"empresa_id"=>$row['empresa_id'],
			"razon_social"=>$row['razon_social'],
			"nombre"=>$row['nombre'],
			"telefono"=>$row['telefono'],
			"correo"=>$row['correo'],
			"rtn"=>$row['rtn'],
			"ubicacion"=>$row['ubicacion'],		
			"image"=>$row['logotipo']
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