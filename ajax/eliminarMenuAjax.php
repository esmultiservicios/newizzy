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
        // Se obtiene el nombre del módulo a eliminar
        if($tipo == 'menu'){
            $sql = "SELECT name FROM menu WHERE menu_id = '$id'";
            $result = $insMainModel->ejecutar_consulta_simple($sql);
            $row = $result->fetch_assoc();
            $nombreModulo = $row['name']; // El nombre del menú

            $sql = "DELETE FROM menu WHERE menu_id = '$id'";
        }elseif($tipo == 'submenu'){
            $sql = "SELECT name FROM submenu WHERE submenu_id = '$id'";
            $result = $insMainModel->ejecutar_consulta_simple($sql);
            $row = $result->fetch_assoc();
            $nombreModulo = $row['name']; // El nombre del submenu

            $sql = "DELETE FROM submenu WHERE submenu_id = '$id'";
        }else{
            $sql = "SELECT name FROM submenu1 WHERE submenu1_id = '$id'";
            $result = $insMainModel->ejecutar_consulta_simple($sql);
            $row = $result->fetch_assoc();
            $nombreModulo = $row['name']; // El nombre del submenu de nivel 1

            $sql = "DELETE FROM submenu1 WHERE submenu1_id = '$id'";
        }

        // Ejecutar la eliminación del menú, submenú o submenú de nivel 1
        if($insMainModel->ejecutar_consulta_simple($sql)){
            // Eliminar el módulo de la lista blanca
            $nombre_config = 'config1'; // Ajusta según tu lógica para definir la configuración
            $moduloEliminar = $nombreModulo; // El módulo a eliminar de la lista blanca

            $eliminacionListaBlanca = $insMainModel->eliminar_modulo_lista_blanca($nombre_config, $moduloEliminar);

            if ($eliminacionListaBlanca) {
                echo json_encode([
                    "type" => "success",
                    "title" => "Éxito",
                    "message" => "Registro eliminado correctamente y lista blanca actualizada"
                ]);
            } else {
                echo json_encode([
                    "type" => "success",
                    "title" => "Éxito",
                    "message" => "Registro eliminado correctamente, pero el módulo no existía en la lista blanca"
                ]);
            }
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