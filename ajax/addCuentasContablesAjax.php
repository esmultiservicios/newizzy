<?php	
	//addCuentasContablesAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['cuenta_codigo']) && isset($_POST['cuenta_nombre'])){
		require_once "../controladores/cuentaContabilidadControlador.php";
		$insVarios = new cuentaContabilidadControlador();
		
		echo $insVarios->agregar_cuenta_contabilidad_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['cuenta_codigo'])) $missingFields[] = "Codigo de la Cuenta";
		if (!isset($_POST['cuenta_nombre'])) $missingFields[] = "Nombre de la Cuenta";
	
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