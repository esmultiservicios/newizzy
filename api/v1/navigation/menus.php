<?php
/** Árbol de navegación filtrado por plan y privilegio del usuario autenticado. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function responderMenusApi(int $status, array $body): void
{
    if (!array_key_exists('mensaje', $body)) $body['mensaje'] = $status >= 400 ? 'No se pudo completar la solicitud.' : '';
    if (!array_key_exists('data', $body)) $body['data'] = null;
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    responderMenusApi(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    responderMenusApi(403, ['ok' => false, 'mensaje' => 'Se requiere HTTPS.']);
}
$authorization = '';
foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'Authorization'] as $key) {
    if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) { $authorization = trim($_SERVER[$key]); break; }
}
if ($authorization === '' && function_exists('getallheaders')) {
    foreach (getallheaders() as $name => $value) {
        if (strcasecmp((string)$name, 'Authorization') === 0 && is_string($value)) { $authorization = trim($value); break; }
    }
}
if (!preg_match('/^Bearer\s+(izzy_[a-f0-9]{64})$/i', $authorization, $matches)) {
    responderMenusApi(401, ['ok' => false, 'mensaje' => 'Token requerido.']);
}
try {
    require_once dirname(__DIR__, 3) . '/core/api/ApiNavigationService.php';
    $menus = (new IzzyApiNavigationService())->menus($matches[1]);
    responderMenusApi(200, ['ok' => true, 'mensaje' => 'Menús autorizados cargados.', 'data' => ['menus' => $menus]]);
} catch (DomainException $e) {
    responderMenusApi(401, ['ok' => false, 'mensaje' => 'Sesión inválida o vencida.']);
} catch (Throwable $e) {
    error_log('IZZY API navigation menus: ' . $e->getMessage());
    responderMenusApi(500, ['ok' => false, 'mensaje' => 'No se pudieron consultar los menús.']);
}
