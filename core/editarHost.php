<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$host_id  = $_POST['host_id'];
	$result = $insMainModel->getHostEdit($host_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['clientes_id'],
		1 => $valores2['planes_id'],	
		2 => $valores2['server'],
		3 => $valores2['db'],	
		4 => $valores2['user'],
		5 => $valores2['pass'],
		6 => $valores2['estado'],										
	);
	echo json_encode($datos);
	?>