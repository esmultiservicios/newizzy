<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['empresa_id']) && isset($_POST['empresa_empresa']) && isset($_POST['telefono_empresa']) && isset($_POST['correo_empresa']) && isset($_POST['rtn_empresa']) && isset($_POST['direccion_empresa'])){
		require_once "../controladores/empresaControlador.php";
		$insVarios = new empresaControlador();
		
		echo $insVarios->edit_empresa_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['empresa_id'])) $missingFields[] = "ID de la Empresa";
		if (!isset($_POST['empresa_empresa'])) $missingFields[] = "Nombre de la Empresa";
		if (!isset($_POST['telefono_empresa'])) $missingFields[] = "Teléfono de la Empresa";
		if (!isset($_POST['correo_empresa'])) $missingFields[] = "Correo de la Empresa";
		if (!isset($_POST['rtn_empresa'])) $missingFields[] = "RTN de la Empresa";
		if (!isset($_POST['direccion_empresa'])) $missingFields[] = "Dirección de la Empresa";
	

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