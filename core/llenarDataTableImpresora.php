<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();

	$result = $insMainModel->consultaImpresora();
	
	$arreglo = array();
	$data = array();
    $activo = '';
	$tipo = 0;

	if(isset($_POST['id'])){
		$tipo = $_POST['id'];
	}
	
	while($row = $result->fetch_assoc()){	
        $activo = ($row['estado'] == '1')? 'Activado': 'Desactivado';    	

		$data[] = array( 
			"impresora_id"=>$row['impresora_id'],
			"descripcion"=>$row['descripcion'],
			"activo"=> $activo,
			"estado"=> $row['estado'],
			"tipo" => $row['tipo']	
		);	
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	if($tipo == 1){
		echo json_encode($data);
	}else{
		echo json_encode($arreglo);
	}
?>	