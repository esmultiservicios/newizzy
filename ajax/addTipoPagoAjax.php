<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['confTipoPago']) && isset($_POST['confCuentaTipoPago'])){
		require_once "../controladores/tipoPagoControlador.php";
		$insVarios = new tipoPagoControlador();
		
		echo $insVarios->agregar_tipo_pago_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['confTipoPago'])) $missingFields[] = "Tipo de Pago";
		if (!isset($_POST['confCuentaTipoPago'])) $missingFields[] = "Cuenta de Pago";
	
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