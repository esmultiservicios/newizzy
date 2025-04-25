<?php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['empresa_empresa']) && isset($_POST['rtn_empresa']) && isset($_POST['telefono_empresa']) && isset($_POST['correo_empresa']) && isset($_POST['direccion_empresa'])){
		require_once "../controladores/empresaControlador.php";
		$insVarios = new empresaControlador();

		var_dump($insVarios->agregar_empresa_controlador());
		echo $insVarios->agregar_empresa_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['empresa_empresa'])) $missingFields[] = "Empresa";
		if (!isset($_POST['rtn_empresa'])) $missingFields[] = "RTN";
		if (!isset($_POST['telefono_empresa'])) $missingFields[] = "Teléfono";
		if (!isset($_POST['correo_empresa'])) $missingFields[] = "Correo";
		if (!isset($_POST['direccion_empresa'])) $missingFields[] = "Dirección";
	
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