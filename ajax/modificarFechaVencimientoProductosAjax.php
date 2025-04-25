<?php	
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['fecha_caducidad']) && isset($_POST['productos_id']) && $_POST['productos_id'] != '0' && 
	   isset($_POST['id_bodega_actual']) && $_POST['id_bodega_actual'] != '0' && 
	   isset($_POST['cantidad_productos']) && $_POST['cantidad_productos'] != '0' && 
	   isset($_POST['empresa_id_productos']) && isset($_POST['lote_id_productos'])){
		
		require_once "../controladores/movimientoProductosControlador.php";
		$insVarios = new movimientoProductosControlador();
		echo $insVarios->modificar_fecha_vencimiento_movimiento_productos_controlador();
	} else {
		// Identificar campos faltantes o inválidos
		$missingFields = [];
		
		if (!isset($_POST['fecha_caducidad']) || $_POST['fecha_caducidad'] == '') $missingFields[] = "Fecha de Caducidad";
		if (!isset($_POST['productos_id']) || $_POST['productos_id'] == '' || $_POST['productos_id'] == '0') $missingFields[] = "ID de Producto válido";
		if (!isset($_POST['id_bodega_actual']) || $_POST['id_bodega_actual'] == '' || $_POST['id_bodega_actual'] == '0') $missingFields[] = "ID de Bodega válido";
		if (!isset($_POST['cantidad_productos']) || $_POST['cantidad_productos'] == '' || $_POST['cantidad_productos'] == '0') $missingFields[] = "Cantidad de Productos válida";
		if (!isset($_POST['empresa_id_productos']) || $_POST['empresa_id_productos'] == '') $missingFields[] = "ID de Empresa";
		if (!isset($_POST['lote_id_productos']) || $_POST['lote_id_productos'] == '') $missingFields[] = "ID de Lote";
	
		// Preparar el mensaje
		$missingText = implode(", ", $missingFields);
		$title = "Error 🚨";
		$message = "Los siguientes campos son obligatorios o no son válidos: $missingText. Por favor, corrígelos.";
		
		// Escapar comillas para JavaScript
		$title = addslashes($title);
		$message = addslashes($message);
		
		// Llamar a la función showNotify
		echo "<script>
			showNotify('error', '$title', '$message');
		</script>";
	}