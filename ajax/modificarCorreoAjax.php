<?php
// ajax/modificarCorreoAjax.php

$peticionAjax = true;

require_once "../core/configGenerales.php";

if (isset($_POST['correo_id']) && isset($_POST['correoConfEmail']) && isset($_POST['metodoEnvioConfEmail'])) {
    require_once "../controladores/correoControlador.php";

    $insCorreo = new correoControlador();

    echo $insCorreo->edit_correo_controlador();
} else {
    $missingFields = [];

    if (!isset($_POST['correo_id'])) $missingFields[] = "ID de correo";
    if (!isset($_POST['correoConfEmail'])) $missingFields[] = "Correo";
    if (!isset($_POST['metodoEnvioConfEmail'])) $missingFields[] = "Método de envío";

    $missingText = implode(", ", $missingFields);
    $title = "Error 🚨";
    $message = "Faltan los siguientes campos: $missingText. Por favor, corrígelos.";

    $title = addslashes($title);
    $message = addslashes($message);

    echo "<script>
        showNotify('error', '$title', '$message');
    </script>";
}