<?php
/**
 * IZZY API V1 - Inicio de sesión para la aplicación móvil.
 *
 * Método: POST
 * Ruta:   /api/v1/auth/login.php
 * Datos:  JSON con usuario/correo y password.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

/**
 * Devuelve una respuesta JSON y termina la solicitud.
 */
function responderApi(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Solo se aceptan solicitudes POST sobre HTTPS.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    responderApi(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    responderApi(403, ['ok' => false, 'mensaje' => 'Se requiere HTTPS.']);
}

if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) {
    responderApi(415, ['ok' => false, 'mensaje' => 'Envíe JSON.']);
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 8192) {
    responderApi(413, ['ok' => false, 'mensaje' => 'Solicitud demasiado grande.']);
}

// Leer las credenciales enviadas por la aplicación.
$input = json_decode((string) file_get_contents('php://input'), true);

if (!is_array($input)) {
    responderApi(400, ['ok' => false, 'mensaje' => 'JSON inválido.']);
}

try {
    require_once dirname(__DIR__, 3) . '/core/services/AuthService.php';

    $service = new IzzyApiAuthService();

    $service->limitarIntentos(
        (string) ($input['correo'] ?? $input['usuario'] ?? ''),
        (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')
    );

    $result = $service->login(
        (string) ($input['correo'] ?? $input['usuario'] ?? ''),
        (string) ($input['password'] ?? '')
    );

    responderApi(200, ['ok' => true, 'data' => $result]);
} catch (OverflowException $e) {
    header('Retry-After: 900');
    responderApi(429, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (InvalidArgumentException $e) {
    responderApi(422, ['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (DomainException $e) {
    responderApi(401, [
        'ok' => false,
        'mensaje' => 'No fue posible iniciar sesión. Verifique sus credenciales y el estado de su cuenta.'
    ]);
} catch (Throwable $e) {
    error_log('IZZY API login: ' . $e->getMessage());
    responderApi(500, [
        'ok' => false,
        'mensaje' => 'No fue posible completar el inicio de sesión.'
    ]);
}
