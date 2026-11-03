<?php
/**
 * IZZY API V1 - Cierre de sesión de la aplicación móvil.
 *
 * Método: POST
 * Ruta:   /api/v1/auth/logout.php
 * Header: Authorization: Bearer <token>
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

/**
 * Devuelve una respuesta JSON y termina la solicitud.
 */
function respuestaLogout(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Solo se permite cerrar sesión mediante POST y HTTPS.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respuestaLogout(405, ['ok' => false]);
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    respuestaLogout(403, ['ok' => false]);
}

// Leer el token Bearer recibido en la cabecera HTTP.
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

if (!preg_match('/^Bearer\s+(izzy_[a-f0-9]{64})$/i', $authorization, $m)) {
    respuestaLogout(401, [
        'ok' => false,
        'mensaje' => 'Token requerido.'
    ]);
}

try {
    require_once dirname(__DIR__, 3) . '/core/services/AuthService.php';

    (new IzzyApiAuthService())->logout($m[1]);

    respuestaLogout(200, [
        'ok' => true,
        'mensaje' => 'Sesión cerrada.'
    ]);
} catch (DomainException $e) {
    respuestaLogout(401, [
        'ok' => false,
        'mensaje' => 'Sesión inválida.'
    ]);
} catch (Throwable $e) {
    error_log('IZZY API logout: ' . $e->getMessage());
    respuestaLogout(500, [
        'ok' => false,
        'mensaje' => 'Error al cerrar sesión.'
    ]);
}
