<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if (isset($_POST['tipo']) && isset($_POST['nombre'])) {
    $insMainModel = new mainModel();

    $tipo = $insMainModel->cleanStringConverterCase($_POST['tipo']);
    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre']);
    $dependencia = isset($_POST['dependencia']) ? $_POST['dependencia'] : null;
    $fecha_registro = date("Y-m-d H:i:s");

    // Consultar el último ID registrado en la tabla correspondiente
    if ($tipo == 'menu') {
        $lastIdQuery = $insMainModel->ejecutar_consulta_simple("SELECT MAX(menu_id) AS last_id FROM menu");
    } elseif ($tipo == 'submenu') {
        $lastIdQuery = $insMainModel->ejecutar_consulta_simple("SELECT MAX(submenu_id) AS last_id FROM submenu");
    } else {
        $lastIdQuery = $insMainModel->ejecutar_consulta_simple("SELECT MAX(submenu1_id) AS last_id FROM submenu1");
    }

    $lastIdRow = $lastIdQuery->fetch_assoc();
    $lastId = $lastIdRow['last_id'];
    $nextId = $lastId + 1; // Calcular el siguiente ID

    // Validar existencia
    if ($tipo == 'menu') {
        $check = $insMainModel->ejecutar_consulta_simple("SELECT menu_id FROM menu WHERE name = '$nombre'");
    } elseif ($tipo == 'submenu') {
        $check = $insMainModel->ejecutar_consulta_simple("SELECT submenu_id FROM submenu WHERE name = '$nombre' AND menu_id = '$dependencia'");
    } else {
        $check = $insMainModel->ejecutar_consulta_simple("SELECT submenu1_id FROM submenu1 WHERE name = '$nombre' AND submenu_id = '$dependencia'");
    }

    if ($check->num_rows == 0) {
        if ($tipo == 'menu') {
            $sql = "INSERT INTO menu (menu_id, name, fecha_registro) VALUES ('$nextId', '$nombre', '$fecha_registro')";
        } elseif ($tipo == 'submenu') {
            $sql = "INSERT INTO submenu (submenu_id, menu_id, name, fecha_registro) VALUES ('$nextId', '$dependencia', '$nombre', '$fecha_registro')";
        } else {
            $sql = "INSERT INTO submenu1 (submenu1_id, submenu_id, name, fecha_registro) VALUES ('$nextId', '$dependencia', '$nombre', '$fecha_registro')";
        }

        if ($insMainModel->ejecutar_consulta_simple($sql)) {
            echo json_encode([
                "type" => "success",
                "title" => "Éxito",
                "message" => "Registro almacenado correctamente",
                "last_id" => $nextId // Incluir el último ID en la respuesta
            ]);
        } else {
            echo json_encode([
                "type" => "error",
                "title" => "Error",
                "message" => "No se pudo completar la operación"
            ]);
        }
    } else {
        echo json_encode([
            "type" => "warning",
            "title" => "Advertencia",
            "message" => "Este registro ya existe en el sistema"
        ]);
    }
} else {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}