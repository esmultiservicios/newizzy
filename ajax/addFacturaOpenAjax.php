<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	if(isset($_POST['cliente_id']) && isset($_POST['cliente']) && isset($_POST['fecha']) && isset($_POST['colaborador_id']) && isset($_POST['colaborador'])){
		require_once "../controladores/facturasControlador.php";
		$insVarios = new facturasControlador();
		
		echo $insVarios->agregar_facturas_open_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['cliente_id'])) $missingFields[] = "ID del Cliente";
		if (!isset($_POST['cliente'])) $missingFields[] = "Nombre del Cliente";
		if (!isset($_POST['fecha'])) $missingFields[] = "Fecha de la Factura";
		if (!isset($_POST['colaborador_id'])) $missingFields[] = "ID del Colaborador";
		if (!isset($_POST['colaborador'])) $missingFields[] = "Nombre del Colaborador";
	
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