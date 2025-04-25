<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['medidas_medidas']) && isset($_POST['descripcion_medidas'])){
		require_once "../controladores/medidasControlador.php";
		$insVarios = new medidasControlador();
		
		echo $insVarios->agregar_medidas_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['medidas_medidas'])) $missingFields[] = "Medida";
		if (!isset($_POST['descripcion_medidas'])) $missingFields[] = "Descripción";
	
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