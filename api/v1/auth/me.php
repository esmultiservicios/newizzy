<?php
/**
 * IZZY API V1 - Perfil y permisos actuales de la aplicación móvil.
 *
 * Método: GET
 * Ruta: /api/v1/auth/me.php
 * Autenticación: Authorization: Bearer <token>
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

/** Envía una respuesta JSON y finaliza el procesamiento. */
function responderPerfil(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// La consulta de perfil solo acepta GET.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    responderPerfil(405, [
        'ok' => false,
        'mensaje' => 'Método no permitido.'
    ]);
}

// El token nunca debe enviarse por una conexión sin cifrar.
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    responderPerfil(403, [
        'ok' => false,
        'mensaje' => 'Se requiere HTTPS.'
    ]);
}

// Los tokens solo se aceptan desde Authorization, nunca desde la URL.
$authorization = '';

// En Apache + FastCGI el encabezado puede exponerse con distintos nombres.
foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'Authorization'] as $key) {
    if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
        $authorization = trim($_SERVER[$key]);
        break;
    }
}

// Compatibilidad adicional con Apache cuando PHP recibe las cabeceras aquí.
if ($authorization === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp((string) $name, 'Authorization') === 0 && is_string($value)) {
            $authorization = trim($value);
            break;
        }
    }
}

if ($authorization === '' && function_exists('apache_request_headers')) {
    foreach (apache_request_headers() as $name => $value) {
        if (strcasecmp((string) $name, 'Authorization') === 0 && is_string($value)) {
            $authorization = trim($value);
            break;
        }
    }
}

// No se aceptan tokens en URL, cookies ni parámetros del formulario.

if (!preg_match('/^Bearer\s+(izzy_[a-f0-9]{64})$/i', $authorization, $matches)) {
    responderPerfil(401, [
        'ok' => false,
        'mensaje' => 'Token requerido.'
    ]);
}

try {
    require_once dirname(__DIR__, 3) . '/core/services/AuthService.php';

    $servicio = new IzzyApiAuthService();
    $perfil = $servicio->miPerfil($matches[1]);

    responderPerfil(200, [
        'ok' => true,
        'data' => $perfil
    ]);
} catch (DomainException $e) {
    responderPerfil(401, [
        'ok' => false,
        'mensaje' => 'Sesión inválida o vencida.'
    ]);
} catch (Throwable $e) {
    error_log('IZZY API perfil: ' . $e->getMessage());

    responderPerfil(500, [
        'ok' => false,
        'mensaje' => 'No se pudo consultar el perfil.'
    ]);
}
