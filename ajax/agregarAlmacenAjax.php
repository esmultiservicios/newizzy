<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['almacen_almacen']) && isset($_POST['ubicacion_almacen']) && isset($_POST['facturar_cero']) ){
		require_once "../controladores/almacenControlador.php";
		$insVarios = new almacenControlador();
		
		echo $insVarios->agregar_almacen_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['almacen_almacen'])) $missingFields[] = "Almacen";
		if (!isset($_POST['ubicacion_almacen'])) $missingFields[] = "Ubicación";
		if (!isset($_POST['facturar_cero'])) $missingFields[] = "Facturar Cero";
	
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