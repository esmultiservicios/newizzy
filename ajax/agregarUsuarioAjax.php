<?php
// agregarUsuarioAjax.php
$peticionAjax = true;
require_once "../core/configGenerales.php";

/*
 * estado_usuario NO se valida con isset() porque cuando el switch está
 * desmarcado el navegador no envía el checkbox. El controlador ya convierte
 * ausencia = Inactivo (2).
 */
$camposRequeridos = [
    'colaboradores_id' => 'Colaborador',
    'privilegio_id' => 'Nivel de privilegio',
    'correo_usuario' => 'Correo electrónico',
    'empresa_usuario' => 'Empresa',
    'tipo_user' => 'Tipo de permisos'
];

$faltantes = [];

foreach ($camposRequeridos as $campo => $etiqueta) {
    if (!isset($_POST[$campo]) || trim((string)$_POST[$campo]) === '') {
        $faltantes[] = $etiqueta;
    }
}

if (!empty($faltantes)) {
    $mensaje = 'Faltan campos requeridos: ' . implode(', ', $faltantes) . '.';

    echo '<script>(function(){' .
        'if (typeof showNotify === "function") {' .
            'showNotify("error", "Datos incompletos", ' . json_encode($mensaje, JSON_UNESCAPED_UNICODE) . ');' .
        '}' .
    '})();</script>';
    exit;
}

require_once "../controladores/usuarioControlador.php";
$insUsuario = new usuarioControlador();

echo $insUsuario->agregar_usuario_controlador();