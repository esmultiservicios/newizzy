<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['nombre_proveedores']) && isset($_POST['rtn_proveedores']) && isset($_POST['fecha_proveedores'])){
		require_once "../controladores/proveedoresControlador.php";
		$insVarios = new proveedoresControlador();
		
		echo $insVarios->agregar_proveedores_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['nombre_proveedores'])) $missingFields[] = "Nombre del Proveedor";
		if (!isset($_POST['rtn_proveedores'])) $missingFields[] = "RTN del Proveedor";
		if (!isset($_POST['fecha_proveedores'])) $missingFields[] = "Fecha del Proveedor";
	
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