<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$compras_id  = $_POST['compras_id'];
	
	$result = $insMainModel->saldo_cuentas_por_pagar($compras_id);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo $consulta2['saldo'];
		}
	}
	