<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

if(isset($_POST['fecha_ingresos']) && isset($_POST['recibide_ingresos']) && isset($_POST['cuenta_ingresos']) && isset($_POST['subtotal_ingresos'])){
    require_once "../controladores/ingresosContabilidadControlador.php";
    $insVarios = new ingresosContabilidadControlador();
    
    echo $insVarios->agregar_ingresos_contabilidad_controlador();
} else {
	// Identificar campos faltantes
	$missingFields = [];
	
	if (!isset($_POST['fecha_ingresos'])) $missingFields[] = "fecha";
	if (!isset($_POST['recibide_ingresos'])) $missingFields[] = "recibide";
	if (!isset($_POST['cuenta_ingresos'])) $missingFields[] = "cuenta";
	if (!isset($_POST['subtotal_ingresos'])) $missingFields[] = "subtotal";

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