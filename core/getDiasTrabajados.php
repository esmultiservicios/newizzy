<?php
$peticionAjax = true;
require_once "configGenerales.php";
require_once "mainModel.php";

// Instanciar mainModel
$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();
if($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '".$validacion['redireccion']."'"
    ]);
}

$colaboradores_id = $_POST['colaboradores_id'];
$fechaiNomina = $_POST['fechaiNomina'];
$fechafNomina = $_POST['fechafNomina'];
$fecha = date("Y-m-d");

// Obtener el total de días marcados y las horas trabajadas dentro del período de la nómina
$queryAsistencia = "SELECT COUNT(asistencia_id) AS totalDias,
                            SUM(TIMESTAMPDIFF(HOUR, horai, horaf)) AS totalHoras
                    FROM asistencia
                    WHERE colaboradores_id = '$colaboradores_id'
                        AND estado = 0
                        AND fecha BETWEEN '$fechaiNomina' AND '$fechafNomina'";
$resultAsistencia = $insMainModel->connection()->query($queryAsistencia);

if ($resultAsistencia->num_rows > 0) {
    $valores = $resultAsistencia->fetch_assoc();
    $diasMarcados = $valores['totalDias']; // Sumar 5 según tu lógica

    // Calcular el equivalente en días trabajados
    $equivalenteDiasTrabajados = round($valores['totalHoras'] / 8, 2);

    // Limitar a 15 días según tu condición
    $diasTrabajados = min($diasMarcados, 15);

    $datos = array(
        0 => $diasTrabajados,
        'equivalenteDiasTrabajados' => $equivalenteDiasTrabajados
    );
} else {
    $datos = array(0 => 0);
}

echo json_encode($datos);
?>