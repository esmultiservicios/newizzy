<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$estado = isset($_POST['estado']) ? $_POST['estado'] : 1;
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
			"municipio"=>$row['municipio']			
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