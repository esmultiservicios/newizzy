<?php
// ajax/cancelIngresoContabilidadAjax.php
$peticionAjax = true;

require_once "../core/configGenerales.php";

if (!isset($_POST['ingresos_id']) || trim($_POST['ingresos_id']) === '') {
    $title = "Error";
    $msg = "No se recibió el ingreso a reversar.";

    $title = addslashes($title);
    $msg = addslashes($msg);

    echo "<script>showNotify('error', '$title', '$msg');</script>";
    exit;
}

require_once "../controladores/ingresosContabilidadControlador.php";

$ins = new ingresosContabilidadControlador();
echo $ins->cancel_ingresos_contabilidad_controlador();
