<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['monto_efectivoPurchase']) && isset($_POST['efectivo_Purchase'])){
		require_once "../controladores/pagoCompraControlador.php";
		$insVarios = new pagoCompraControlador();
		
		echo $insVarios->agregar_pago_compra_controlador_efectivo();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['monto_efectivoPurchase'])) $missingFields[] = "Monto Efectivo";
		if (!isset($_POST['efectivo_Purchase'])) $missingFields[] = "Efectivo Bill";
	
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