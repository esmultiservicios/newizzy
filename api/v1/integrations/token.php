<?php
/** Intercambio seguro de credenciales de servidor por Bearer temporal. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function izzyTokenReply(int $status,array $body): void {
    http_response_code($status);
    echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD']??'')!=='POST') {header('Allow: POST');izzyTokenReply(405,['ok'=>false,'mensaje'=>'Método no permitido.']);}
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') izzyTokenReply(403,['ok'=>false,'mensaje'=>'HTTPS requerido.']);
if (stripos((string)($_SERVER['CONTENT_TYPE']??''),'application/json')!==0) izzyTokenReply(415,['ok'=>false,'mensaje'=>'Envíe JSON.']);
if ((int)($_SERVER['CONTENT_LENGTH']??0)>4096) izzyTokenReply(413,['ok'=>false,'mensaje'=>'Solicitud demasiado grande.']);
$body=(string)file_get_contents('php://input',false,null,0,4097);
if (strlen($body)>4096) izzyTokenReply(413,['ok'=>false,'mensaje'=>'Solicitud demasiado grande.']);
$request=json_decode($body,true);
if (!is_array($request)) izzyTokenReply(400,['ok'=>false,'mensaje'=>'JSON inválido.']);
try {
    require_once dirname(__DIR__,3).'/core/api/ApiClientAuthService.php';
    $response=(new IzzyApiClientAuthService())->emitirToken((string)($request['client_id']??''),(string)($request['client_secret']??''));
    izzyTokenReply(200,['ok'=>true,'mensaje'=>'Token emitido.','data'=>$response]);
} catch (DomainException $e) {
    izzyTokenReply(401,['ok'=>false,'mensaje'=>'Credenciales inválidas o integración no autorizada.']);
} catch (Throwable $e) {
    error_log('IZZY API integration token: '.$e->getMessage());
    izzyTokenReply(500,['ok'=>false,'mensaje'=>'No se pudo emitir el token.']);
}
