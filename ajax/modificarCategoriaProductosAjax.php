<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['categoria_id']) && isset($_POST['categoria_productos'])){
		require_once "../controladores/categoriaProductosControlador.php";
		$insVarios = new categoriaProductosControlador();
		
		echo $insVarios->edit_categoria_productos_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['categoria_id'])) $missingFields[] = "ID de la Categoria";
		if (!isset($_POST['categoria_productos'])) $missingFields[] = "Nombre de la Categoria";
	
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