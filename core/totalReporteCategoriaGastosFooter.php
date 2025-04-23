<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();
	
	$datos = [
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],		
	];		
	
	$query = "SELECT cg.nombre AS 'categoria', SUM(e.total) As 'monto'
        FROM egresos AS e
        INNER JOIN categoria_gastos AS cg
        ON e.categoria_gastos_id = cg.categoria_gastos_id
        WHERE CAST(e.fecha_registro AS DATE) BETWEEN '".$datos['fechai']."' AND '".$datos['fechaf']."' AND e.estado = 1
        ORDER BY e.fecha_registro DESC";
    $result = $insMainModel->consulta_total_ingreso($query);
    $row = $result->fetch_assoc();

    echo json_encode($row);