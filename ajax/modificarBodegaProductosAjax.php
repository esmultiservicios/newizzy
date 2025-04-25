<?php	
$peticionAjax = true;
require_once "../core/configGenerales.php";

if(isset($_POST['productos_id']) && $_POST['productos_id'] != '0' &&
   isset($_POST['id_bodega']) && $_POST['id_bodega'] != '0') {
    
    require_once "../controladores/productosControlador.php";
    $insVarios = new productosControlador();
    echo $insVarios->edit_bodega_productos_controlador();
    
} else {
    // Identificar campos con problemas
    $invalidFields = [];
    
    if(!isset($_POST['productos_id']) || $_POST['productos_id'] == '0') {
        $invalidFields[] = "ID del Producto";
    }
    if(!isset($_POST['id_bodega']) || $_POST['id_bodega'] == '0') {
        $invalidFields[] = "ID de Bodega";
    }

    // Preparar el mensaje
    $fieldsText = implode(" y ", $invalidFields);
    $title = "Error 🚨";
    $message = "Los siguientes campos son obligatorios: $fieldsText. No pueden ser cero.";
    
    // Escapar comillas para JavaScript
    $title = addslashes($title);
    $message = addslashes($message);
    
    // Mostrar notificación
    echo "<script>
        showNotify('error', '$title', '$message');
    </script>";
}