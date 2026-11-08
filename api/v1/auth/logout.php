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
header('X-Content-Type-Options: nosniff');

/**
 * Devuelve una respuesta JSON y termina la solicitud.
 */
function respuestaLogout(int $status, array $data): void
{
    if (!array_key_exists('mensaje', $data)) $data['mensaje'] = $status >= 400 ? 'No se pudo completar la solicitud.' : '';
    if (!array_key_exists('data', $data)) $data['data'] = null;
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Solo se permite cerrar sesión mediante POST y HTTPS.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respuestaLogout(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    respuestaLogout(403, ['ok' => false, 'mensaje' => 'Se requiere HTTPS.']);
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
// Si el Access Token ya venció, Android puede cerrar sesión enviando el
// refresh token como JSON, incluso si conserva el Bearer vencido en la cabecera.
$tokenRenovacion = '';

if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === 0) {
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
        respuestaLogout(413, ['ok' => false, 'mensaje' => 'Solicitud demasiado grande.']);
    }
    $requestBody = (string) file_get_contents('php://input', false, null, 0, 8193);
    if (strlen($requestBody) > 8192) {
        respuestaLogout(413, ['ok' => false, 'mensaje' => 'Solicitud demasiado grande.']);
    }
    $entrada = json_decode($requestBody, true);
    if (is_array($entrada) && is_string($entrada['refresh_token'] ?? null)
        && preg_match('/^izzyr_[a-f0-9]{64}$/D', $entrada['refresh_token'])) {
        $tokenRenovacion = $entrada['refresh_token'];
    }
}

if ($tokenRenovacion === '' && !preg_match('/^Bearer\s+(izzy_[a-f0-9]{64})$/i', $authorization, $m)) {
    respuestaLogout(401, [
        'ok' => false,
        'mensaje' => 'Token requerido.'
    ]);
}

try {
    require_once dirname(__DIR__, 3) . '/core/services/AuthService.php';

    $servicio = new IzzyApiAuthService();

    if ($tokenRenovacion !== '') {
        $servicio->logoutConRefresh($tokenRenovacion);
    } else {
        $servicio->logout($m[1]);
    }

    respuestaLogout(200, [
        'ok' => true,
        'mensaje' => 'Sesión cerrada.',
        'data' => null
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
