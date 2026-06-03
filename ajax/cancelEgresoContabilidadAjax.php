<?php
// ajax/cancelEgresoContabilidadAjax.php
$peticionAjax = true;

require_once "../core/configGenerales.php";

if (!isset($_POST['egresos_id']) || trim($_POST['egresos_id']) === '') {
    $title = "Error";
    $message = "No se recibió el egreso a reversar.";

    $title = addslashes($title);
    $message = addslashes($message);

    echo "<script>showNotify('error', '$title', '$message');</script>";
    exit;
}

require_once "../controladores/egresosContabilidadControlador.php";

$insEgresos = new egresosContabilidadControlador();
echo $insEgresos->cancel_egresos_contabilidad_controlador();
