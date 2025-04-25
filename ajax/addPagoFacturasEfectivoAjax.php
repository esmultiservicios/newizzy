<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['monto_efectivo']) && isset($_POST['efectivo_bill'])){
		require_once "../controladores/pagoFacturaControlador.php";
		$insVarios = new pagoFacturaControlador();
		
		echo $insVarios->agregar_pago_factura_controlador_efectivo();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['monto_efectivo'])) $missingFields[] = "Monto Efectivo";
		if (!isset($_POST['efectivo_bill'])) $missingFields[] = "Efectivo Bill";
	
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