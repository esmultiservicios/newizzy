<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['secuencia_facturacion_id']) && isset($_POST['cai_secuencia']) && isset($_POST['prefijo_secuencia']) && isset($_POST['relleno_secuencia']) && isset($_POST['incremento_secuencia']) && isset($_POST['siguiente_secuencia']) && isset($_POST['rango_inicial_secuencia']) && isset($_POST['rango_final_secuencia']) && isset($_POST['fecha_activacion_secuencia']) && isset($_POST['fecha_limite_secuencia'])){
		require_once "../controladores/secuenciaFacturacionControlador.php";
		$insVarios = new secuenciaFacturacionControlador();
		
		echo $insVarios->edit_secuencia_facturacion_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['secuencia_facturacion_id'])) $missingFields[] = "ID de la Secuencia de Facturación";
		if (!isset($_POST['cai_secuencia'])) $missingFields[] = "CAI de la Secuencia";
		if (!isset($_POST['prefijo_secuencia'])) $missingFields[] = "Prefijo de la Secuencia";
		if (!isset($_POST['relleno_secuencia'])) $missingFields[] = "Relleno de la Secuencia";
		if (!isset($_POST['incremento_secuencia'])) $missingFields[] = "Incremento de la Secuencia";
		if (!isset($_POST['siguiente_secuencia'])) $missingFields[] = "Siguiente Secuencia";
		if (!isset($_POST['rango_inicial_secuencia'])) $missingFields[] = "Rango Inicial de la Secuencia";
		if (!isset($_POST['rango_final_secuencia'])) $missingFields[] = "Rango Final de la Secuencia";
		if (!isset($_POST['fecha_activacion_secuencia'])) $missingFields[] = "Fecha de Activación de la Secuencia";
		if (!isset($_POST['fecha_limite_secuencia'])) $missingFields[] = "Fecha Límite de la Secuencia";

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