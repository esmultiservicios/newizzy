<?php	
	$peticionAjax = true;
	require_once "configGenerales.php";
	require_once "mainModel.php";

	$insMainModel = new mainModel();

	$datos = [
		"tipo_producto_id" => $_POST['tipo_producto_id'],
		"fechai" => $_POST['fechai'],
		"fechaf" => $_POST['fechaf'],
		"bodega" => $_POST['bodega'],
		"producto" => $_POST['producto'],
		"cliente" =>  $_POST['cliente'],

	];		

		$producto = '';
		$cliente = '';
		$tipo = '';

		if($datos['bodega'] != ''){
			$bodega = "AND bo.almacen_id = '".$datos['bodega']."'";
		}else{ $bodega = '';}

		if($datos['bodega'] == '0'){
			$bodega = '';
		}

		if($datos['producto'] != ''){
			$producto =  "AND p.productos_id = '".$datos['producto']."'";
		}

		if($datos['cliente'] != ''){
			$cliente =  "AND m.clientes_id = '".$datos['cliente']."'";
		}
		
		if($datos['tipo_producto_id'] != ''){
			$tipo = "AND p.tipo_producto_id = '".$datos['tipo_producto_id']."'";
		}
	

	$query = "SELECT
		IFNULL(SUM(m.cantidad_entrada),0.00) AS 'entrada',
		IFNULL(SUM(m.cantidad_salida),0.00) AS 'salida',
		IFNULL(SUM(m.cantidad_entrada) -	SUM(m.cantidad_salida),0.00) AS 'saldo'
		FROM
			movimientos AS m
		INNER JOIN productos AS p ON m.productos_id = p.productos_id
		INNER JOIN medida AS me ON p.medida_id = me.medida_id
		INNER JOIN almacen AS bo ON p.almacen_id = bo.almacen_id
		LEFT JOIN clientes AS cl ON cl.clientes_id = m.clientes_id
		WHERE
		CAST(m.fecha_registro AS DATE) BETWEEN '".$datos['fechai']."'
		AND '".$datos['fechaf']."'
		$bodega
		$tipo
		$cliente
		$producto
		";

    $result = $insMainModel->consulta_total_gastos($query);

    $row = $result->fetch_assoc();

    echo json_encode($row);
	?>