<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";

	if(isset($_POST['productos_id']) && isset($_POST['producto']) && isset($_POST['descripcion']) && isset($_POST['precio_compra']) && isset($_POST['precio_venta'])){
		require_once "../controladores/productosControlador.php";
		$insVarios = new productosControlador();
		
		echo $insVarios->edit_productos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['productos_id'])) $missingFields[] = "ID del Producto";
		if (!isset($_POST['producto'])) $missingFields[] = "Nombre del Producto";
		if (!isset($_POST['descripcion'])) $missingFields[] = "Descripción del Producto";
		if (!isset($_POST['precio_compra'])) $missingFields[] = "Precio de Compra";
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
	