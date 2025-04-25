<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['isv_id']) && isset($_POST['valor'])){
		require_once "../controladores/impuestosControlador.php";
		$insVarios = new impuestosControlador();
		
		echo $insVarios->edit_impuestos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['isv_id'])) $missingFields[] = "ID del Impuesto";
		if (!isset($_POST['valor'])) $missingFields[] = "Valor del Impuesto";
	
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