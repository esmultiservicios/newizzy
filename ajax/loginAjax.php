<?php
$peticionAjax = true;
require_once '../core/configGenerales.php';

if (isset($_GET['token'])) {
	require_once '../controladores/loginControlador.php';

	$logout = new loginControlador();

	echo $logout->cerrar_sesion_controlador();
} else {
	// Identificar campos faltantes
	$missingFields = [];
	
	if (!isset($_POST['email'])) $missingFields[] = "Correo Electrónico";
	if (!isset($_POST['password'])) $missingFields[] = "Contraseña";

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