<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaboradores_id_apertura']) && isset($_POST['monto_apertura'])){
		require_once "../controladores/aperturaCajaControlador.php";
		$insVarios = new aperturaCajaControlador();
		
		echo $insVarios->agregar_apertura_caja_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['colaboradores_id_apertura'])) $missingFields[] = "ID del Colaborador";
		if (!isset($_POST['monto_apertura'])) $missingFields[] = "Monto de la Apertura";
	
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