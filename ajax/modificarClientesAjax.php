<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";

	if(isset($_POST['clientes_id']) && isset($_POST['nombre_clientes']) && isset($_POST['identidad_clientes']) && isset($_POST['dirección_clientes']) && isset($_POST['telefono_clientes']) && isset($_POST['correo_clientes'])){
		require_once "../controladores/clientesControlador.php";
		$insVarios = new clientesControlador();
		
		echo $insVarios->edit_clientes_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['clientes_id'])) $missingFields[] = "ID del Cliente";
		if (!isset($_POST['nombre_clientes'])) $missingFields[] = "Nombre del Cliente";
		if (!isset($_POST['identidad_clientes'])) $missingFields[] = "Identidad del Cliente";
		if (!isset($_POST['dirección_clientes'])) $missingFields[] = "Dirección del Cliente";
		if (!isset($_POST['telefono_clientes'])) $missingFields[] = "Teléfono del Cliente";
		if (!isset($_POST['correo_clientes'])) $missingFields[] = "Correo del Cliente";
	
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