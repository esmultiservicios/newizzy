<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['banco_id']) && isset($_POST['confbanco'])){
		require_once "../controladores/bancoControlador.php";
		$insVarios = new bancoControlador();
		
		echo $insVarios->edit_banco_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['banco_id'])) $missingFields[] = "ID del Banco";
		if (!isset($_POST['confbanco'])) $missingFields[] = "Confirmar Banco";
	
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