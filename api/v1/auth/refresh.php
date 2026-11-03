<?php
/**
 * IZZY API V1 - Renovación segura de la sesión móvil.
 * POST /api/v1/auth/refresh.php
 * Body: {"refresh_token":"izzyr_..."}
 * No requiere Authorization: Bearer, ya que puede haber vencido.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function respuestaRenovacion(int $codigo, array $datos): void
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respuestaRenovacion(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    respuestaRenovacion(403, ['ok' => false, 'mensaje' => 'Se requiere HTTPS.']);
}

if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) {
    respuestaRenovacion(415, ['ok' => false, 'mensaje' => 'Envíe JSON.']);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
    respuestaRenovacion(413, ['ok' => false, 'mensaje' => 'Solicitud demasiado grande.']);
}

$entrada = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($entrada)) {
    respuestaRenovacion(400, ['ok' => false, 'mensaje' => 'JSON inválido.']);
}

$refreshToken = $entrada['refresh_token'] ?? '';
if (!is_string($refreshToken) || !preg_match('/^izzyr_[a-f0-9]{64}$/D', $refreshToken)) {
    respuestaRenovacion(401, ['ok' => false, 'mensaje' => 'Token de renovación requerido.']);
}

try {
    require_once dirname(__DIR__, 3) . '/core/services/AuthService.php';

    $servicio = new IzzyApiAuthService();
    $tokens = $servicio->renovarSesion($refreshToken);

    respuestaRenovacion(200, ['ok' => true, 'data' => $tokens]);
} catch (DomainException $e) {
    respuestaRenovacion(401, [
        'ok' => false,
        'mensaje' => 'La sesión de renovación venció o fue revocada. Inicie sesión nuevamente.'
    ]);
} catch (Throwable $e) {
    error_log('IZZY API refresh: ' . $e->getMessage());
    respuestaRenovacion(500, ['ok' => false, 'mensaje' => 'No se pudo renovar la sesión.']);
}
