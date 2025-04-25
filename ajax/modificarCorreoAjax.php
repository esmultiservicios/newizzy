<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['serverConfEmail']) && isset($_POST['correoConfEmail']) && isset($_POST['puertoConfEmail']) && isset($_POST['smtpSecureConfEmail']) && isset($_POST['passConfEmail'])){
		require_once "../controladores/correoControlador.php";
		$insVarios = new correoControlador();
		
		echo $insVarios->edit_correo_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['serverConfEmail'])) $missingFields[] = "Servidor de Correo";
		if (!isset($_POST['correoConfEmail'])) $missingFields[] = "Correo de Correo";
		if (!isset($_POST['puertoConfEmail'])) $missingFields[] = "Puerto de Correo";
		if (!isset($_POST['smtpSecureConfEmail'])) $missingFields[] = "SMTP Seguro de Correo";
		if (!isset($_POST['passConfEmail'])) $missingFields[] = "Contraseña de Correo";
	
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