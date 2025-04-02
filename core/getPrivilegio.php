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
		"DB_MAIN" => $_SESSION['db_cliente'],		
	];	

	$result = $insMainModel->getPrivilegio($datos);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo '<option value="'.$consulta2['privilegio_id'].'">'.$consulta2['nombre'].'</option>';
		}
	}else{
		echo '<option value="">No hay datos que mostrar</option>';
	}