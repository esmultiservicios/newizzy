<?php	
	//eliminarUsersAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['usuarios_id'])){
		require_once "../controladores/usuarioControlador.php";
		$insVarios = new usuarioControlador();
		
		echo $insVarios->delete_user_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['usuarios_id'])) $missingFields[] = "ID del Usuario";
	
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