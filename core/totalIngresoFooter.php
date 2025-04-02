<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();
	
	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];		

    $query = "SELECT IFNULL(sum(i.subtotal),0.00) as 'subtotal', IFNULL(sum(i.impuesto),0.00) AS 'impuesto', IFNULL(sum(i.descuento),0.00) AS 'descuento', 
                IFNULL(sum(i.nc),0.00) AS 'nc', IFNULL(sum(i.total),0.00) AS 'total'
				FROM ingresos AS i
				INNER JOIN cuentas AS c
				ON i.cuentas_id = c.cuentas_id
				INNER JOIN clientes AS cli
				ON i.clientes_id = cli.clientes_id
				WHERE CAST(i.fecha_registro AS DATE) BETWEEN '".$datos['fechai']."' AND '".$datos['fechaf']."' AND i.estado = 1
				ORDER BY i.fecha_registro DESC";

    $result = $insMainModel->consulta_total_gastos($query);
    $row = $result->fetch_assoc();

    echo json_encode($row);
?>	