<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['movimientos_id']) && isset($_POST['movimiento_producto']) && isset($_POST['movimientos_tipo_producto_id']) && isset($_POST['movimiento_cantidad'])){
		require_once "../controladores/movimientoProductosControlador.php";
		$insVarios = new movimientoProductosControlador();
		
		echo $insVarios->agregar_movimiento_productos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['movimientos_id'])) $missingFields[] = "ID de la Caja";
	
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