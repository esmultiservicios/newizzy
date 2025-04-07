<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if(isset($_POST['id']) && isset($_POST['tipo']) && isset($_POST['nombre'])){
    $insMainModel = new mainModel();
    
    $id = $_POST['id'];
    $tipo = $insMainModel->cleanStringConverterCase($_POST['tipo']);
    $nombre = $insMainModel->cleanStringConverterCase($_POST['nombre']);
    $dependencia = isset($_POST['dependencia']) ? $_POST['dependencia'] : null;

    if($tipo == 'menu'){
        $sql = "UPDATE menu SET name = '$nombre' WHERE menu_id = '$id'";
    }elseif($tipo == 'submenu'){
        $sql = "UPDATE submenu SET name = '$nombre', menu_id = '$dependencia' WHERE submenu_id = '$id'";
    }else{
        $sql = "UPDATE submenu1 SET name = '$nombre', submenu_id = '$dependencia' WHERE submenu1_id = '$id'";
    }

    if($insMainModel->ejecutar_consulta_simple($sql)){
        echo json_encode([
            "type" => "success",
            "title" => "Éxito",
            "message" => "Registro actualizado correctamente"
        ]);
    }else{
        echo json_encode([
            "type" => "error",
            "title" => "Error",
            "message" => "No se pudo actualizar el registro"
        ]);
    }
}else{
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}