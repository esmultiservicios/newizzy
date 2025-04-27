<?php
	//agregarColaboradorAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nombre_colaborador']) && isset($_POST['telefono_colaborador']) && isset($_POST['puesto_colaborador']) && isset($_POST['colaboradores_activo'])){
		require_once "../controladores/colaboradorControlador.php";
		$insVarios = new colaboradorControlador();

		echo $insVarios->agregar_colaborador_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['nombre_colaborador'])) $missingFields[] = "Nombre del colaborador";
		if (!isset($_POST['telefono_colaborador'])) $missingFields[] = "Teléfono del colaborador";
		if (!isset($_POST['puesto_colaborador'])) $missingFields[] = "Puesto del colaborador";
		if (!isset($_POST['colaboradores_activo'])) $missingFields[] = "Estado del colaborador";
	
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