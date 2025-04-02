<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = [
		"estado" => $_POST['estado'],	
		"colaboradores_id" => $_POST['colaborador'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],
	];	

	$result = $insMainModel->getAsistencia($datos);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"asistencia_id"=>$row['asistencia_id'],
			"colaboradores_id"=>$row['colaboradores_id'],
			"colaborador"=>$row['colaborador'],
			"fecha"=>$row['fecha'],
			"hora_entrada"=>$row['hora_entrada'],
			"hora_salida"=>$row['hora_salida'],
			"horai"=>$row['horai'],
			"horaf"=>$row['horaf'],
			"horat"=>$row['total_horas'],
			"comentario"=>$row['comentario']									
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