<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nomina_id']) && isset($_POST['nominad_empleados'])){
		require_once "../controladores/nominaControlador.php";
		$insVarios = new nominaControlador();
		
		echo $insVarios->agregar_nomina_detalles_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['nomina_id'])) $missingFields[] = "ID de la Nomina";
		if (!isset($_POST['nominad_empleados'])) $missingFields[] = "ID de los Empleados";
	
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