<?php
// IZZY - Entrada independiente de Pantalla de Cocina
// DESTINO: /cocina/index.php
// No usa plantilla general ni sesión normal de IZZY.
declare(strict_types=1);

require_once dirname(__DIR__) . '/core/configGenerales.php';

header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// /cocina/ funciona como pantalla de vinculación.
// /cocina/TOKEN/ sigue funcionando como acceso directo/fallback.
$tokenCocina = trim((string)($_GET['token'] ?? ''));

if ($tokenCocina === '') {
    $requestPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
    if (preg_match('~/cocina/([a-f0-9]{64})(?:/|$)~i', $requestPath, $m)) {
        $tokenCocina = $m[1];
    }
}

$tokenCocina = strtolower($tokenCocina);
if ($tokenCocina !== '' && !preg_match('/^[a-f0-9]{64}$/', $tokenCocina)) {
    $tokenCocina = '';
}

require dirname(__DIR__) . '/vistas/contenido/cocina-view.php';