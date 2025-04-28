<?php
//renovar_sesion.php
session_start();

// Simplemente actualiza el tiempo de la sesión
$_SESSION['session_time'] = time();

// Puedes devolver un JSON indicando que la renovación fue exitosa
echo json_encode(['success' => true]);