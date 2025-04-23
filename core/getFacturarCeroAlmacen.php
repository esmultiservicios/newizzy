<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
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
    $estado = false;
	
	$almacen_id = $_POST['almacen_id'];	
	
	$result = $insMainModel->getAlmacenId($almacen_id);

	if($result->num_rows>0){
		$res = $result->fetch_assoc();
        if($res['facturar_cero']){
            $estado = true;		

        }
	}		
	
	echo json_encode($estado);