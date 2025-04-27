<?php	
	//llenarDataTableCobrarClientes.php
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	// Instanciar mainModel
	$insMainModel = new mainModel();

	// Validar sesión primero
	$validacion = $insMainModel->validarSesion();
	if($validacion['error']) {
		return $insMainModel->showNotification([
			"title" => "Error de sesión",
			"text" => $validacion['mensaje'],
			"type" => "error",
			"funcion" => "window.location.href = '".$validacion['redireccion']."'"
		]);
	}
	
	$datos = [
		"estado" => $_POST['estado'],
		"clientes_id" => $_POST['clientes_id'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],
		"empresa_id_sd" => $_SESSION['empresa_id_sd'],		
	];	

	$result = $insMainModel->getCuentasporCobrarClientes($datos);
	
	$arreglo = array();
	$data = array();
	$estadoColor = 'bg-warning';
	$credito = 0.00;
	$abono = 0.00;
	$saldo = 0.00;
	$totalCredito = 0;
	$totalAbono = 0;
	$totalPendiente = 0;

	while($row = $result->fetch_assoc()){
		$resultAbonos = $insMainModel->getAbonosCobrarClientes($row['facturas_id']);
		$rowAbonos = $resultAbonos->fetch_assoc();

		if ($rowAbonos['total'] != null || $rowAbonos['total'] != ""){
			$abono = $rowAbonos['total'];
		}else{
			$abono = 0.00;
		}

		$credito = $row['importe'];
		$saldo = $row['importe'] - $abono;

		$totalCredito += $credito;
		$totalAbono += $abono;
		$totalPendiente += $saldo;

		if($row['estado'] == 2){
			$estadoColor = 'bg-c-green';
		}else{
			$estadoColor = 'bg-c-yellow';
		}

		$data[] = array( 
			"cobrar_clientes_id"=>$row['cobrar_clientes_id'],
			"facturas_id"=>$row['facturas_id'],
			"fecha"=>$row['fecha'],
			"cliente"=> $row['cliente'],
			"numero"=>$row['numero'],
			"credito"=> $credito,
			"abono"=>$abono,						
			"saldo"=>$saldo,
			"color"=> $estadoColor,
			"estado"=>$row['estado'],
			"total_credito"=> $totalCredito,
			"total_abono"=>$totalAbono,
			"total_pendiente"=> $totalPendiente,
			"vendedor"=>$row['vendedor'],
		);		
	}
	
	$arreglo = array(
		"echo" => 1,
		"totalrecords" => count($data),
		"totaldisplayrecords" => count($data),
		"data" => $data
	);

	echo json_encode($arreglo);