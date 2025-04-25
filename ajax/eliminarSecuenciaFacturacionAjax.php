<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['secuencia_facturacion_id'])){
		require_once "../controladores/secuenciaFacturacionControlador.php";
		$insVarios = new secuenciaFacturacionControlador();
		
		echo $insVarios->delete_secuencia_facturacion_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['secuencia_facturacion_id'])) $missingFields[] = "ID de la Secuencia de Facturación";
	
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