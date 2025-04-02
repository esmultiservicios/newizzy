<?php	
	$peticionAjax = true;
	if(!isset($_SESSION['user_sd'])){ 
	   session_start(['name'=>'SD']); 
	}
	
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$colaborador_id = $_SESSION['colaborador_id_sd'];

	$result = $insMainModel->getUserSession($colaborador_id);
	
	if($result->num_rows>0){
		$consulta2 = $result->fetch_assoc();
		$nombre_ = explode(" ", trim(ucwords(strtolower($consulta2['nombre']), " ")));
		$nombre_usuario = $nombre_[0];
		$apellido_ = explode(" ", trim(ucwords(strtolower($consulta2['apellido']), " ")));	
		$nombre_apellido = $apellido_[0];
  
		$usuario_sistema_nombre = $nombre_usuario." ".$nombre_apellido;
		echo $usuario_sistema_nombre;
	}
?>