<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$result = $insMainModel->getTotalBills();
	
	$totalBills = 0;
	
	if($result->num_rows>0){
		$consulta2 = $result->fetch_assoc();
		$totalBills = $consulta2['total'];
	}

	if ($totalBills !== null) {
		$formattedValue = number_format($totalBills, 2);
	} else {
		$formattedValue = 0;
	}

	echo $formattedValue;
?>