<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['usu_forgot'])){
		require_once "../controladores/cambiarContraseñaControlador.php";
		$insVarios = new cambiarContraseñaControlador();
		
		echo $insVarios->resetear_contraseña_login_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['usu_forgot'])) $missingFields[] = "ID del Usuario";
	
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