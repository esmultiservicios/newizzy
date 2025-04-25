<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nombre_clientes']) && isset($_POST['identidad_clientes']) && isset($_POST['fecha_clientes'])){
		require_once "../controladores/clientesControlador.php";
		$insVarios = new clientesControlador();
		
		echo $insVarios->agregar_clientes_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['nombre_clientes'])) $missingFields[] = "Nombre del cliente";
		if (!isset($_POST['identidad_clientes'])) $missingFields[] = "Identidad del cliente";
		if (!isset($_POST['fecha_clientes'])) $missingFields[] = "Fecha de nacimiento del cliente";
	
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