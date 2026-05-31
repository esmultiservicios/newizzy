<?php	
	//addDestinatario.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['correo']) && isset($_POST['nombre'])){
		require_once "../controladores/correoControlador.php";
		$insVarios = new correoControlador();
		
		echo $insVarios->registrar_destinatarios_correo_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['correo'])) $missingFields[] = "Correo";
		if (!isset($_POST['nombre'])) $missingFields[] = "Nombre";
	
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