<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$asistencia_id = $_POST['asistencia_id'];
	$result = $insMainModel->getAsistenciaId($asistencia_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['colaboradores_id'],
		1 => $valores2['fecha'],
		2 => $valores2['horai'],
		3 => $valores2['horaf'],
		4 => $valores2['estado'],
		5 => $valores2['comentario'],				
	);
	echo json_encode($datos);
?>	