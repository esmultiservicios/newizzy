<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['proveedor_egresos']) && isset($_POST['cuenta_egresos']) && isset($_POST['fecha_egresos']) && isset($_POST['factura_egresos']) && isset($_POST['subtotal_egresos']) && isset($_POST['isv_egresos']) && isset($_POST['descuento_egresos']) && isset($_POST['nc_egresos']) && isset($_POST['total_egresos'])){
		require_once "../controladores/egresosContabilidadControlador.php";
		$insVarios = new egresosContabilidadControlador();
		
		echo $insVarios->agregar_egresos_contabilidad_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['proveedor_egresos'])) $missingFields[] = "proveedor";
		if (!isset($_POST['cuenta_egresos'])) $missingFields[] = "cuenta";
		if (!isset($_POST['fecha_egresos'])) $missingFields[] = "fecha";
		if (!isset($_POST['factura_egresos'])) $missingFields[] = "factura";
		if (!isset($_POST['subtotal_egresos'])) $missingFields[] = "subtotal";
		if (!isset($_POST['isv_egresos'])) $missingFields[] = "isv";
		if (!isset($_POST['descuento_egresos'])) $missingFields[] = "descuento";
		if (!isset($_POST['nc_egresos'])) $missingFields[] = "nc";
		if (!isset($_POST['total_egresos'])) $missingFields[] = "total";

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