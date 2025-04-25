<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";

	if(isset($_POST['proveedores_id']) && isset($_POST['proveedor']) && isset($_POST['facturaPurchase']) && isset($_POST['colaborador_id']) ){
		require_once "../controladores/comprasControlador.php";
		$insVarios = new comprasControlador();
		
		echo $insVarios->agregar_compras_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['proveedores_id'])) $missingFields[] = "ID del Proveedor";
		if (!isset($_POST['proveedor'])) $missingFields[] = "Nombre del Proveedor";
		if (!isset($_POST['facturaPurchase'])) $missingFields[] = "Factura de la Compra";
		if (!isset($_POST['colaborador_id'])) $missingFields[] = "ID del Colaborador";
	
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