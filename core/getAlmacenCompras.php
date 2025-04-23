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
	
	$datos = [	
		"empresa_id" => $_SESSION['empresa_id_sd'],
		"privilegio_colaborador" => $_SESSION['privilegio_sd']	
	];

	$result = $insMainModel->getAlmacen($datos);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['almacen_id'].'">'.$consulta2['almacen'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}