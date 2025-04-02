<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$clientes_id = $_POST['clientes_id'];
	$result = $insMainModel->getPlanesConsultaCliente($clientes_id);
	$plan = "";

	if($result->num_rows>0){
		$consulta2 = $result->fetch_assoc();
		$plan = $consulta2['plan'];
	}

	echo $plan;
?>	