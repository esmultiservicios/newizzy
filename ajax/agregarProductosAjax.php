<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	require_once "../core/mensajes.php"; // Incluye el archivo de mensajes
	
	if (isset($_POST['medida']) && isset($_POST['producto']) && isset($_POST['precio_venta']) ) {
		require_once "../controladores/productosControlador.php";
		$insVarios = new ProductosControlador();

		echo $insVarios->agregar_productos_controlador();
	} else {
		$missingFields = [];
	
		if (!isset($_POST['medida'])) {
			$missingFields[] = "medida del producto";
		}
	
		if (!isset($_POST['producto'])) {
			$missingFields[] = "nombre del producto";
		}
	
		if (!isset($_POST['precio_venta'])) {
			$missingFields[] = "precio de venta del producto";
		}
	
		$missingFieldsText = implode(", ", $missingFields);
		echo generarMensajeError('Error 🚨', $missingFieldsText);
	}	