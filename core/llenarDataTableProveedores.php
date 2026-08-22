<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$estado = isset($_POST['estado']) ? trim((string)$_POST['estado']) : '';

	// Si el select todavía no había cargado, el navegador podía enviar estado="".
	// En ese caso se utiliza Activo (1), que es el filtro predeterminado de la vista.
	if ($estado === '') {
		$estado = 1;
	}
	$result = $insMainModel->getProveedores($estado);
	
	$arreglo = array();
	$data = array();
	
	while($row = $result->fetch_assoc()){				
		$data[] = array( 
			"proveedores_id"=>$row['proveedores_id'],
			"proveedor"=>$row['proveedor'],
			"rtn"=>$row['rtn'],
			"localidad"=>$row['localidad'],
			"telefono"=>$row['telefono'],
			"correo"=>$row['correo'],
			"departamento"=>$row['departamento'],
			"municipio"=>$row['municipio'],
			"estado"=>$row['estado']
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);
