<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";
require_once "../core/mainModel.php";

if(isset($_POST['id']) && isset($_POST['tipo'])){
    $insMainModel = new mainModel();
    
    $id = $_POST['id'];
    $tipo = $_POST['tipo'];
    $hasDependencies = false;

    // Validar dependencias
    if($tipo == 'menu'){
        $check = $insMainModel->ejecutar_consulta_simple("SELECT submenu_id FROM submenu WHERE menu_id = '$id'");
        if($check->num_rows > 0) $hasDependencies = true;
    }elseif($tipo == 'submenu'){
        $check = $insMainModel->ejecutar_consulta_simple("SELECT submenu1_id FROM submenu1 WHERE submenu_id = '$id'");
        if($check->num_rows > 0) $hasDependencies = true;
    }

    if(!$hasDependencies){
        if($tipo == 'menu'){
            $sql = "DELETE FROM menu WHERE menu_id = '$id'";
        }elseif($tipo == 'submenu'){
            $sql = "DELETE FROM submenu WHERE submenu_id = '$id'";
        }else{
            $sql = "DELETE FROM submenu1 WHERE submenu1_id = '$id'";
        }

        if($insMainModel->ejecutar_consulta_simple($sql)){
            echo json_encode([
                "type" => "success",
                "title" => "Éxito",
                "message" => "Registro eliminado correctamente"
            ]);
        }else{
            echo json_encode([
                "type" => "error",
                "title" => "Error",
                "message" => "No se pudo eliminar el registro"
            ]);
        }
    }else{
        echo json_encode([
            "type" => "warning",
            "title" => "Advertencia",
            "message" => "No se puede eliminar porque tiene elementos dependientes"
        ]);
    }
}else{
    echo json_encode([
        "type" => "error",
        "title" => "Error",
        "message" => "Datos incompletos"
    ]);
}