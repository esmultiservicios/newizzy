<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	require_once "Database.php";
	
	$insMainModel = new mainModel();
	$database = new Database();
		
	$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;
	$result = $insMainModel->getClientes($estado);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){		
		$data[] = array( 
			"clientes_id"=>$row['clientes_id'],
			"cliente"=>$row['cliente'],
			"rtn"=>$row['rtn'],
			"localidad"=>$row['localidad'],
			"telefono"=>$row['telefono'],
			"correo"=>$row['correo'],
			"departamento"=>$row['departamento'],
			"municipio"=>$row['municipio'],
			"sistema"=>$row['db_values'],
			"eslogan" => $row['eslogan'],
			"otra_informacion" => $row['otra_informacion'],
			"whatsapp" => $row['whatsapp'],
			"empresa" => $row['empresa'],
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);