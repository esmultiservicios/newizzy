<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mensajes.php"; // Incluye el archivo de mensajes

if (isset($_POST['colaborador_id']) && isset($_POST['puesto_colaborador']) && isset($_POST['nombre_colaborador']) && isset($_POST['apellido_colaborador']) && isset($_POST['telefono_colaborador'])) {
    require_once "../controladores/colaboradorControlador.php";
    $insVarios = new colaboradorControlador();
    
    echo $insVarios->editar_colaborador_controlador();
} else {
    $missingFields = [];

    if (!isset($_POST['colaborador_id'])) {
        $missingFields[] = "ID del colaborador";
    }
    if (!isset($_POST['puesto_colaborador'])) {
        $missingFields[] = "puesto del colaborador";
    }
    if (!isset($_POST['nombre_colaborador'])) {
        $missingFields[] = "nombre del colaborador";
    }
    if (!isset($_POST['apellido_colaborador'])) {
        $missingFields[] = "apellido del colaborador";
    }
    if (!isset($_POST['telefono_colaborador'])) {
        $missingFields[] = "teléfono del colaborador";
    }

    $missingFieldsText = implode(", ", $missingFields);
    echo generarMensajeError('Error 🚨', $missingFieldsText);
}