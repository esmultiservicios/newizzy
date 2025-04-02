<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$colaborador_id = $_POST['colaborador_id'];
	$result = $insMainModel->getHoraInicio($colaborador_id);
	
	$horaInicio = "Hora Entrada ";
	
	if($result->num_rows>0){
		$consulta2 = $result->fetch_assoc();
		if($consulta2['horai'] != ""){
			$horaInicio = "Hora Salida ";
		}		
	}

	$datos = array(
		0 => $horaInicio,
	);	

	echo json_encode($datos);
?>	