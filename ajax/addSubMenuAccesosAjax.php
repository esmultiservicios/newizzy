<?php	
	//addSubMenuAccesosAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['privilegio_id_accesos']) && isset($_POST['menu_id_accesos'])){
		require_once "../controladores/menuAccesosControlador.php";
		$insVarios = new menuAccesosControlador();
		
		echo $insVarios->agregar_SubMenuAccesos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['privilegio_id_accesos'])) $missingFields[] = "ID del Privilegio";
		if (!isset($_POST['menu_id_accesos'])) $missingFields[] = "ID del Menú";
	
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