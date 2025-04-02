<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$datos = array();	
	$db = $GLOBALS['db'];

	$mysqliCodCliente = $insMainModel->connectionDBLocal($db);
	
	$query = "SELECT codigo_cliente FROM server_customers WHERE db = '$db'";

	$resultCodCliente = $mysqliCodCliente->query($query) or die($mysqliCodCliente->error);

	if($resultCodCliente->num_rows>0){
		$consulta2 = $resultCodCliente->fetch_assoc();
		$datos = array(
			0 => $consulta2['codigo_cliente'], 					
		);
	}
	
	echo json_encode($datos);
?>