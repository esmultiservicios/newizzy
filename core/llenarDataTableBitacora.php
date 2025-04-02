<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$fechai = $_POST['fechai'];
	$fechaf = $_POST['fechaf'];
	
	$result = $insMainModel->getBitacora($fechai, $fechaf);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){
		$data[] = array( 
			"bitacoraFecha"=>$row['bitacoraFecha'],
			"bitacoraHoraInicio"=>$row['bitacoraHoraInicio'],
			"bitacoraHoraFinal"=>$row['bitacoraHoraFinal'],
			"bitacoraTipo"=>$row['bitacoraTipo'],
			"colaborador"=>$row['colaborador']		  
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