<?php
//modificarUsersAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";

if (isset($_POST['usuarios_id']) && isset($_POST['correo_usuario']) && isset($_POST['tipo_user']) && isset($_POST['privilegio_id']) && isset($_POST['empresa_usuario'])) {
    require_once "../controladores/usuarioControlador.php";
    $insVarios = new usuarioControlador();
    
    echo $insVarios->edit_user_controlador();
} else {
    $missingFields = [];

    if (!isset($_POST['usuarios_id'])) {
        $missingFields[] = "ID del usuario";
    }
    if (!isset($_POST['correo_usuario'])) {
        $missingFields[] = "correo del usuario";
    }
    if (!isset($_POST['tipo_user'])) {
        $missingFields[] = "tipo de usuario";
    }
    if (!isset($_POST['privilegio_id'])) {
        $missingFields[] = "privilegio";
    }
    if (!isset($_POST['empresa_usuario'])) {
        $missingFields[] = "empresa del usuario";
    }

    $missingFieldsText = implode(", ", $missingFields);

    echo "
    <script>
        swal({
            title: 'Error 🚨', 
            content: {
                element: 'span',
                attributes: {
                    innerHTML: 'Faltan los siguientes campos: <b>$missingFieldsText</b>. Por favor, corrígelos.'
                }
            },
            icon: 'error', 
            buttons: {
                confirm: {
                    text: 'Entendido',
                    className: 'btn-danger'
                }
            },
            closeOnEsc: false,
            closeOnClickOutside: false
        });
    </script>";
}