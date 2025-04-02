<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";
	
	$insMainModel = new mainModel();
	
	$egresos_id = $_POST['egresos_id'];
	$result = $insMainModel->getEgresosEdit($egresos_id);
	$valores2 = $result->fetch_assoc();
	
	$datos = array(
		0 => $valores2['proveedores_id'],
		1 => $valores2['cuentas_id'],
		2 => $valores2['empresa_id'],
		3 => $valores2['fecha'],
		4 => $valores2['factura'],
		5 => $valores2['subtotal'],
		6 => $valores2['impuesto'],
		7 => $valores2['descuento'],
		8 => $valores2['nc'],
		9 => $valores2['total'],
	    10 => $valores2['observacion']		
	);
	echo json_encode($datos);
	?>