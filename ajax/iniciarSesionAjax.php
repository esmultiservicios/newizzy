<?php
$peticionAjax = true;
require_once '../core/configGenerales.php';

if (isset($_POST['inputEmail']) && isset($_POST['inputPassword'])) {
	require_once '../controladores/loginControlador.php';
	require_once '../core/mainModel.php';

	$login = new loginControlador();

	if ($login->validar_pago_pendiente_main_server_controlador() == 1) {
		echo $login->iniciar_sesion_controlador();
	} else {
		echo $login->validar_pago_pendiente_main_server_controlador();
	}

	$insMainModel = new mainModel();

	// mainModel::guardar_historial_accesos("Inicio de Sesion");
} else {
	// Identificar campos faltantes
	$missingFields = [];
	
	if (!isset($_POST['inputEmail'])) $missingFields[] = "Correo Electrónico";
	if (!isset($_POST['inputPassword'])) $missingFields[] = "Contraseña";

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