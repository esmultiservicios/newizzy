<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	require_once "Database.php";
	
	$insMainModel = new mainModel();

	if(!isset($_SESSION['user_sd'])){ 
		session_start(['name'=>'SD']); 
	}
	
	$database = new Database();
	
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

	$result = $insMainModel->getAlmacen($datos);
	
	$arreglo = array();
	$data = array();
	$facturar_cero = 'No';

	while($row = $result->fetch_assoc()){
		if($row['facturar_cero'] == 1){
			$facturar_cero = 'Si';
		}elseif($row['facturar_cero'] == 0){
			$facturar_cero = 'No';
		}
		$data[] = array( 
			"almacen_id"=>$row['almacen_id'],
			"empresa"=>$row['empresa'],
			"facturarCero"=>$facturar_cero,
			"almacen"=>$row['almacen'],

			"ubicacion"=>$row['ubicacion']		  
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