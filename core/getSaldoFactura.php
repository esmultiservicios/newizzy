<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	$factura_id  = $_POST['facturas_id'];
	
	$result = $insMainModel->saldo_factura_cuentas_por_cobrar($factura_id);
	
	if($result->num_rows>0){
		while($consulta2 = $result->fetch_assoc()){
			 echo $consulta2['saldo'];
		}
	}
	