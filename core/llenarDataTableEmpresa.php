<?php
// core/llenarDataTableEmpresa.php

$peticionAjax = true;

require_once "configGenerales.php";
require_once "mainModel.php";

$insMainModel = new mainModel();

// Validar sesión primero
$validacion = $insMainModel->validarSesion();

if ($validacion['error']) {
    return $insMainModel->showNotification([
        "title" => "Error de sesión",
        "text" => $validacion['mensaje'],
        "type" => "error",
        "funcion" => "window.location.href = '" . $validacion['redireccion'] . "'"
    ]);
}

$conexion = $insMainModel->connection();

try {
    $stmt = $conexion->prepare("SELECT nombre FROM privilegio WHERE privilegio_id = ?");
    $stmt->bind_param("i", $_SESSION['privilegio_sd']);
    $stmt->execute();

    $resultadoPrivilegio = $stmt->get_result();
    $privilegio_colaborador = "";

    if ($resultadoPrivilegio->num_rows > 0) {
        $row = $resultadoPrivilegio->fetch_assoc();
        $privilegio_colaborador = $row['nombre'];
    }

    $estado = (isset($_POST['estado']) && $_POST['estado'] !== '') ? $_POST['estado'] : 1;

    $datos = [
        "privilegio_id" => $_SESSION['privilegio_sd'],
        "colaborador_id" => $_SESSION['colaborador_id_sd'],
        "privilegio_colaborador" => $privilegio_colaborador,
        "empresa_id" => $_SESSION['empresa_id_sd'],
        "estado" => $estado
    ];

    $result = $insMainModel->getEmpresa($datos);

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "empresa_id" => $row['empresa_id'],
            "razon_social" => $row['razon_social'],
            "nombre" => $row['nombre'],
            "otra_informacion" => $row['otra_informacion'],
            "eslogan" => $row['eslogan'],
            "celular" => $row['celular'],
            "telefono" => $row['telefono'],
            "correo" => $row['correo'],
            "image" => $row['logotipo'],
            "logotipo" => $row['logotipo'],
            "rtn" => $row['rtn'],
            "ubicacion" => $row['ubicacion'],
            "facebook" => $row['facebook'],
            "sitioweb" => $row['sitioweb'],
            "horario" => $row['horario'],
            "estado" => $row['estado'],
            "fecha_registro" => $row['fecha_registro'],
            "firma_documento" => $row['firma_documento'],
            "MostrarFirma" => $row['MostrarFirma']
        ];
    }

    echo json_encode([
        "echo" => 1,
        "totalrecords" => count($data),
        "totaldisplayrecords" => count($data),
        "data" => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        "echo" => 0,
        "error" => $e->getMessage(),
        "data" => []
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }

    if (isset($conexion)) {
        $conexion->close();
    }
}