<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['permisos_tipo_user_id'])){
		require_once "../controladores/tipoUsuarioAccesosControlador.php";
		$insVarios = new tipoUsuarioAccesosControlador();
		
		echo $insVarios->agregar_tipoUsuarioAccesos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['permisos_tipo_user_id'])) $missingFields[] = "ID del Tipo de Usuario";
	
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