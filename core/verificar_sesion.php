<?php
if (!isset($_SESSION['user_sd'])) {
    session_start(['name' => 'SD']);
}

$tiempoActual = time(); // Tiempo actual en segundos
$renovar = filter_var($_GET['renovar'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($renovar) {
    $_SESSION['session_time'] = time(); // Actualiza el tiempo de la sesión
}

$sessionTime = $_SESSION['session_time'] ?? 0; // Convertir la fecha y hora almacenada en timestamp

$diferenciaEnSegundos = $tiempoActual - $sessionTime;
$diferenciaEnMinutos = $diferenciaEnSegundos / 60;

$tiempoSesion = 120; // segundos (ajústalo según tus necesidades)
$umbralNotificacion = 60; // segundos antes de la expiración para mostrar la notificación (ajústalo según tus necesidades)

// Calcular el tiempo restante en minutos
$tiempoRestante = max(0, round($tiempoSesion - $diferenciaEnMinutos, 2)); // Redondear a 2 decimales

// Verificar si la diferencia en minutos es menor o igual que $tiempoSesion
if ($diferenciaEnMinutos < $tiempoSesion) {
    // Aún dentro del tiempo de sesión
    if ($tiempoRestante <= $umbralNotificacion) {
        // Umbral de notificación, mostrar la notificación
        echo json_encode(['estado' => 'show_notification', 'tiempoRestante' => $tiempoRestante, 'tiempoSesion' => $tiempoSesion, 'renovar' => $renovar]);
    } else {
        // Dentro del tiempo de sesión pero no en el umbral de notificación
        echo json_encode(['estado' => 'active', 'tiempoRestante' => $tiempoRestante, 'tiempoSesion' => $tiempoSesion, 'renovar' => $renovar]);
    }
} else {
    // Fuera del tiempo de sesión
    if ($tiempoRestante <= 0) {
        // Sesión expirada
        unset($_SESSION['user_sd']);
        unset($_SESSION['expire_time']);
        echo json_encode(['estado' => 'expired']);
    } else {
        // Renovar sesión
        $_SESSION['session_time'] = time(); // Actualiza el tiempo de la sesión
        echo json_encode(['estado' => 'renew', 'tiempoRestante' => $tiempoRestante, 'tiempoSesion' => $tiempoSesion, 'renovar' => $renovar]);
    }
}
?>