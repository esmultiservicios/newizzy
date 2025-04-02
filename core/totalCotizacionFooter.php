<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();

	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];		

		$query = "SELECT IFNULL(SUM(cd.cantidad*cd.precio),0.00) AS 'subtotal', IFNULL(SUM(cd.isv_valor),0.00) AS 'impuesto', IFNULL(SUM(cd.descuento),0.00) AS 'descuento', IFNULL(SUM((cd.cantidad*cd.precio) + cd.isv_valor - cd.descuento),0.00) AS 'total'
		FROM cotizacion AS c
		INNER JOIN cotizacion_detalles AS cd
		ON c.cotizacion_id = cd.cotizacion_id
		WHERE CAST(c.fecha AS DATE) BETWEEN '".$datos['fechai']."' AND '".$datos['fechaf']."' AND c.estado = 1
		ORDER BY c.fecha DESC";

    $result = $insMainModel->consulta_total_gastos($query);

    $row = $result->fetch_assoc();

    echo json_encode($row);
?>	