<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['bk_nm_chk']) && isset($_POST['check_num'])){
		require_once "../controladores/pagoFacturaControlador.php";
		$insVarios = new pagoFacturaControlador();
		
		echo $insVarios->agregar_pago_factura_controlador_cheque();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['bk_nm_chk'])) $missingFields[] = "Banco";
		if (!isset($_POST['check_num'])) $missingFields[] = "Número de cheque";
	
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