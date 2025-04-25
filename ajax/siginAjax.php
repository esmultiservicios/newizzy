<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_GET['inputEmail']) && isset($_GET['inputPassword'])){
		require_once "../controladores/loginControlador.php";
		$login = new loginControlador();
		
		echo $login->iniciar_sesion_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_GET['inputEmail'])) $missingFields[] = "Email";
		if (!isset($_GET['inputPassword'])) $missingFields[] = "Password";
	
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