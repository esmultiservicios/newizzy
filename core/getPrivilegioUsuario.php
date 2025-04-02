<?php	
$peticionAjax = true;

if (!isset($_SESSION['user_sd'])) { 
    session_start(['name'=>'SD']); 
}

// Verifica si la sesión ha expirado (ejemplo: 30 minutos de inactividad)
$session_lifetime = 1800; // 30 minutos
if (isset($_SESSION['session_time']) && (time() - $_SESSION['session_time']) > $session_lifetime) {
    session_unset();  // Limpia las variables de sesión
    session_destroy(); // Destruye la sesión
    echo json_encode(["error" => "session_expired"]);
    exit();
}

// Renovar el tiempo de sesión
$_SESSION['session_time'] = time();

$privilegio_id = $_SESSION['privilegio_sd'];

$datos = array(
    0 => $privilegio_id, 					
);

echo json_encode($datos);