<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();
	
	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];		
	
	$query = "SELECT  IFNULL(sum(e.subtotal),0.00) as 'subtotal', IFNULL(sum(e.impuesto),0.00) AS 'impuesto', IFNULL(sum(e.descuento),0.00) AS 'descuento',
     IFNULL(sum(e.nc),0.00) AS 'nc', IFNULL(sum(e.total),0.00) AS 'total'
     FROM egresos AS e
        INNER JOIN cuentas AS c
        ON e.cuentas_id = c.cuentas_id
        INNER JOIN proveedores AS p
        ON e.proveedores_id = p.proveedores_id
        WHERE CAST(e.fecha_registro AS DATE) BETWEEN '".$datos['fechai']."' AND '".$datos['fechaf']."' AND e.estado = 1
        ORDER BY e.fecha_registro DESC";
    $result = $insMainModel->consulta_total_ingreso($query);
    $row = $result->fetch_assoc();

    echo json_encode($row);
?>	
   