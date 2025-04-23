<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaborador_id']) && isset($_POST['nombre_colaborador']) && isset($_POST['telefono_colaborador'])){
		require_once "../controladores/colaboradorControlador.php";
		$insVarios = new colaboradorControlador();
		
		echo $insVarios->editar_colaborador_perfil_controlador();
	}else{
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['colaborador_id'])) $missingFields[] = "ID del colaborador";
		if (!isset($_POST['telefono_colaborador'])) $missingFields[] = "puesto";
		if (!isset($_POST['nombre_colaborador'])) $missingFields[] = "nombre";

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