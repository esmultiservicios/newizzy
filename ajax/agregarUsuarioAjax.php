<?php
	//agregarUsuarioAjax.php
	$peticionAjax = true;
	require_once "../core/configGenerales.php";
	
	if(isset($_POST['colaboradores_id']) && isset($_POST['privilegio_id']) && 
		isset($_POST['correo_usuario']) && isset($_POST['empresa_usuario']) && 
		isset($_POST['tipo_user']) && isset($_POST['estado_usuario'])){
		require_once "../controladores/usuarioControlador.php";
		$insVarios = new usuarioControlador();

		echo $insVarios->agregar_usuario_controlador();
	} else {
		// Identificar campos faltantes
		$missingFields = [];
		
		if (!isset($_POST['usuarios_colaborador_id'])) $missingFields[] = "ID del Colaborador";
		if (!isset($_POST['colaborador_id_usuario'])) $missingFields[] = "ID del Colaborador";
		if (!isset($_POST['privilegio_id'])) $missingFields[] = "ID del Privilegio";
		if (!isset($_POST['correo_usuario'])) $missingFields[] = "Correo del Usuario";
		if (!isset($_POST['empresa_usuario'])) $missingFields[] = "Empresa del Usuario";
		if (!isset($_POST['tipo_user'])) $missingFields[] = "Tipo de Usuario";
		if (!isset($_POST['usuarios_activo'])) $missingFields[] = "Activo del Usuario";
	
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