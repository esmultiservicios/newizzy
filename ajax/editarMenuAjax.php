<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if (isset($_POST['id']) && isset($_POST['tipo']) && isset($_POST['nombre'])) {
    $insMainModel = new mainModel();

    $id = $_POST['id'];
    $tipo = $insMainModel->cleanStringConverterCase($_POST['tipo']);
    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre']);
    $dependencia = isset($_POST['dependencia']) ? $_POST['dependencia'] : null;

    // Variable para el nombre de la configuración que se actualizará en la lista blanca
    $nombre_config = 'configuracion_principal'; // Ajusta según tu lógica para definir la configuración

    // Dependiendo del tipo (menu, submenu, submenu1), se actualiza la tabla correspondiente
    if ($tipo == 'menu') {
        $sql = "UPDATE menu SET name = '$nombre' WHERE menu_id = '$id'";
    } elseif ($tipo == 'submenu') {
        $sql = "UPDATE submenu SET name = '$nombre', menu_id = '$dependencia' WHERE submenu_id = '$id'";
    } else {
        $sql = "UPDATE submenu1 SET name = '$nombre', submenu_id = '$dependencia' WHERE submenu1_id = '$id'";
    }

    // Ejecutamos la actualización de la tabla correspondiente
    if ($insMainModel->ejecutar_consulta_simple($sql)) {
        // Actualizamos la lista blanca con el nuevo módulo (nombre del menú o submenu actualizado)
        $moduloNuevo = $nombre; // El módulo nuevo es el nombre actualizado
        $actualizacionListaBlanca = $insMainModel->guardar_o_actualizar_modulo_lista_blanca($nombre_config, $moduloNuevo);

        if ($actualizacionListaBlanca) {
            echo json_encode([
                "type" => "success",
                "title" => "Éxito",
                "message" => "Registro actualizado correctamente y lista blanca modificada"
            ]);
        } else {
            echo json_encode([
                "type" => "warning",
                "title" => "Advertencia",
                "message" => "El módulo ya existía en la lista blanca"
            ]);
        }
    } else {
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo actualizar el registro"
        ]);
    }
} else {
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}
