<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['empresa_secuencia']) && isset($_POST['cai_secuencia']) && isset($_POST['prefijo_secuencia']) && isset($_POST['relleno_secuencia']) && isset($_POST['incremento_secuencia']) && isset($_POST['siguiente_secuencia']) && isset($_POST['rango_inicial_secuencia']) && isset($_POST['rango_final_secuencia']) && isset($_POST['fecha_activacion_secuencia']) && isset($_POST['fecha_limite_secuencia']) && isset($_POST['estado_secuencia']) && isset($_POST['documento_secuencia']) ){
		require_once "../controladores/secuenciaFacturacionControlador.php";
		$insVarios = new secuenciaFacturacionControlador();
		
		echo $insVarios->agregar_secuencia_facturacion_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['empresa_secuencia'])) $missingFields[] = "Empresa";
		if (!isset($_POST['cai_secuencia'])) $missingFields[] = "CAI";
		if (!isset($_POST['prefijo_secuencia'])) $missingFields[] = "Prefijo";
		if (!isset($_POST['relleno_secuencia'])) $missingFields[] = "Relleno";
		if (!isset($_POST['incremento_secuencia'])) $missingFields[] = "Incremento";
		if (!isset($_POST['siguiente_secuencia'])) $missingFields[] = "Siguiente";
		if (!isset($_POST['rango_inicial_secuencia'])) $missingFields[] = "Rango Inicial";
		if (!isset($_POST['rango_final_secuencia'])) $missingFields[] = "Rango Final";
		if (!isset($_POST['fecha_activacion_secuencia'])) $missingFields[] = "Fecha de Activación";
		if (!isset($_POST['fecha_limite_secuencia'])) $missingFields[] = "Fecha de Limite";
		if (!isset($_POST['estado_secuencia'])) $missingFields[] = "Estado";
		if (!isset($_POST['documento_secuencia'])) $missingFields[] = "Documento";
	
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