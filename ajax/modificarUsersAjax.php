<?php
$peticionAjax = true;
require_once "../core/configGenerales.php";

if(isset($_POST['usuarios_id']) && isset($_POST['correo_usuario']) && 
   isset($_POST['tipo_user']) && isset($_POST['privilegio_id']) && 
   isset($_POST['empresa_usuario'])) {
    
    require_once "../controladores/usuarioControlador.php";
    $insVarios = new usuarioControlador();
    echo $insVarios->edit_user_controlador();
    
} else {
    // Identificar campos faltantes
    $missingFields = [];
    
    if(!isset($_POST['usuarios_id'])) $missingFields[] = "ID del usuario";
    if(!isset($_POST['correo_usuario'])) $missingFields[] = "Correo del usuario";
    if(!isset($_POST['tipo_user'])) $missingFields[] = "Tipo de usuario";
    if(!isset($_POST['privilegio_id'])) $missingFields[] = "Privilegio";
    if(!isset($_POST['empresa_usuario'])) $missingFields[] = "Empresa del usuario";

    // Preparar el mensaje
    $missingText = implode(", ", $missingFields);
    $title = "Error 🚨";
    $message = "Faltan los siguientes campos: $missingText. Por favor, complétalos.";
    
    // Escapar comillas para JavaScript
    $title = addslashes($title);
    $message = addslashes($message);
    
    // Mostrar notificación
    echo "<script>
        showNotify('error', '$title', '$message');
    </script>";
}