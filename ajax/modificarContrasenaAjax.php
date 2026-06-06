<?php	
	//modificarContrasenaAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['contranaterior']) && isset($_POST['nuevacontra']) && isset($_POST['repcontra'])){
		require_once "../controladores/cambiarContraseñaControlador.php";
		$insVarios = new cambiarContraseñaControlador();
		
		echo $insVarios->edit_contraseña_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['contranaterior'])) $missingFields[] = "Contraseña Anterior";
		if (!isset($_POST['nuevacontra'])) $missingFields[] = "Nueva Contraseña";
		if (!isset($_POST['repcontra'])) $missingFields[] = "Repetir Contraseña";
	
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