<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['ubicacion_ubicacion']) && isset($_POST['ubicacion_id'])){
		require_once "../controladores/ubicacionControlador.php";
		$insVarios = new ubicacionControlador();
		
		echo $insVarios->edit_ubicacion_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['ubicacion_ubicacion'])) $missingFields[] = "Ubicación";
		if (!isset($_POST['ubicacion_id'])) $missingFields[] = "ID de la Ubicación";
	
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