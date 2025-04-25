<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['diarios_id']) && isset($_POST['confCuenta'])){
		require_once "../controladores/diariosControlador.php";
		$insVarios = new diariosControlador();
		
		echo $insVarios->edit_diarios_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['diarios_id'])) $missingFields[] = "ID del Diario";
		if (!isset($_POST['confCuenta'])) $missingFields[] = "Confirmar Cuenta";
	
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