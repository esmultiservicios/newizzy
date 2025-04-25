<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	require_once "../core/mensajes.php"; // Incluye el archivo de mensajes
	
	if (isset($_POST['medida']) && isset($_POST['producto']) && isset($_POST['precio_venta']) ) {
		require_once "../controladores/productosControlador.php";
		$insVarios = new ProductosControlador();

		echo $insVarios->agregar_productos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['medida'])) $missingFields[] = "Medida";
		if (!isset($_POST['producto'])) $missingFields[] = "Producto";
		if (!isset($_POST['precio_venta'])) $missingFields[] = "Precio de Venta";
	
		// Preparar el mensaje
		$missingText = implode(", ", $missingFields);
		$title = "Error 🚨";
		$message = "Faltan los siguientes campos: $missingText. Por favor, corrígelos.";
		
		// Escapar comillas para JavaScript
		$title = addslashes($title);
		$message = addslashes($message);
		
		// Llamar a TU función showNotify exactamente como está definida
		echo "<script>
			showNotify('error', '$title', '$message');
		</script>";
	}	