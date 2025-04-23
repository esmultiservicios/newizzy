<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['contrato_colaborador_id']) && isset($_POST['contrato_tipo_contrato_id']) && isset($_POST['contrato_pago_planificado_id']) && isset($_POST['contrato_tipo_empleado_id'])){
		require_once "../controladores/contratoControlador.php";
		$insVarios = new contratoControlador();
		
		echo $insVarios->agregar_contrato_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['contrato_colaborador_id'])) $missingFields[] = "ID del colaborador";
		if (!isset($_POST['contrato_tipo_contrato_id'])) $missingFields[] = "Tipo de contrato";
		if (!isset($_POST['contrato_pago_planificado_id'])) $missingFields[] = "Plan de pago";
		if (!isset($_POST['contrato_tipo_empleado_id'])) $missingFields[] = "Tipo de empleado";
	

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