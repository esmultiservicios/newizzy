<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['almacen_id']) && isset($_POST['almacen_almacen'])){
		require_once "../controladores/almacenControlador.php";
		$insVarios = new almacenControlador();
		
		echo $insVarios->delete_almacen_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['almacen_id'])) $missingFields[] = "ID del Almacen";
		if (!isset($_POST['almacen_almacen'])) $missingFields[] = "Almacen";

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